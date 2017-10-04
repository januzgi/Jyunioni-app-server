<?php

# URLs for different group's events
$LINKKI_THIS_MONTH_EVENTS_URL = "http://linkkijkl.fi/events/?ical=1&tribe-bar-date=2017-";
$LINKKI_NEXT_MONTH_EVENTS_URL = "http://linkkijkl.fi/events/?ical=1&tribe-bar-date=2017-";
$PORSSI_EVENTS_URL = "http://www.porssiry.fi/tapahtumat/";
$DUMPPI_EVENTS_URL = "http://dumppi.fi/tapahtumat/";
$STIMULUS_EVENTS_URL = "http://www.stimulus.fi/ilmoittautuminen.php";


# Get the month for appending LINKKI_EVENTS_URL to get this and next month's events.
// Set the default timezone to use.
date_default_timezone_set('Europe/Helsinki');

$month = date('n');
$nextMonth = $month + 1;

// Set the months to the Linkki's URLs
$LINKKI_THIS_MONTH_EVENTS_URL .= $month;
$LINKKI_NEXT_MONTH_EVENTS_URL .= $nextMonth;


// Get Linkki's this & next months contents to raw data .txt file
$thisContent = file_get_contents($LINKKI_THIS_MONTH_EVENTS_URL);
$nextContent = file_get_contents($LINKKI_NEXT_MONTH_EVENTS_URL);

$thisMonthFile = '/Users/JaniS/Sites/Jyunioni server/linkkiThisMonthRawEventData.txt';
$nextMonthFile = '/Users/JaniS/Sites/Jyunioni server/linkkiNextMonthRawEventData.txt';


// Write the contents back to a .txt file
echo "Following files written succesfully:<br><br>";

if (file_put_contents($thisMonthFile, $thisContent) !== false){
	echo $thisMonthFile . "<br>";
}

if (file_put_contents($nextMonthFile, $nextContent) !== false){
	echo $nextMonthFile . "<br><br>";
}

echo "################### ################### ###################" . "<br>" . "Output:" . "<br>" . "################### ################### ###################" . "<br><br>";



# Read the text file line by line and parse the different Event fields.
extractEventsData($thisMonthFile);
extractEventsData($nextMonthFile);


// Variables for the event's extracting
# Event's count in a file
$eventsCount = 0;
# Event's beginning symbol
$eventBegin = "BEGIN:VEVENT";

	// Create the Event object variables
	// Event imageId and groupColorId will be added locally in the phone
	$eventTimeStart = "";
    $eventTimeEnd = "";
	$eventTimestamp = "";

	$eventName = "";
	$eventInformation = "";
	$eventUrl = "";

	// Create the array for the extracted event data
	$extractedEventsData = array();


function extractEventsData($file){

# Count the amount of Events in the file. Open the file for reading only.
$handle = @fopen("$file", "r");
if ($handle) {

    while (($buffer = fgets($handle)) !== false) {

    	// If the line contains "BEGIN:VEVENT"
        if (strpos($buffer, $eventBegin) !== false){
        	$eventsCount++;
        }
    }

    if (!feof($handle)) {
        echo "Error: unexpected fgets() fail.<br>";
    }

    fclose($handle);
}


# Open the file for reading only. Get the different event values from the file
$handle = @fopen("$file", "r");
if ($handle) {

	// Keep reading until end of line and check if there are Events in the file. Also check that the for -loop hasn't looped as many times as there
	// are events in the file.
    while (($buffer = fgets($handle)) !== false && $eventsCount !== 0) {

    	// Set maximum execution time so in case of an erronous run it doesn't loop 30 seconds (default)
   		ini_set('max_execution_time', 1);

    	// Loop through all the separate event's in the file.
    	// Find Event fields in order: startTime, endTime, eventName, eventInformation, eventUrl
    	for ($i = 0; $i < $eventsCount; $i++){

    		// If the calendar ends
			if (strpos($buffer, "END:VCALENDAR") !== false) break;

    		while (true) {
    			if (strpos($buffer, "DTSTART;") !== false){
    				// Parse event starting time
    				$eventTimeStart = $buffer; 
    				// echo $eventTimeStart . "<br>";

    				while (true) {
    					if (strpos($buffer, "DTEND;") !== false){
    						// Parse event ending time
    						$eventTimeEnd = $buffer;
    						// echo $eventTimeEnd . "<br>";
    						
    						while (true) {
    							if (strpos($buffer, "SUMMARY:") !== false){
    								$eventName = $buffer;
    								// echo $eventName . "<br>";

    								while (true) {
    									if (strpos($buffer, "DESCRIPTION:") !== false){
    										$eventInformation = $buffer;
    										// echo $eventInformation . "<br>";

    										// Event's URL
    										while (true) {
    											if (strpos($buffer, "URL") !== false){
    												$eventUrl = $buffer;
    												// echo $eventUrl . "<br><br><br>";

    												// Event information ends on the next line so hop to it and break the for loop back to the while loop.
    												$buffer = fgets($handle);
    												break;

    											}
    											$buffer = fgets($handle);

    										}
    										break; 

    									}
    									$buffer = fgets($handle);
    								}
    								break;

    							}
    							$buffer = fgets($handle);
    						}
    						break;

    					}
    					$buffer = fgets($handle);
    				}
    				break;

    			}
    			$buffer = fgets($handle);
    		}

    		// Add the event's data to the list


    		echo "<br>" . $eventTimeStart . "<br>" . $eventTimeEnd . "<br>" . $eventName . "<br>" . $eventInformation . "<br>" . $eventUrl . "<br>";
    		break;
    	}
    }


    // In case of an error with the fgets() method
    if (!feof($handle)) {
        echo "Error: unexpected fgets() fail.<br>";
    }

    // Close the handle from taking resources
    fclose($handle);


    // return or do something with the results.
}


}



?>








