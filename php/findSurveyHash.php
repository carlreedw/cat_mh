<?php

if (USERID !== 'carlreed' && USERID !== 'site_admin') {
	exit("unknown user");
}

$survey_hash = $_GET['hash'];

if (empty($survey_hash)) {
	exit("empty survey hash");
}

//	determine participant_id and survey_id
$result = $module->query("SELECT participant_id, survey_id FROM redcap_surveys_participants WHERE hash = ?", [$survey_hash]);
$row = db_fetch_assoc($result);
$participant_id = $row["participant_id"];
$survey_id = $row["survey_id"];

if (empty($participant_id)) {
	exit("empty participant_id");
}
if (empty($survey_id)) {
	exit("empty survey_id");
}

//	determine project_id from survey_id
$result = $module->query("SELECT project_id FROM redcap_surveys WHERE survey_id = ?", [$survey_id]);
$row = db_fetch_assoc($result);
$project_id = $row["project_id"];

if (empty($project_id)) {
	exit("empty project_id");
}

//	determine record from participant_id
$result = $module->query("SELECT record FROM redcap_surveys_response WHERE participant_id = ?", [$participant_id]);
$row = db_fetch_assoc($result);
$record = $row["record"];

echo "<pre>";
echo "project_id: $project_id\n";
echo "record: $record";
echo "</pre>";
exit("done");

?>