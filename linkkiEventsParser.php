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
echo "Following files written succesfully:<br>";

if (file_put_contents($thisMonthFile, $thisContent) !== FALSE){
	echo $thisMonthFile . "<br>";
}

if (file_put_contents($nextMonthFile, $nextContent) !== FALSE){
	echo $nextMonthFile . "<br>";
}






# Read the text file line by line and parse the different Event fields.

# Event's count in a file
$eventsCount = 0;
# Event's beginning symbol
$eventBegin = "BEGIN:VEVENT";

# Count the amount of Events in the file. Open the file for reading only.
$handle = @fopen("$thisMonthFile", "r");
if ($handle) {

    while (($buffer = fgets($handle)) !== FALSE) {

    	// If the line contains "BEGIN:VEVENT"
        if (strpos($buffer, $eventBegin) !== FALSE){
        	$eventsCount++;
        }
    }

    if (!feof($handle)) {
        echo "Error: unexpected fgets() fail.<br>";
    }

    fclose($handle);
}



# Open the file for reading only. Get the different variable values from the file
$handle = @fopen("$thisMonthFile", "r");
if ($handle) {

	// Create the Event object variables
	// Event imageId and groupColorId will be added locally in the phone
	$eventTimeStart = "";
    $eventTimeEnd = "";
	$eventTimestamp = "";

	$eventName = "";
	$eventInformation = "";
	$eventUrl = "";


	// Keep reading until end of line and check if there are Events in the file.
    while (($buffer = fgets($handle)) !== FALSE && $eventsCount !== 0) {


    	for ($i = 0; $i < $eventsCount; $i++){

    		if (strpos($buffer, "DTSTART;") !== FALSE){
    			$eventTimeStart = $buffer;
    			echo $eventTimeStart . "<br>";
    		}


    	}



        echo $buffer . "<br>";
    }


    // In case of an error with the fgets() method
    if (!feof($handle)) {
        echo "Error: unexpected fgets() fail.<br>";
    }

    // Close the handle from taking resources
    fclose($handle);
}













?>








