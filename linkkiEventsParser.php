<?php

# URLs
// Proper URL format after appends: http://linkkijkl.fi/events/?ical=1&tribe-bar-date=2017-1
$LINKKI_THIS_MONTH_EVENTS_URL = "http://linkkijkl.fi/events/?ical=1&tribe-bar-date=";
$LINKKI_NEXT_MONTH_EVENTS_URL = "http://linkkijkl.fi/events/?ical=1&tribe-bar-date=";

// Set the default timezone to use.
date_default_timezone_set('Europe/Helsinki');

// Get the year for appending the URL
$year      = date("Y");

// Get the month for appending the URL to get this and next month's events.
$month     = date("n");
$nextMonth = $month + 1;

// Set the months to the Linkki's URLs
$LINKKI_THIS_MONTH_EVENTS_URL .= $year . "-" . $month;
$LINKKI_NEXT_MONTH_EVENTS_URL .= $year . "-" . $nextMonth;

// Get Linkki's this & next months contents to linkkiRawEventData.txt file
$content = file_get_contents($LINKKI_THIS_MONTH_EVENTS_URL);
$content .= file_get_contents($LINKKI_NEXT_MONTH_EVENTS_URL);


// File path to directory "Jyunioni-server". 
// Run command "pwd" when in "Jyunioni-server" directory and put the result in $homeDirPath
$homeDirPath = "/Users/JaniS/Sites/Jyunioni-server";

// The current file's path
$filePath = "/Raw-event-data/linkkiRawEventData.txt";


// Write the contents back to a .txt file
if (file_put_contents($homeDirPath . $filePath, $content) !== false) {
    echo "Linkki's raw events data written succesfully to: " . $filePath . "\n";
}


// Read the raw data file line by line and parse the different Event fields.
extractEventsData($homeDirPath . $filePath, $homeDirPath);


function extractEventsData($file, $homeDirPath)
{
    
    // Variables for the event's extracting
    // Event's count in a file
    $eventsCount = 0;
    // Event's beginning symbol
    $eventBegin  = "BEGIN:VEVENT";
    
    // Create the Event object variables
    // Event imageId and groupColorId will be added locally in the phone
    $eventTimeStart = "";
    $eventTimeEnd   = "";
    $eventTimestamp = "";
    
    $eventName        = "";
    $eventInformation = "";
    $eventUrl         = "";
    
    // Create the array for the extracted event data
    $extractedEventsData = "LINKKI\n";
    
    
    // Count the amount of Events in the file. Open the file for reading only.
    $handle = @fopen("$file", "r");
    if ($handle) {
        
        while (($buffer = fgets($handle)) !== false) {
            
            // If the line contains "BEGIN:VEVENT"
            if (strpos($buffer, $eventBegin) !== false) {
                $eventsCount++;
            }
        }
        
        if (!feof($handle)) {
            echo "Error: unexpected fgets() fail.<br>";
        }
        
        fclose($handle);
    }
    
    $count = -1;
    
    // Open the file for reading only. Get the different event values from the file
    $handle = @fopen("$file", "r");
    if ($handle) {
        
        // Keep reading until end of line and check if there are Events in the file. Also check that the for -loop hasn't looped as many times as there
        // are events in the file.
        while (($buffer = fgets($handle)) !== false && $eventsCount !== 0) {
            
            // Set maximum execution time so in case of an erronous run it doesn't loop 30 seconds (default)
            ini_set('max_execution_time', 1);
            
            // Loop through all the separate event's in the file.
            // Find Event fields in order: startTime, endTime, eventName, eventInformation, eventUrl
            for ($i = 0; $i < $eventsCount; $i++) {
                
                // If the calendar ends
                if (strpos($buffer, "END:VCALENDAR") !== false)
                    break;
                
                while (true) {
                    if (strpos($buffer, "DTSTART;") !== false) {
                        // Parse event starting time
                        $eventTimeStart = extractTime($buffer);
                        
                        while (true) {
                            if (strpos($buffer, "DTEND;") !== false) {
                                // Parse event ending time
                                $eventTimeEnd   = extractTime($buffer);
                                $eventTimestamp = extractTimestamp($eventTimeStart, $eventTimeEnd);
                                
                                while (true) {
                                    if (strpos($buffer, "SUMMARY:") !== false) {
                                        $eventName = extractField($buffer);
                                        
                                        while (true) {
                                            if (strpos($buffer, "DESCRIPTION:") !== false) {
                                                $eventInformation = extractInformation($buffer);
                                                
                                                // Event's URL
                                                while (true) {
                                                    if (strpos($buffer, "URL") !== false) {
                                                        $eventUrl = extractField($buffer);
                                                        
                                                        // Event's information ends on the next line so hop to it and break the for loop back to the while loop.
                                                        $buffer = fgets($handle);
                                                        $count++;
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
                
                $extractedEventsData .= "\n" . 
                    "eventName: " . $eventName . "\n" . 
                    "eventTimestamp: " . $eventTimestamp . "\n" . 
                    "eventUrl: " . $eventUrl . "\n" . 
                    "eventInformation: " . $eventInformation . "\n\n" . 
                    "END_OF_EVENT" . "\n\n";
                
                break;
            }
        }
        
        // In case of an error with the fgets() method
        if (!feof($handle)) {
            echo "Error: unexpected fgets() fail.<br>";
        }
        
        // Close the handle from taking resources
        fclose($handle);


		// The current file's path
		$filePath = "/Parsed-events/linkkiEvents.txt";

        $eventDataFile = $homeDirPath . $filePath;
        
        // Write the results into a .txt file.
        if (file_put_contents($eventDataFile, $extractedEventsData) !== false) {
            echo "Linkki's events data written succesfully to: " . $eventDataFile . "\n";
        }
        
    }
    
}


// Extract content after ':' char
function extractField($line)
{
    // Find the position of ':', take a substring after it to the end of line and trim whitespace.
    $result = trim(substr($line, strpos($line, ":") + 1));
    
    return $result;
}


// Extract timestamps
function extractTime($line)
{
    
    $date   = "";
    $time   = "";
    $result = "";
    
    $line = extractField($line);
    // Line is now this format: "20170723T170000" or "20170829"
    
    // If the line contains the date and the hours, for example: "20170723T170000"
    if (strpos($line, "T") !== false) {
        $date = strtok($line, "T");
        $time = substr($line, strpos($line, "T") + 1, 4);
        
        $result = $date . " " . $time;
        // echo $result = "20170723 1700"
        
        $result = date("j.n. G:i", strtotime($result));
        // echo $result = "23.7. 17:00"
        
        return $result;
    }
    
    // If the line is without the 'T', it contains only the date. For example: "20170829"
    $result = date("j.n.", strtotime($line));
    // echo $result = "29.8."
    
    return $result;
}



// Extract the event's timestamp
// Check if the event happens only on one day. If, then use the date on the startTime only.
//
// Example input: "25.9. 17:00", "25.9. 23:00"
// Example output: "25.9. 17:00 - 23:00"
function extractTimestamp($startTime, $endTime)
{
    $result = $startTime . " - " . $endTime;
    
    $startDay = substr($startTime, 0, strpos($startTime, "."));
    $endDay   = substr($endTime, 0, strpos($endTime, "."));
    
    // Check if it's only one day event.
    if (strcmp($startDay, $endDay) === 0) {
        
        // Check if the event has starting or ending hours. Then return one date with hours.
        if (strpos($startTime, ":") !== false || strpos($endTime, ":") !== false) {
            // echo $startTime . " - " . $endTime = "25.9. 17:00 - 25.9. 23:00"
            
            $result = $startTime . " - " . substr($endTime, strrpos($endTime, " "));
            // echo $result = "25.9. 17:00 - 23:00"
        }
    }
    
    return $result;
}


// Extracts the event information field
function extractInformation($information)
{
    $result = substr($information, strpos($information, ":") + 1);
    // Replace '\n' and '\' char's in the information. Delete whitespace.
    $result = str_replace("\\n", "\n", $result);
    $result = trim(str_replace("\\", "", $result));
    
    return $result;
}


?>