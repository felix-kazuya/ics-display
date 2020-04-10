<?php
// Load dependencies
require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/config.php";

if ($timezone_override !== false) {
	date_default_timezone_set($timezone_override);
}

// Create empty array for all relevant events
$result_array = array();
$failed = false;
$item_count = 0;


// Add both assignments and exams to the array
foreach ($calendar_urls as $name => $url) {
	$this_ical = new \ICal\ICal($url);

	// Skip calendar if it cannot load
	if ($this_ical === false) {
		$failed = true;
		continue;
	}

	

	// Get events within date range
	$start_date = $days_back == -1 ? false : "-{$days_back} days";
	$end_date = $days_forward == -1 ? false : "+{$days_forward} days";
	$events = $this_ical->eventsFromRange($start_date, $end_date);

	
	foreach ($events as $event) {
		$targetDate = date("l d.m.Y", strtotime($event->dtstart)); 
		if (empty($result_array[$target])) {
			$result_array[$targetDate] = array();
		}

		array_push($result_array[$targetDate], array(
			"time" => date("H:i", strtotime($event->dtstart)),
			"date" => date("l d.m.Y", strtotime($event->dtstart)),
            "etime" => date("H:i", strtotime($event->dtend)),
            "edate" => date("l, F j", strtotime($event->dtend)),
			"timestamp" => $event->dtstart,
			"desc" => stripslashes($event->description),
			"title" => $startDate. "bis" . $endDate . " " . stripslashes($event->summary),
			"calendar" => $name
		));

		$item_count++;
	}
}
ksort($result_array);
echo "<pre>";
var_dump($result_array);
exit(0);

// Sort the remaining array of events by key to put dates in ascending order
ksort($result_array);






