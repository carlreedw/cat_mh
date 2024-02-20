<?php

if(USERID !== 'carlreed' && USERID !== 'site_admin') {
	exit;
}

require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

$fields = ["log_id", "record", "subjectid", "sequence", "_converted", "time_of_day", "offset", "sched_dt", "kcat", "reminder"];
$completion_fields = ["record", "sequence", "time_of_day", "offset", "sched_dt"];

echo "<pre>";
$failed_to_update = "";
$updated_messages = "";

// $pids = $module->getProjectsWithModuleEnabled();
// echo(print_r($pids, true));

// foreach($pids as $pid) {
	if ($_GET['testing']) {
		$reminder_settings = (object) $module->getReminderSettings();
		echo("\$reminder_settings: " . print_r($reminder_settings, true) . "\n");
	} elseif ($_GET['count_message_types']) {
		$pid = $module->getProjectId();
		$result = $module->queryLogs("SELECT message, COUNT(message) GROUP BY message");
		$msgs = [];
		while ($row = db_fetch_assoc($result)) {
			$msgs[] = $row;
		}
		echo print_r($msgs, true);
	} elseif ($_GET['count_invites']) {
		// report to see how many inviteSent and ignoreReminder messages are logged
		// report how many are complete, missing record, subjectid, sequence, _converted, time_of_day, offset, or sched_dt
		
		$_GET['pid'] = $pid;
		
		// make counts objects and define fields
		$counts = new stdClass();
		$counts->ignoreReminder = 0;
		$counts->invitationSent = 0;
		$counts->total = 0;
		// foreach($fields as $field) {
			// $counts->$field = 0;
		// }
		
		// write db query
		$query = "SELECT message, " . implode(", ", $fields) . " WHERE message = ? OR message = ? ORDER BY sched_dt DESC LIMIT 20";
		// echo $query . "\n";
		
		// fetch messages
		$result = $module->queryLogs($query, ["invitationSent", "ignoreReminder"]);
		while ($row = db_fetch_assoc($result)) {
			// message is complete unless a $completion_fields field is missing
			// foreach($fields as $field) {
				// // if field is missing, increment count
				// if (empty($row[$field]) and $row[$field] !== "0") {
					// $counts->$field++;
				// }
			// }
			
			// $row["sched_dt"] = date("c", $row["sched_dt"]);
			
			if ($row['message'] == "invitationSent") {
				$counts->invitationSent++;
			} elseif ($row['message'] == "ignoreReminder") {
				$counts->ignoreReminder++;
			}
			echo(print_r($row, true). "\n");
			$counts->total++;
		}
		// print counts
		echo "\nPID: $pid\n";
		echo "Total invite messages: " . $counts->total . "\n";
		echo "	invitationSent messages: " . $counts->invitationSent . "\n";
		echo "	ignoreReminder messages: " . $counts->ignoreReminder . "\n";
		// foreach($fields as $field) {
			// echo "\tMessages with missing $field value: " . $counts->$field . "\n";
		// }
	} elseif ($_GET['remove_incomplete_invites']) {
		// remove invitationSent and ingoreReminder messages that are missing a completion field or have been _converted
		$field_string = "(" . implode(' IS NULL OR ', $completion_fields) . " IS NULL)";
		echo "\$field_string: $field_string\n";
		$result = $module->countLogs("(message = ? OR message = ?) AND $field_string", ["invitationSent", "ignoreReminder"]);
		$module->removeLogs("(message = ? OR message = ?) AND $field_string", ["invitationSent", "ignoreReminder"]);
		echo "Removed $result messages that are incomplete";
	} elseif ($_GET['update_invites']) {
		// update project's invitationSent and ignoreReminder messages to have subjectid values so we can deploy v2.11.0 and fix the record rename issue
		// mark copied messages as _converted = true for convenience
		$count = 0;
		$empty_record = 0;
		$empty_sid = 0;
		$failed_to_update = [];
		$result = $module->queryLogs("SELECT log_id, message, record, subjectid, sequence, offset, time_of_day, sched_dt, _converted WHERE (message = ? OR message = ?) AND (subjectid IS NULL AND _converted IS NULL)", ['invitationSent', 'ignoreReminder']);
		while ($row = db_fetch_assoc($result)) {
			if (!empty($row['record'])) {
				$row['subjectid'] = $module->getSubjectID($row['record']);
				$row['_converted'] = true;
				$old_log_id = $row['log_id'];
				if (!empty($row['subjectid'])) {
					unset($row['log_id']);
					
					$new_log_id = $module->log($row['message'], $row);
					
					if (is_numeric($new_log_id)) {
						$count++;
						// $updated_messages = $updated_messages . print_r($row, true) . "\n\n";
						$module->removeLogs("log_id = ?", $old_log_id);
					} else {
						$failed_to_update[] = $old_log_id;
					}
				} else {
					$module->removeLogs("log_id = ?", $old_log_id);
					$empty_sid++;
				}
			} else {
				$empty_record++;
			}
		}
		
		echo "Updated $count invitationSent and ignoreReminder messages that were missing a subjectid value.\n";
		echo "Messages with empty record: $empty_record\n";
		echo "Messages with record_id that has empty subjectid: $empty_sid (invite message deleted)\n";
		echo "Messages that failed to update: " . count($failed_to_update) . "\n" . print_r($failed_to_update, true);
	} elseif ($_GET['remove_copied_invites']) {
		// remove invitationSent and ingoreReminder messages that are superfluous since a copy has been added with an updated subjectid value
		$result = $module->countLogs("(message = ? OR message = ?) AND (subjectid is null or _converted is null)", ["invitationSent", "ignoreReminder"]);
		$module->removeLogs("(message = ? OR message = ?) AND (subjectid is null or _converted is null)", ["invitationSent", "ignoreReminder"]);
		echo "Removed $result invitationSent and ignoreReminder messages that had empty subjectid or _converted values.";
	} elseif ($_GET['remove_all_invites']) {
		// remove ALL invitationSent and ingoreReminder messages
		$result = $module->countLogs("(message = ? OR message = ?)", ["invitationSent", "ignoreReminder"]);
		$module->removeLogs("(message = ? OR message = ?)", ["invitationSent", "ignoreReminder"]);
		echo "Removed $result invitationSent and ignoreReminder messages.";
	}
// }

echo "</pre>";
require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
?>