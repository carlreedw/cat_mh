<?php
if(USERID !== 'carlreed' && USERID !== 'site_admin') {
	exit;
}

$pid = $module->getProjectId();
$_GET['pid'] = $pid;
$fields = ["project_id", "log_id", "record", "subjectid", "sequence", "_converted", "time_of_day", "offset", "sched_dt"];

// write db query
$query = "SELECT message, " . implode(", ", $fields) . " WHERE (message = ? OR message = ?) AND project_id = ? ORDER BY log_id";

// fetch messages
$messages = [];
$result = $module->queryLogs($query, ["invitationSent", "ignoreReminder", $pid]);
$invitationSentCount = 0;
$ignoreReminderCount = 0;
while ($row = db_fetch_assoc($result)) {
	$messages[] = $row;
	if ($row['message'] == 'invitationSent') {
		$invitationSentCount++;
	} elseif ($row['message'] == 'ignoreReminder') {
		$ignoreReminderCount++;
	}
}

$messages["invitationSentCount"] = $invitationSentCount;
$messages["ignoreReminderCount"] = $ignoreReminderCount;

// write file
header("Content-disposition: attachment; filename=invite_message_export_pid_$pid.json");
header('Content-type: application/download');
exit(json_encode($messages));