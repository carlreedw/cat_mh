<?php
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
?>
<!doctype html>
<html lang="en">
	<head>
		<!-- Required meta tags -->
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		
		<link rel="stylesheet" href="//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css">
		<link rel="stylesheet" href="<?php echo($module->getUrl('css/report.css')); ?>">
		<title>CAT-MH Interview Results</title>
	</head>
	<body>
		<h2>CAT-MH Interview Results</h2>
		<table style='width:90%' id='results'>
			<thead>
				<tr>
					<th>Record ID</th>
					<?php
					$add_columns = $module->getProjectSetting('add-subjectid-columns');
					if ($add_columns) {
						echo("<th>subjectid</th>");
					}
					?>
					<th>Date Scheduled</th>
					<th>Date Taken</th>
					<th>Sequence</th>
					<th>Test Type</th>
					<th>Diagnosis</th>
					<th>Confidence</th>
					<th>Severity</th>
					<th>Category</th>
					<th>Precision</th>
					<th>Probability</th>
					<th>Percentile</th>
					<th>PHQ-9 Equivalency</th>
					<th>Reviewed</th>
				</tr>
			</thead>
			<tbody>
<?php
	// get all record IDs
	$params = [
		"project_id" => $module->getProjectId(),
		"return_format" => 'array',
		"fields" => [$module->getRecordIDField()]
	];
	
	// filter by record, sequence, and datetime if applicable
	$recordFilter = htmlentities($_GET['record'], ENT_QUOTES, 'UTF-8');
	if (!empty($recordFilter))
		$params['records'] = $recordFilter;
	if (isset($_GET['seq']))
		$seqFilter = htmlentities($_GET['seq'], ENT_QUOTES, 'UTF-8');
	if (isset($_GET['sched_dt']))
		$schedFilter = htmlentities($_GET['sched_dt'], ENT_QUOTES, 'UTF-8');
	
	$data = \REDCap::getData($params);
	foreach($data as $rid => $record) {
		$sid = $module->getSubjectID($rid);
		if (!$module->checkSubjectid($sid)) {
			continue;
		}
		// get this patients interviews
		$interviews = $module->getInterviewsByRecordID($rid);
		
		foreach($interviews as $i => $interview) {
			$sequence_name = $interview->sequence;
			$sequence_datetime = $interview->scheduled_datetime;
			$seq_ok = (empty($seqFilter) or $seqFilter == $sequence_name);
			$sched_ok = (empty($schedFilter) or $schedFilter == $sequence_datetime);
			if ($interview->status == "4" and !empty($interview->results) and $sched_ok and $seq_ok) {
				foreach($interview->results->tests as $j => $test) {
					// make reviewed checkbox
					$test_name = $test->label;
					
					// k-cat support
					if ($interview->kcat) {
						$test_reviewed = $module->countLogs("message = ? AND subjectid = ? AND sequence = ? AND scheduled_datetime = ? AND test_name = ? AND kcat = ?", [
							'reviewed_test',
							$sid,
							$sequence_name,
							$sequence_datetime,
							$test_name,
							$interview->kcat
						]);
					} else {
						$test_reviewed = $module->countLogs("message = ? AND subjectid = ? AND sequence = ? AND scheduled_datetime = ? AND test_name = ?", [
							'reviewed_test',
							$sid,
							$sequence_name,
							$sequence_datetime,
							$test_name
						]);
					}
					$checked = '';
					if ($test_reviewed) {
						$test_reviewed = 'true';
						$checked = ' checked';
					} else {
						$test_reviewed = 'false';
					}
					
					$phq9 = '';
					if ($test->type == 'DEP' and is_float($test->severity)) {
						$phq9 = 3.57 + 0.29 * ($test->severity);
						// $test->severity = strval($test->severity) . " (PHQ-9: $phq9)";
					}
					
					$reviewed_cbox = "<input type='checkbox' class='reviewed_cbox' data-test='$test_name' data-sid='$sid' data-seq='$sequence_name' data-date='$sequence_datetime' data-kcat='{$interview->kcat}' data-checked='$test_reviewed'$checked>";
					
					$subjectid_col = "";
					if ($add_columns) {
						if (empty($sid)) {
							$subjectid_col = "<td>(none)</td>";
						} else {
							$subjectid_col = "<td>$sid</td>";
						}
					}
					
					echo("
					<tr>
						<td>{$rid}</td>
						$subjectid_col
						<td>$sequence_datetime</td>
						<td>" . date("Y-m-d H:i", $interview->timestamp) . "</td>
						<td>$sequence_name</td>
						<td>$test_name</td>
						<td>{$test->diagnosis}</td>
						<td>{$test->confidence}</td>
						<td>{$test->severity}</td>
						<td>{$test->category}</td>
						<td>{$test->precision}</td>
						<td>{$test->prob}</td>
						<td>{$test->percentile}</td>
						<td>$phq9</td>
						<td>$reviewed_cbox</td>
					</tr>");
				}
			}
		}
	}
?>
			</tbody>
		</table>
		<script src="https://code.jquery.com/jquery-3.6.0.js"></script>
		<script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
		<script src="https://cdn.datatables.net/buttons/1.7.0/js/dataTables.buttons.min.js"></script>
		<script src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.flash.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.2.0/jszip.min.js"></script>
		<script src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.html5.min.js"></script>
		<script src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.print.min.js"></script>
		<script type='text/javascript'>
			CATMH = {}
			CATMH.review_ajax_url = "<?php echo $module->getUrl('ajax/review_ajax.php'); ?>"
		</script>
		<script src="<?php echo $module->getUrl('js/results.js'); ?>"></script>
	</body>
</html>
<?php
require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
?>