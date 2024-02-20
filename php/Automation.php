<?php

use Google\Auth\ApplicationDefaultCredentials;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

// $cha_module_path = $module->getModulePath();
require_once $cha_module_path . 'vendor/autoload.php';

class Automation {
	public $sheets_1_doc_id = "19gT88Zd-GJGkyH3g7g3oUaByfSOHvoyUyhKj7S3iOzM"; // CHAMindWell Consent Form
	private $sheets_2_doc_id = "1bUrJ619jjE446oXxyWCioR9yjeSUdGwpTJiG13zelsc"; // ***Primary CHAMindWell Enrolled/Handoff to groups/studies
	private $sheets_3_doc_id = "17JK6rFrJBRoNx716k6ZU3rqW-ItVNH67Uqylj6oWDXs"; // CHA MindWell en Español Consent Form (Responses)
	private $agenda_insert_marker = "// End Important Documents";
	private $srv_acct_email = "catmh-automation@redcap-to-sheets-testing.iam.gserviceaccount.com";
	
	public function __construct($module) {
		$this->module = $module;
	}
	
	public function determineLocality() {
		if (gethostname() == "CARLPC") {
			$gcred_path = "D:/CHA/cha_credentials/service_acct_creds.json";
			$this->agenda_doc_id = "1uAfNi4bgwcPV7B9bjEXENzCbtr3Cao1oml1sPnCdOjc";
			$this->english_chamindwell_pid = 34;
			$this->spanish_chamindwell_pid = 30;
		} else {
			$gcred_path = $this->module->getModulePath() . "service_acct_creds.json";
			// $this->agenda_doc_id = "1-eAqmC9iklfuLkSnKI2xg4ziz_-2f5XbapjJ19ScPQQ";	// the real document
			$this->agenda_doc_id = "1kJ151aFu-_ZWcAXodYzq9dXHv06-PyV6qZ5f09Dl7D8";	// "Agenda Test Document" not sure what this document is for.. maybe sharing with Fiona only?
			$this->english_chamindwell_pid = 1031;
			$this->spanish_chamindwell_pid = 890;
			/*Project IDs - from 'Fiona notes.txt'
			CHAMindWell Launch 1: 606
			CHAMindWell Launch 2: 1031
			CHAMindWell en Espanol Launch 1: 890*/
		}
		echo("agenda_doc_id: " . $this->agenda_doc_id . "\n");
		putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $gcred_path);
	}
	
	public function performAutomation() {
		/*	strategy for testing the automation using this function (performAutomation):
		1. GET the json representation of the agenda document from Google Docs API
		2. GET the spreadsheet data from the enrollment worksheets provided by Google Sheets API
		3. Calculate the enrollment statistics and tabulate into a request object that we will send as a batchUpdate for the agenda doc
		4. Determine the appropriate insertion index by parsing the document and finding the end of the table of contents
		5. Create and submit a request to update the agenda document
		6. Report or log success/failure as appropriate
		*/
		
		$stats = $this->tabulateEnrollmentStats();
		$this->insertEnrollmentStats($stats);
	}
	
	private function getClient() {
		if (!empty($this->client)) {
			return $this->client;
		}
		// define the scopes for your API call
		$scopes = [
			"https://www.googleapis.com/auth/documents",
			"https://www.googleapis.com/auth/documents.readonly",
			"https://www.googleapis.com/auth/drive",
			"https://www.googleapis.com/auth/drive.file",
			"https://www.googleapis.com/auth/drive.readonly",
		];
		
		$middleware = ApplicationDefaultCredentials::getMiddleware($scopes);
		$stack = HandlerStack::create();
		$stack->push($middleware);
		
		// create the HTTP client
		$client = new Client([
		  'handler' => $stack,
		  'auth' => 'google_auth',  // authorize all requests
		  // 'base_uri' => 'https://docs.googleapis.com/',
		]);
		$this->client = $client;
		
		return $client;
	}
	
	private function determineInsertIndex() {
		$client = $this->getClient();
		
		// set mask fields to get only text content and start/end indices
		$mask_fields_text = 'body.content(paragraph.elements(textRun.content,startIndex,endIndex))';
		$res = $client->request('GET', 'https://docs.googleapis.com/v1/documents/' . $this->agenda_doc_id . '?fields=' . $mask_fields_text);
		$agenda_data = json_decode($res->getBody());
		
		// // local testing
		// $agenda_data = json_decode(file_get_contents('C:\Users\carl\Desktop\test_agenda_masked.txt'));
		// if (!$agenda_data->body || !$agenda_data->body->content) {
			// throw new \Exception("error: couldn't find body or body->content in returned agenda data");
		// }
		
		foreach($agenda_data->body->content as $section) {
			foreach ($section->paragraph->elements as $element) {
				if (strpos($element->textRun->content, $this->agenda_insert_marker) !== false) {
					return $element->endIndex + 1;
				}
			}
		}
	}
	
	private function tabulateEnrollmentStats() {
		/*
		Enrollment Statistic Variable
			Workbook | Worksheet
				How to calculate the value
		//
		lang->total_new_enrollments
			CHAMindWell Consent Form | Form Responses 3
				count # rows with matching date (col C) for last -8 to -1 days
		lang->new_connected_path_enrollments:
			CHAMindWell Consent Form | Form Responses 3
				for each cell in col C that's counted as a new enrollment, if that enrollee is marked 'Connected' in column AM, increment this field value
		lang->new_community_path_enrollments
			CHAMindWell Consent Form | Form Responses 3
				for each cell in col C that's counted as a new enrollment, if that enrollee is marked 'Community' in column AM, increment this field value
		lang->total_active_overall_enrollments
			***Primary CHAMindWell Enrolled/Handoff to groups/studies | Pt Identifier Sheet/Enrolled
				Count the number of "Y" in column A that also have a non-empty value for column E
		lang->total_registered_to_date
			***Primary CHAMindWell Enrolled/Handoff to groups/studies | Pt Identifier Sheet/Enrolled
				Count the number of non-empty cells in column E
		lang->catmh_completion
			REDCap projects 606 (CHAMindWell Launch 1), 890 (en Espanol), and 1031 (CHAMindWell Launch 2)
				For each active patient in each project above, divide number of completed CAT-MH surveys by the number of issued CAT-MH surveys
		*/
		
		// create object to hold enrollment stats for both language projects
		$stats = new stdClass();
		$stats->english = new stdClass();
		$stats->spanish = new stdClass();
		
		// calculate total_new_enrollments, new_connected_path_enrollments, and new_community_path_enrollments
		$days_ago_8 = strtotime(date("Y-m-d")) - (24 * 60 * 60) * 8;
		$days_ago_1 = strtotime(date("Y-m-d")) - (24 * 60 * 60) * 1;
		$stats->dates = new stdClass();
		$stats->dates->begin = date("n/j/y", $days_ago_8);
		$stats->dates->end = date("n/j/y", $days_ago_1);
		
		$stats->english->total_new_enrollments = 0;
		$stats->english->new_connected_path_enrollments = 0;
		$stats->english->new_community_path_enrollments = 0;
		$stats->english->total_registered_to_date = 0;
		$stats->english->total_active_overall_enrollments = 0;
		
		// fetch and process data values
		$column_data = $this->getSheetColumns($this->sheets_1_doc_id, '2067083601', 'Form Responses 3', ['C', 'H', 'AM']);
		$consent_data = &$column_data;
		foreach($column_data['C']->values as $row => $value) {
			$this_ts = strtotime($value[0]);
			if ($this_ts) {
				if ($this_ts >= $days_ago_8 && $this_ts <= $days_ago_1) {
					$stats->english->total_new_enrollments++;
					
					$cell_text = strtolower($column_data['AM']->values[$row][0]);
					if (strpos($cell_text, "connected path") !== false) {
						$stats->english->new_connected_path_enrollments++;
					} elseif (strpos($cell_text, "community path") !== false) {
						$stats->english->new_community_path_enrollments++;
					}
				}
			}
		}
		
		// read from Pt Identifier Sheet/Enrolled columns A, B, E, G (active y/n, path name, project, record ID)
		$column_data = $this->getSheetColumns($this->sheets_2_doc_id, '0', '', ['A', 'B', 'E', 'G']);
		foreach($column_data['A']->values as $row => $value) {
			$enrolled = false;
			if ($value[0] == 'Y') {
				$enrolled = true;
			}
			
			$path = $column_data['B']->values[$row][0];
			$redcap_project = $column_data['E']->values[$row][0];
			$record_id = $column_data['G']->values[$row][0];
			if (!empty($record_id)) {
				$stats->english->total_registered_to_date++;
				if ($enrolled) {
					$stats->english->total_active_overall_enrollments++;
				}
			}
		}
		
		// calculate catmh_completion
		list($completed_interviews, $counted_interviews) = $this->calculateEnglishCompletionStats($consent_data, $stats->dates->begin, $stats->dates->end);
		$stats->english->catmh_completion = "$completed_interviews / $counted_interviews (" . number_format($completed_interviews/$counted_interviews * 100, 1) . "%)";
		
		// // // SPANISH
		// calculate total_new_enrollments, new_connected_path_enrollments, and new_community_path_enrollments
		$column_data = $this->getSheetColumns($this->sheets_3_doc_id, '990544801', '', ['B', 'C']);
		$stats->spanish->total_new_enrollments = 0;
		$stats->spanish->total_active_overall_enrollments = 0;
		$stats->spanish->total_registered_to_date = 0;
		
		// carl_log("spanish column_data:\n" . print_r($column_data, true));
		// $stats->spanish->catmh_completion = "Not Yet Implemented";
		list($completed_interviews, $counted_interviews) = $this->calculateSpanishCompletionStats($stats->dates->begin, $stats->dates->end);
		$stats->spanish->catmh_completion = "$completed_interviews / $counted_interviews (" . number_format($completed_interviews/$counted_interviews * 100, 1) . "%)";
		
		foreach($column_data['C']->values as $row => $value) {
			if ($value[0] == "Acepto") {
				$stats->spanish->total_active_overall_enrollments++;
				$stats->spanish->total_registered_to_date++;
				$enroll_ts = strtotime($column_data['B']->values[$row][0]);
				if ($enroll_ts >= $days_ago_8 && $enroll_ts <= $days_ago_1) {
					$stats->spanish->total_new_enrollments++;
				}
			}
		}
		
		return $stats;
	}
	
	private function insertEnrollmentStats($stats) {
		// this function writes a formatted block of text to the agenda document that shows new and overall enrollment statistics
		$client = $this->getClient();
		$requests_obj = new stdClass();
		
		// create and add text insert request to request array
		$request1 = new stdClass();
		$insert_obj = new stdClass();
		$location_obj = new stdClass();
		
		$insert_index = $this->determineInsertIndex();
		$location_obj->index = $insert_index;
		$todays_date = date('l n/j/y');
		$todays_month = date("F");
		$insert_obj->text = $todays_date . '
CHAMindWell in English (' . $stats->dates->begin . '-' . $stats->dates->end . ')
	Total new enrollments: ' . $stats->english->total_new_enrollments . '
	New connected path enrollments: ' . $stats->english->new_connected_path_enrollments . '
	New community path enrollments: ' . $stats->english->new_community_path_enrollments . '
	Total active overall enrollments: ' . $stats->english->total_active_overall_enrollments . '
	Total registered to date: ' . $stats->english->total_registered_to_date . "
	Connected Path CAT-MH completion for the month of $todays_month : " . $stats->english->catmh_completion . '
CHAMindWell en Español 
	*New enrollments this week: (' . $stats->dates->begin . '-' . $stats->dates->end . '): ' . $stats->spanish->total_new_enrollments . '
	Total active enrollments: ' . $stats->spanish->total_active_overall_enrollments . ' 
	Total registered to date: ' . $stats->spanish->total_registered_to_date . "
	% completed CAT-MH $todays_month 2023: " . $stats->spanish->catmh_completion . '
';
		$insert_obj->location = $location_obj;
		$request1->insertText = $insert_obj;
		$requests_obj->requests[] = $request1;
		
		// add another request, a 'CreateParagraphBulletsRequest' to format the paragraphs
		$request2 = new stdClass();
		$request2->createParagraphBullets = new stdClass();
		$req_obj = $request2->createParagraphBullets;
		$req_obj->range = new stdClass();
		$req_obj->range->startIndex = $insert_index + 1 + strlen($todays_date);
		$req_obj->range->endIndex = $insert_index + strlen($insert_obj->text) - 15;
		$req_obj->bulletPreset = 'BULLET_DISC_CIRCLE_SQUARE';
		$requests_obj->requests[] = $request2;
		
		// add another request, a 'UpdateTextStyleRequest' to format the paragraphs
		$request3 = new stdClass();
		$request3->updateTextStyle = new stdClass();
		$req_obj = $request3->updateTextStyle;
		$req_obj->textStyle = new stdClass();
		$req_obj->textStyle->bold = true;
		$req_obj->textStyle->fontSize = new stdClass();
		$req_obj->textStyle->fontSize->magnitude = 11;
		$req_obj->textStyle->fontSize->unit = "PT";
		$req_obj->range = new stdClass();
		$req_obj->range->startIndex = $insert_index + 1 + strlen($todays_date);
		$req_obj->range->endIndex = $insert_index + strlen($insert_obj->text) - 15;
		$req_obj->fields = 'bold,fontSize';
		$requests_obj->requests[] = $request3;
		
		// add final request to set "New enrollments this week" to be a hyperlink
		$hyperlink_url = 'https://docs.google.com/spreadsheets/d/1bUrJ619jjE446oXxyWCioR9yjeSUdGwpTJiG13zelsc/edit#gid=0';
		$request4 = new stdClass();
		$request4->updateTextStyle = new stdClass();
		$req_obj = $request4->updateTextStyle;
		$req_obj->textStyle = new stdClass();
		$req_obj->textStyle->link = new stdClass();
		$req_obj->textStyle->link->url = $hyperlink_url;
		$req_obj->range = new stdClass();
		$req_obj->range->startIndex = $insert_index + 1 + strlen($todays_date) + strpos($insert_obj->text, 'New enrollments this week') - 31;
		$req_obj->range->endIndex = $req_obj->range->startIndex + strlen('New enrollments this week');
		$req_obj->fields = 'link';
		$requests_obj->requests[] = $request4;
		
		echo "<pre>\$insert_index: " . print_r($insert_index, true) . "\n";
		echo "\$requests_obj: " . print_r($requests_obj, true) . "<br /><br /></pre>";
		$post_body = json_encode($requests_obj);
		$response = $client->post(
			"https://docs.googleapis.com/v1/documents/" . $this->agenda_doc_id . ":batchUpdate",
			[
				'body' => $post_body
			]
		);
		echo "response code: " . $response->getStatusCode();
	}
	
	private function calculateEnglishCompletionStats($column_data, $begin_timestamp, $end_timestamp) {
		// calculate Connected Path CAT-MH completion for this month and year
		
		// get email addresses and record IDs from CHAMindWell Launch 1/2 projects
		$params = [
			"project_id" => $this->english_chamindwell_pid,
			"return_format" => 'json',
			"fields" => ["record_id", "catmh_email"]
		];
		$project1_data = json_decode(\REDCap::getData($params));
		
		$completed_interviews = 0;
		$counted_interviews = 0;
		
		foreach($project1_data as $record) {
			// find this record in col G, then find if it's connected in B, if so, tally this record's catmh interviews
			$col_index = null;
			foreach ($column_data["G"]->values as $i => $arr) {
				if ($arr[0] == $record->record_id) {
					$col_index = $i;
					break;
				}
			}
			if (!empty($col_index)) {
				$is_connected = strtolower($column_data["B"]->values[$col_index][0]) === "connected";
				if ($is_connected) {
					list($completed, $total) = $this->countCompletedInterviews($record->record_id, $begin_timestamp, $end_timestamp);
					$completed_interviews += $completed;
					$counted_interviews += $total;
					// carl_log("record_id: " . $record->record_id . ", (" . $completed_interviews . " / " . $counted_interviews . ")");
				}
			}
		}
		return [$completed_interviews, $counted_interviews];
	}
	
	private function calculateSpanishCompletionStats($begin_timestamp, $end_timestamp) {
		// calculate Connected Path CAT-MH completion for this month and year
		
		// first we need to collect a set of record IDs for which to gather interview data
		$record_ids = [];
		
		// get email addresses and record IDs from CHAMindWell Launch 1/2 projects
		$params = [
			"project_id" => $this->spanish_chamindwell_pid,
			"return_format" => 'json',
			"fields" => ["record_id"]
		];
		$project1_data = json_decode(\REDCap::getData($params));
		
		$completed_interviews = 0;
		$counted_interviews = 0;
		
		foreach($project1_data as $record) {
			list($completed, $total) = $this->countCompletedInterviews($record->record_id, $begin_timestamp, $end_timestamp);
			$completed_interviews += $completed;
			$counted_interviews += $total;
		}
		return [$completed_interviews, $counted_interviews];
	}
	
	public function getSheetColumns($doc_id, $sheet_id, $sheet_name, $col_array) {
		// set mask fields to get only text content and start/end indices
		$client = $this->getClient();
		$mask_fields_text = 'sheets.properties';
		$res = $client->request('GET', 'https://sheets.googleapis.com/v4/spreadsheets/' . $doc_id . '?fields=' . $mask_fields_text);
		$response_obj = json_decode($res->getBody()->getContents());
		$max_rows = 0;
		foreach($response_obj->sheets as $index => $sheet) {
			if ($sheet->properties->sheetId == $sheet_id) {
				$max_rows = (int) $sheet->properties->gridProperties->rowCount;
			}
		}
		
		// determine ranges to pull from (columns C and AM, all rows, for sheet with sheetId: 2067083601)
		$ranges = [];
		foreach($col_array as $col_character) {
			$ranges[] = "$sheet_name!$col_character" . "1:$col_character$max_rows";
		}
		$req_url = 'https://sheets.googleapis.com/v4/spreadsheets/' . $doc_id . "/values:batchGet?";
		foreach($ranges as $range_param) {
			$req_url .= "ranges=$range_param&";
		}
		$req_url = substr($req_url, 0, strlen($req_url) - 1);
		
		$res = $client->request('GET', $req_url);
		$data = json_decode($res->getBody()->getContents());
		
		// we will refer to range objects for cell values
		$columns = [];
		foreach($data->valueRanges as $valueRange) {
			foreach($col_array as $col_character) {
				if (strpos($valueRange->range, $col_character . "1:") !== false) {
					$columns[$col_character] = $valueRange;
				}
			}
		}
		return $columns;
	}
	
	public function countCompletedInterviews($record_id, $start_time, $end_time) {
		$start_timestamp = strtotime($start_time);
		$end_timestamp = strtotime($end_time);
		$completed_interviews = 0;
		$total_interviews = 0;
		
		// fetch interviews
		$result = $this->module->queryLogs("SELECT interview, scheduled_datetime WHERE message='catmh_interview' AND subjectid = ?", [$record_id]);
		
		while ($row = db_fetch_assoc($result)) {
			$interview = json_decode($row['interview']);
			$interview_timestamp = strtotime($row['scheduled_datetime']);
			if ($start_timestamp <= $interview_timestamp && $interview_timestamp <= $end_timestamp) {
				// carl_log("scheduled_datetime: " . $row['scheduled_datetime']);
				// carl_log("subjectid: " . $row['subjectid']);
				// carl_log("interview: " . print_r($row['interview'], true));
				// carl_log("timestamp for scheduled_datetime: " . strtotime($row['scheduled_datetime']));
				$total_interviews++;
				if ($interview->status == 4) {
					$completed_interviews++;
				}
			}
		}
		// carl_log("completed / total from inside the function: " . $completed_interviews . " / " . $total_interviews);
		return [$completed_interviews, $total_interviews];
	}
	
}

$automation_instance = new Automation($this);
$automation_instance->determineLocality();
$automation_instance->performAutomation();

// $days_ago_8 = strtotime(date("Y-m-d")) - (24 * 60 * 60) * 8;
// $days_ago_1 = strtotime(date("Y-m-d")) - (24 * 60 * 60) * 1;
// $stats->dates = new stdClass();
// $stats->dates->begin = date("n/j/y", $days_ago_8);
// $stats->dates->end = date("n/j/y", $days_ago_1);
// carl_log("\$stats->dates->begin: " . $stats->dates->begin);
// carl_log("\$stats->dates->end: " . $stats->dates->end);
// carl_log("\$stats->dates->begin timestamp: " . strtotime($stats->dates->begin));
// carl_log("\$stats->dates->end timestamp: " . strtotime($stats->dates->end));
// list($completed, $total) = $automation_instance->countCompletedInterviews("AVK5021", $stats->dates->begin, $stats->dates->end);
// carl_log("completed: $completed, total: $total");
