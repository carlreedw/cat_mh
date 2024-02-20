<?php

$pids_enabled = $module->getProjectsWithModuleEnabled();
$original_pid = $_GET['pid'];
$dry_run = true;

echo "<pre>";
echo "Script start timestamp: " . date("Y-m-d") . " -- " . time() . "\n";
echo "Project IDs of projects in list to have their ignoreSent and reminderSent messages updated: " . print_r($pids_enabled, true) . "\n";
echo "Purpose: This script will retrieve all ignoreSent and reminderSent messages from each project with the CAT-MH module enabled. It will then store a copy of that message in the database with the associated record's subjectid also appended. This will prevent the module from incorrectly re-sending previously emailed survey invites to participants." . "\n";
echo "DRY_RUN: $dry_run\n";
// // removing previously converted messages

// foreach($pids_enabled as $pid) {
	// $_GET['pid'] = $pid;
	
	// // update project messages
	// $result = $module->removeLogs("(message = ? OR message = ?) AND '_converted' = 1", ['invitationSent', 'ignoreReminder']);
	// $result = $module->queryLogs("SELECT message, record, subjectid, sequence, offset, time_of_day WHERE (message = ? OR message = ?) AND '_converted' IS NOT NULL", ['invitationSent', 'ignoreReminder']);
	// $count = 0;
	// echo "Counting _converted messages for project $pid\n" . "\n";
	// while ($row = db_fetch_assoc($result)) {
		// $count++;
		// // echo "	message read from db: " . print_r($row, true) . "\n";
		
		// // if (!empty($row['record'])) {
			// // $row['subjectid'] = $module->getSubjectID($row['record']);
			// // $row['_converted'] = true;
			// // if (!empty($row['subjectid'])) {
				// // $module->log($row['message'], $row);
				// // echo("	stored updated message copy back to db: " . print_r($row, true) . "\n");
			// // }
		// // }
	// }
	// echo ("	Count: " . $count) . "\n";
// }

$total_updated = 0;
foreach($pids_enabled as $pid) {
	$_GET['pid'] = $pid;
	echo $pid . "\n";
	
	// if ($pid == $original_pid) {
		$count = $module->countLogs("(message = ? OR message = ?) AND subjectid IS NULL AND _converted IS NULL", ['invitationSent', 'ignoreReminder']);
		echo "Count: $count\n";
	// }
	
	// update project messages
	// $result = $module->queryLogs("SELECT message, record, subjectid, sequence, offset, time_of_day WHERE (message = ? OR message = ?) AND subjectid IS NULL AND _converted IS NULL", ['invitationSent', 'ignoreReminder']);
	// $count = $module->countLogs("(message = ? OR message = ?) AND subjectid IS NULL AND _converted IS NULL", ['invitationSent', 'ignoreReminder']);
	// echo "Reading (relevant) messages for project $pid\n" . "\n";
	// while ($row = db_fetch_assoc($result)) {
		// $count++;
		// echo "	message read from db: " . print_r($row, true) . "\n";
		
		// if (!empty($row['record']) && !$dry_run) {
			// $row['subjectid'] = $module->getSubjectID($row['record']);
			// // $row['subjectid'] = null;
			// $row['_converted'] = true;
			// if (!empty($row['subjectid'])) {
				// $module->log($row['message'], $row);
				// echo("	stored updated message copy back to db: " . print_r($row, true) . "\n");
			// }
		// }
	// }
	// echo ("	Count: " . $count) . "\n";
	// $total_updated = $total_updated + $count;
}

// echo "Total messages updated: $total_updated\n";
echo "Script execution completed.";
echo "</pre>";
$_GET['pid'] = $original_pid;
?>