<?php

require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
echo "<pre>";
$pid = $module->getProjectId();

// $data = \REDCap::getData('3');
// print_r(json_encode($data));

// // DETERMINE IF SEQUENCE SET TO HIDE QUESTION NUMBER
	// $seq_name = urldecode($_GET['sequence']);
	// echo "seq_name: $seq_name\n";
	// $seq_index = array_search($seq_name, $module->getProjectSetting('sequence'));
	// echo "seq_index: $seq_index\n";
	// $hide_this_seq = $module->getProjectSetting('hide_question_number')[$seq_index];
	// echo "hide_this_seq: $hide_this_seq\n";

// PRINT INVITE LOG MESSAGES
	// $result = $module->queryLogs("SELECT message, subjectid, sequence, offset, time_of_day WHERE message = ? OR message = ?", ['invitationSent', 'ignoreReminder']);
	// while ($row = db_fetch_assoc($result)) {
		// echo "row: " . print_r($row, true);
	// }

// // REMOVE ALL LOGGED MESSAGES
	// $module->removeLogs("message = ? OR message = ?", ['invitationSent', 'ignoreReminder']);
	
	// $module->removeLogs("true");
	// print_r($module->scheduleSequence('Monthly CAT-MH', 1, '12:15'));
	// print_r($module->scheduleSequence('Monthly CAT-MH', 2, '12:15'));
	// print_r($module->scheduleSequence('Lots of Tests', 1, '12:15'));
	// print_r($module->scheduleSequence('Lots of Tests', 2, '12:15'));
	
// // REMOVE SCHEDULED SEQUENCES WITH NO NAME
	// return $module->removeLogs("name IS NULL AND offset IS NOT NULL AND time_of_day IS NOT NULL", []);
	
// // REMOVE INVITE SENT LOGS
	// $module->removeLogs("message= ?", ['reviewed_test']);
	// echo "coutn: " . $module->countLogs("message= ?", ['catmh_interview']);
	// echo "\n\n";
	
// // RUN EMAILER CRON
	// $module->sendInvitations(strtotime("+35 days"));
	// $module->sendInvitations(time());

// // PRINT ALL PROJECT SETTINGS
	// print_r($module->getProjectSettings());
	
// // PRINT PROJECT METADATA
	// // $project = new Project($pid);
	// $project = $Proj;
	// echo("Proj type: " . gettype($Proj) . "\n");
	// print_r($project->metadata['subjectid']['form_name']);
	
// // TEST GET SEQUENCE INDEX
	// $seq = "seq x";
	// $seq_index = $module->getSequenceIndex($seq);
	// echo "seq $seq index: $seq_index";

// // TEST GET TEST LABEL
	// $seq = 'seq x';
	// $test = 'dep';
	// $label = $module->getTestLabel($seq, $test);
	// echo "seq $seq test $test label: $label";

// // RESET FUTURE CRON HISTORY VALUES TO PRESENT
	// $now = date("Y-m-d H:i:s");
	// db_query("UPDATE redcap_crons
		// SET cron_last_run_start = '$now',
		// cron_last_run_end = '$now'
		// WHERE cron_last_run_start > '$now' OR cron_last_run_end > '$now'\n");
	// db_query("UPDATE redcap_crons_history
		// SET cron_run_start = '$now',
		// cron_run_end = '$now'
		// WHERE cron_run_start > '$now' OR cron_run_end > '$now'");

// // PRINT REVIEWED TESTS
	// $result = $module->queryLogs("SELECT message, subjectid, sequence, scheduled_datetime, test_name, kcat WHERE message = ?", ['reviewed_test']);
	// while ($row = db_fetch_assoc($result)) {
		// echo "row: " . print_r($row, true);
	// }

// // SHOW ALL SCHEDULED SEQUENCES
	// $result = $module->queryLogs("SELECT message, name, offset, time_of_day WHERE message = ?", ['scheduleSequence']);
	// while ($row = db_fetch_assoc($result)) {
		// echo "row: " . print_r($row, true);
	// }
	
// // KCAT TEST/LABEL GETTER TESTS
	// $kcat_seq = 'kcat orig labels';
	// echo "print kcat sequence default tests/labels\n";
	
	// echo "\$module->getKCATTests($kcat_seq, 'primary'):\n";
	// $tests = $module->getKCATTests($kcat_seq, 'primary');
	// echo print_r($tests, true) . "\n";
	// echo "\$module->getKCATTestLabels(\$tests, $kcat_seq, 'primary'):\n";
	// echo print_r($module->getKCATTestLabels($tests, $kcat_seq, 'primary'), true) . "\n";
	
	// echo "\$module->getKCATTests($kcat_seq, 'secondary'):\n";
	// $tests = $module->getKCATTests($kcat_seq, 'secondary');
	// echo print_r($tests, true) . "\n";
	// echo "\$module->getKCATTestLabels(\$tests, $kcat_seq, 'secondary'):\n";
	// echo print_r($module->getKCATTestLabels($tests, $kcat_seq, 'secondary'), true) . "\n";
	
	
	// $kcat_seq = 'kcat1 alt labels';
	// echo "print kcat sequence alternate tests/labels\n";
	
	// echo "\$module->getKCATTests($kcat_seq, 'primary'):\n";
	// $tests = $module->getKCATTests($kcat_seq, 'primary');
	// echo print_r($tests, true) . "\n";
	// echo "\$module->getKCATTestLabels(\$tests, $kcat_seq, 'primary'):\n";
	// echo print_r($module->getKCATTestLabels($tests, $kcat_seq, 'primary'), true) . "\n";
	
	// echo "\$module->getKCATTests($kcat_seq, 'secondary'):\n";
	// $tests = $module->getKCATTests($kcat_seq, 'secondary');
	// echo print_r($tests, true) . "\n";
	// echo "\$module->getKCATTestLabels(\$tests, $kcat_seq, 'secondary'):\n";
	// echo print_r($module->getKCATTestLabels($tests, $kcat_seq, 'secondary'), true) . "\n";
	
// // PRINT LIST OF REVIEWED TESTS
	// $result = $module->queryLogs("SELECT interview WHERE message = ?", ['catmh_interview']);
	// while ($row = db_fetch_assoc($result)) {
		// $interview = json_decode($row['interview']);
		// foreach ($interview->results->tests as $test) {
			// if ($test->reviewed)
				// echo "reviewed test->label: " . print_r($test->label, true) . "\n";
		// }
	// }
	
// // SCHEDULE A SINGLE SEQUENCE
	// print_r($module->scheduleSequence('Monthly CAT-MH', 1, '00:00'));

// // TEST BUILD QUESTION TO TEST MAP FUNCTION
	// $module->buildQuestionTestMap();
	// print_r($module->questionTestMap);
	
	// CHECKING TO SEE THAT ALL TEST TYPES ARE IN QUESTION ID TO TEST NAME MAP
	// $tests = [];
	// foreach ($module->questionTestMap as $short_name_test_array) {
		// $tests_as_string = json_encode($short_name_test_array);
		// if (!in_array($tests_as_string, $tests, true)) {
			// $tests[] = $tests_as_string;
		// }
	// }
	// echo print_r($tests, true) . "\n\n";
	
	// $tests_array = [
		// $module->testTypes,
		// $module->kcat_primary_tests,
		// $module->kcat_optional_primary_tests,
		// $module->kcat_secondary_tests
	// ];
	// foreach ($tests_array as $array) {
		// foreach ($array as $test1 => $value) {
			// if (in_array($test1, $tests, true)) {
				// echo $test1 . " FOUND\n";
			// } else {
				// echo $test1 . " MISSING!\n";
			// }
		// }
	// }

	// public $testTypes = [
	// public $kcat_primary_tests = [
	// public $kcat_optional_primary_tests = [
	// public $kcat_secondary_tests = [

// add interview object for record test eda
	// $interview = json_decode(file_get_contents($module->getModulePath() . "ignore/_TEST_EDA_INTERVIEW.json"));
	// $module->updateInterview($interview);
	// $ints = $module->getInterviewsByRecordID("1");
	// print_r($ints);

// testing getKCATSequenceIndex
	// $result = $module->getKCATSequenceIndex("K-CAT Sequence 1");
	// echo(print_r($result, true) . "\n");
	// if (is_numeric($result)) {
		// echo("is_numeric\n");
	// }
	// carl_log("hi");
	
// test chamindwell automation cron method
	// echo $module->emailer_cron();
	echo $module->run_chamindwell_automation();
	// $module->test_cron();
	
// test module->checkSubjectid($sid) function for validity
	// valid sid, valid 32, empty string, non-string, non-alphanumeric, non-unique (pid 37 local record 1), null
	// $sids = ["abc123", "extNWnguq6WOtKIglk7SrgiXCcAlO3Xi", "", 2362983, "abc_23", "zxivjoweirng", null];
	// foreach ($sids as $sid) {
		// echo("module->checkSubjectid('$sid') = " . print_r($module->checkSubjectid($sid), true) . "\n");
	// }

echo "</pre>";
require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
?>