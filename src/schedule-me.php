<?php

// Load dependencies
require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/config.php";

if ($timezone_override !== false) {
    date_default_timezone_set($timezone_override);
}

// Create empty array for all relevant events
$result_array = [];
$failed = false;

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
    $end_date   = $days_forward == -1 ? false : "+{$days_forward} days";
    $events     = $this_ical->eventsFromRange($start_date, $end_date);

    foreach ($events as $event) {

        // fetch necessary information from event
        $TSStart    = strtotime($event->dtstart);
        $TSEnd      = strtotime($event->dtend);
        $TSStartDay = mktime(0, 0, 0, date('m', $TSStart), date('d', $TSStart), date('Y', $TSStart));
        $TSEndDay   = mktime(0, 0, 0, date('m', $TSEnd),   date('d', $TSEnd),   date('Y', $TSEnd));
        $endDate    = date("l d.m.Y", $TSEnd);
        $globStart  = date("l d.m.Y", $TSStart);

        $startTime  = date("H:i", $TSStart);

        $loopCount  = 0;
        // put event on to every day as target where it takes part
        while ( $TSStartDay <= $TSEndDay ) {

            $targetDate = date("Ymd", $TSStartDay);
            $startDate  = date("l d.m.Y", $TSStartDay);

            // create empty array for target date if it does not exist
            if (empty($result_array[$targetDate])) {
                $result_array[$targetDate] = [];
            }

            // set $startTime to start of current target day since event already
            // takes part at least on two days
            if ( $loopCount >= 0 ) {
                $startTime = "00:00";
            }
            else {
                $startTime = date("H:i", $TSStartDay);
            }

            // set $endTime to start of the following day after target day since
            // event takes part on at least the next day, too
            if ( $TSStartDay < $TSEndDay ) {
                $endTime = "00:00";
            }
            // last target day reached – set correct end time
            else {
                $endTime = date("H:i", $TSEnd);
            }

            // put every information needed into the $result_array by $targetDate
            $result_array[$targetDate][] = [
    			"time"       =>  $startTime,
    			"date"       =>  $startDate,
    			"etime"      =>  $endTime,
    			"edate"      =>  $endDate,
    			"timestamp"  =>  date("Y-m-d H:i", $TSStartDay),
    			"desc"       =>  stripslashes($event->description),
    			"title"      =>  $globStart . " bis " . $endDate . " | " . stripslashes($event->summary),
    			"calendar"   =>  $name,
            ];

            $TSStartDay += 86400; // 24h * 60min * 60s
            $loopCount++;
        }
    }
}

ksort($result_array);

// forget all days that are no more relevant due to time past and config
$oldest = date('Ymd', strtotime("-$days_back days"));
$newest = date('Ymd', strtotime("+$days_forward days"));
foreach ($result_array as $target => $events) {
    if ( $target > $newest OR $target < $oldest ) {
        unset($result_array[$target]);
    }
}


// Display an error if we failed to read a calendar
if ($failed && $show_ical_errors) {
    echo "<div class='day'>\n\t<div class='heading error'>Error</div>\n\t<div class='events'>\n" .
         "\t\t<div class='events-inner'>Unable to read one or more calendars.</div>\n" .
         "\t</div>\n</div>\n";
}

$item_count = 0;
$stop = false;

// Cycle through each date and display it
foreach ($result_array as $events) {
    // If we've displayed the maximum number of events, stop.
    if ($stop) {
        break;
    }


    // Display the container for the date
    echo "<div class='day'>\n" .
         "\t<div class='heading'>". $events[0]['date'] ."</div>\n" .
         "\t<div class='events'>\n";

    // Loop through each event on this date
    foreach ($events as $event) {
        // If we've displayed the maximum number of events, stop.
        if ($stop) {
            break;
        }


        // If we have enough items, complete this iteration and stop.
        $item_count++;
        if ($number_of_events != -1 && $item_count == $number_of_events) {
            $stop = true;
        }

        $class = ""; // Default the extra classes on the event to none.

        // Highlight designated calendars, those with the appropriate keyword, and today's events, if configured.
        if (
            (!empty($highlight_calendars) && in_array($event['calendar'], $highlight_calendars)) ||
            ($highlight_today && strtotime($event['timestamp']) < strtotime("tomorrow") &&
            strtotime($event['timestamp']) >= strtotime("today")) ||
            (!empty($highlight_keyword) && strpos($event["desc"], $highlight_keyword) !== false)
        ) {
            $class .= " highlight";
        }

        // Darken designated calendars, those with the appropriate keyword, and past events, if configured.
        if (
            (!empty($darken_calendars) && in_array($event['calendar'], $darken_calendars)) ||
            ($darken_past && strtotime($event['timestamp']) < strtotime("now")) ||
            (!empty($darken_keyword) && strpos($event["desc"], $darken_keyword) !== false)
        ) {
            $class .= " darken";
        }

        // Display the event
        $eventtime = "whole day";
        if ($event['time'] != $event['etime']) {
            $eventtime = $event['time'] ." - " . $event['etime'];
        }

        echo "\t\t<div class='events-inner $class'>\n" .
             "\t\t\t<div class='time'>" . $eventtime . "</div>\n" .
             "\t\t\t<div class='event'>" . $event['title'] . "</div>\n" .
             "\t\t</div>\n";
    }

    echo "\t</div>\n</div>\n";
}

