<?php

// require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

/*
query default parameters:
	message, log_id, timestamp, user, ip, project_id, record

list of all parameters used in the cat-mh module when logging messages:
	subject_id, sequence, offset, time_of_day, sched_dt, _converted, reminder, kcat, enabled, frequency, duration, delay, name, record_id, interviewID
	identifier, signature, scheduled_datetime, interview, update_id, tests_seen, date_ymd, test_name, subjectid, subjectID
	
	
*/

// if(USERID !== 'site_admin') {
if(USERID !== 'carlreed') {
	exit;
}

$fields = ["message", "log_id", "timestamp", "user", "ip", "project_id", "record", "subject_id", "sequence", "offset", "time_of_day", "sched_dt", "_converted", "reminder", "kcat", "enabled", "frequency", "duration", "delay", "name", "record_id", "interviewID", "identifier", "signature", "scheduled_datetime", "interview", "update_id", "tests_seen", "date_ymd", "test_name", "subjectid"];

$pid = $module->getProjectId();
$messages = [];
$query = "SELECT " . implode(", ", $fields) . " WHERE project_id = ? LIMIT 400000, 200000";
$result = $module->queryLogs($query, [$pid]);

while ($row = db_fetch_assoc($result)) {
	foreach($row as $index=>$field) {
		if(empty($field)) {
			unset($row[$index]);
		}
	}
	$messages[] = $row;
}

$messages[] = ["total_message_count" => count($messages)];
// echo "</pre>";
// require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';

header("Content-disposition: attachment; filename=all_messages_pid_$project_id.json");
header('Content-type: application/download');
exit(json_encode($messages));
?>