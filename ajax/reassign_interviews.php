<?php
// Get params
if (!isset($_POST['from_record']) || !isset($_POST['to_record'])) exit;
$_POST['from_record'] = (string)$_POST['from_record'];
$_POST['to_record'] = (string)$_POST['to_record'];
if ($_POST['from_record'] === "" || $_POST['to_record'] === "") exit;
$from_record = trim(rawurldecode(urldecode($_POST['from_record'])));
$to_record = trim(rawurldecode(urldecode($_POST['to_record'])));
$pid = $module->getProjectId();

// declare empty object to return with .success/.message
$json = new \stdClass();

// check to see if to_record exists
$to_sid = $module->getSubjectID($to_record);

if (!$module->checkSubjectid($sid)) {
	$json->message = "Error - record has invalid subjectid: $sid " . htmlspecialchars($to_record);
	exit(json_encode($json));
}
	
if (empty($to_sid)) {
	$json->message = "Error - couldn't find existing subject ID for record " . htmlspecialchars($to_record);
	exit(json_encode($json));
}

// get interview data
$interviews = $module->getInterviewsByRecordID($from_record);
if (empty($interviews)) {
	$json->message = "Error - couldn't find existing interviews for record " . htmlspecialchars($from_record);
	exit(json_encode($json));
}

// update interview (and if applicable, their .results) objects .subjectid property
$updated_count = 0;
foreach ($interviews as $interview) {
	$interview->subjectID = $to_sid;
	if ($interview->results)
		$interview->results->subjectId = $to_sid;
	
	$interview->update_id = $interview->update_id + 1;
	
	// log updated interview, if successful, remove from logs the old copy of the interview (associated with from_record)
	$parameters = [
		"subjectid" => $to_sid,
		"sequence" => $interview->sequence,
		"interviewID" => $interview->interviewID,
		"identifier" => $interview->identifier,
		"signature" => $interview->signature,
		"scheduled_datetime" => $interview->scheduled_datetime,
		"interview" => json_encode($interview),
		"update_id" => $interview->update_id,
		"record_id" => $to_record,
	];
	if ($interview->kcat)
		$parameters['kcat']= $interview->kcat;
	
	// log new interview message
	$new_log_id = $module->log('catmh_interview', $parameters);
	if (!empty($new_log_id)) {
		$updated_count = $updated_count + 1;
		$deleted = $module->removeLogs("message = ? AND record_id = ? AND interviewID = ?", [
			"catmh_interview",
			$from_record,
			$interview->interviewID,
		]);
		if (empty($deleted)) {
			$json->message = "Error - copied interview with ID " . $interview->interviewID . " but couldn't remove the old interview object. ";
			exit(json_encode($json));
		}
	}
}

$json->success = true;
$json->message = "Successfully reassigned $updated_count interviews from records '$from_record' to '$to_record'";

$log_msg = $json->message;
$ids = print_r(array_column($interviews, "interviewID"), true);
$log_msg .= "\nInterviewIDs of interviews reassigned:\n$ids";
\REDCap::logEvent("CAT-MH External Module", $log_msg, NULL, NULL, NULL, $module->getProjectId());

exit(json_encode($json));
