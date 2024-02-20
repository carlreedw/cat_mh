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

echo "<pre>";
$pid = $module->getProjectId();
$result = $module->removeLogs("project_id = ?", [$pid]);
echo "result: " . print_r($result, true);
echo "</pre>";
?>