<?php

# URL
$DUMPPI_EVENTS_URL = "https://dumppi.fi/tapahtumat/";

// Set the default timezone to use.
date_default_timezone_set('Europe/Helsinki');


// Get Dumppi's "css-events-list" HTML div's URL list to a dumppiRawUrlData.json file
fetchUrls($DUMPPI_EVENTS_URL);

// Using the list of URL's, get each event's data from it's own event page and parse the event attributes.
$dumppiUrlsJson = file_get_contents("/wwwhome/home/jatasuor/html/Jyunioni-server/Raw-event-data/dumppiRawUrlData.json");

// Decode the .json into an array
$dumppiEventUrls = json_decode($dumppiUrlsJson, true);


// Get the raw event data into dumppiRawEventData.txt and extract the event details into dumppiEvents.txt
getRawEventData($dumppiEventUrls);


// Fetch each event's raw data from the event's page
function getRawEventData($urls)
{
    // All different events data will be put into this string to be put into dumppiRawEventData.txt
    # $eventPageContents = "";
    
    // A temporary variable for single events raw data
    $tmp = "";
    
    // The parsed event details will be put into this string
    $eventDetails = "DUMPPI\n";
    
    
    foreach ($urls as $url) {
        
        // Get the content of the site
        $html = file_get_contents($url);
        
        // Create a new DOMDocument instance
        $dom = new DOMDocument();
        
        // Surpress errors with the '@' and load the String containing the page's HTML to the DOMDocument object
        @$dom->loadHTML($html);
        
        // More about the xpath query usage: http://www.the-art-of-web.com/php/html-xpath-query/
        $xpath         = new DOMXpath($dom);
        $xpath_results = $xpath->query('//div[@class="uutinen"]');
        // $contents is an instance of DOMNodeList
        
        // Put the HTML from the page into this single events raw data string
        $tmp = $dom->saveHTML($xpath_results->item(0));
        
        # Then add it to the all raw data string
        # $eventPageContents .= $tmp;
        
        // Parse the event details from the .txt
        $eventDetails .= extractDumppiEventDetails($tmp, $url);
    }
     
    
    /*
    # Get all events raw data into a file.
    $dumppiRawEventData = "/Users/JaniS/Sites/Jyunioni server/Raw event data/dumppiRawEventData.txt";
    
    // Write the raw data results into dumppiRawEventData.txt file.
    if (file_put_contents($dumppiRawEventData, $eventPageContents) !== false) {
    echo "<br><b><i>Dumppi's raw events data written succesfully to: </i></b>" . $dumppiRawEventData . "<br>";
    }
    */
    
    
    $dumppiEvents = "/wwwhome/home/jatasuor/html/Jyunioni-server/Parsed-events/dumppiEvents.txt";
    
    // Write the parsed events into dumppiEvents.txt file.
    if (file_put_contents($dumppiEvents, $eventDetails) !== false) {
        echo "Dumppi's parsed events data written succesfully to: " . $dumppiEvents . "\n";
    }
    
}


function extractDumppiEventDetails($rawInformation, $eventUrl)
{
    // Different event attributes, url is given as a parameter
    $eventName             = "";
    $startDate             = "";
    $endDate               = "";
    $eventTimestamp        = "";
    $eventInformation      = "";
    // How many lines of event information is shown
    $eventInformationLines = 7;
    
    // Skim through rawInformation line by line
    // Idea from: https://stackoverflow.com/questions/1462720/iterate-over-each-line-in-a-string-in-php
    $newline = "\r\n";
    $line    = strtok($rawInformation, $newline);
    
    while ($line !== false) {
        // Get the string until newline character
        $line = strtok($newline);
        
        while (true) {
            if (strpos($line, "<h2 class=\"post-title\"><a href=\"") !== false) {
                $eventName = extractName($line);
                
                while (true) {
                    if (strpos($line, "<strong>Päiväys ja aika</strong><br>") !== false) {
                        $line           = strtok($newline);
                        $eventTimestamp = extractTimestamp($line);
                        
                        while (true) {
                            if (strpos($line, "<br style=\"clear:both\">") !== false) {
                                // Skip to the first line of the <p> element where the event information is.
                                // $line = strtok($newline);
                                
                                // Limit the amount of text in the event information to $eventInformationLines of <p> elements.
                                for ($j = 0; $j < $eventInformationLines; $j++) {
                                    $line = strtok($newline);
                                    
                                    // Skip the line if it contains "jkjsak"
                                    if (strpos($line, "jkjsak") !== false) {
                                        $line = strtok($newline);
                                    }
                                    
                                    // If the line contains "<ul>", step one line forward
                                    if (strpos($line, "<ul>") !== false) {
                                        $line = strtok($newline);
                                    }
                                    
                                    // If the line contains "<li>", step one line forward
                                    if (strpos($line, "<li>") !== false) {
                                        $line = strtok($newline);
                                    }
                                    
                                    $eventInformation .= $line . "\n";
                                }
                                
                                $eventInformation = extractEventInformation($eventInformation);
                                break;
                            }
                            $line = strtok($newline);
                        }
                        break;
                    }
                    $line = strtok($newline);
                }
                break;
            }
            $line = strtok($newline);
        }
        break;
    }

    // Return what will be written into the dumppiEvents.txt
    return "\n" . 
        "eventName: " . $eventName . "\n" . 
        "eventTimestamp: " . $eventTimestamp . "\n" . 
        "eventUrl: " . $eventUrl . "\n" . 
        "eventInformation: " . $eventInformation . "\n\n" . 
        "END_OF_EVENT" . "\n\n";
}


function extractName($line)
{
    // Strip any html elements. Also decode any html chars like &amp;'s (&'s) etc..
    return trim(htmlspecialchars_decode((strip_tags($line))));
}


function extractTimestamp($line)
{
    // Input example:
    // <p> <strong>Päiväys ja aika</strong><br> 25.10.2017<br><i>00:00</i><br> <a href="http://dumppi.fi/events/it-tiedekunnan-vaihtoinfoilta/ical/">iCal</a> </p>
    $startDate = "";
    $endDate   = "";
    
    $line = substr($line, strrpos($line, "</strong><br>"));
    $line = substr($line, 0, strpos($line, "</i><br>"));
    // echo htmlspecialchars($line) = "25.10.2017<br><i>00:00"
    
    // Split the string into a date and a time
    $date = substr($line, 0, strpos($line, "<br><i>"));
    $time = substr($line, strrpos($line, "<br><i>") + 7);
    
    // Check if it's a 2 day event, then format $startDate and $endDate. 
    // Else it's one day event.
    if (strpos($date, " - ") !== false) {
        // Substring the startDate and endDate
        $startDate = substr($date, 0, strpos($date, " -"));
        $endDate   = substr($date, strrpos($date, " ") + 1);
        
        // Format the dates getting leading 0's and the year off 
        $startDate = str_replace(".", "-", $startDate);
        $startDate = date("j.n.", strtotime($startDate));
        
        $endDate = str_replace(".", "-", $endDate);
        $endDate = date("j.n.", strtotime($endDate));
        
        $date = $startDate . " - " . $endDate;
    } else {
        // Format the date getting leading 0's and the year off
        $date = str_replace(".", "-", $date);
        $date = date("j.n.", strtotime($date));
    }
    
    // If time contains letters, then return just the date
    if (preg_match("/[a-z]/i", $time)) {
        return $date;
    }
    
    // If there's no time, return date only
    if (strpos($time, ":") === false) {
        return $date;
    }
    
    // Check if it's a 2 day event with hours, then make the timestamp accordingly
    if (strpos($date, " - ") !== false) {
        // Substring the hours for the startHours and endHours. Trim them at the same time.
        $startHours = trim(substr($time, 0, strpos($time, " -")));
        $endHours   = substr($time, strrpos($time, " ") + 1);
        
        return $startDate . " " . $startHours . " - " . $endDate . " " . $endHours;
    }
    
    return $date . " " . $time;
}


function extractEventInformation($eventInformation)
{
    // Replace all HTML linebreaks ("<br>")'s with a newline "\n"
    $eventInformation = str_replace("<br>", "\n", $eventInformation);
    
    // Replace all html tags
    $eventInformation = trim(strip_tags($eventInformation));
    
    // Decode any html chars like &amp;'s (&'s) etc..
    $eventInformation = htmlspecialchars_decode($eventInformation);
    
    return $eventInformation;
}


function fetchUrls($url)
{
    // Get the contents of the site
    $html = file_get_contents($url);
    
    // Create a new DOMDocument instance
    $dom = new DOMDocument();
    
    // Surpress errors with the '@' and load the String containing the pages HTML to the DOMDocument object
    @$dom->loadHTML($html);
    
    
    // More about the xpath query usage: http://www.the-art-of-web.com/php/html-xpath-query/
    $xpath    = new DOMXpath($dom);
    $articles = $xpath->query('.//div[@class="css-events-list"]');
    // $articles is an instance of DOMNodeList
    
    // Get all links in div with id "css-events-list". Create an array instance.
    $links = array();
    
    foreach ($articles as $container) {
        $arr = $container->getElementsByTagName("a");
        
        // Put the elements content (the URL's) from href fields to an array
        foreach ($arr as $item) {
            $href = $item->getAttribute("href");
            
            // Add each link to the array
            array_push($links, $href);
        }
    }
    
    // Delete the first URL which is the "https://dumppi.fi/ilmoittautumisen-pelisaannot/"
    if (strcmp(array_pop(array_reverse($links)), "https://dumppi.fi/ilmoittautumisen-pelisaannot/") === 0) {
        // Delete the link to the second page from the links list.
        array_shift($links);
    }
    
    $dumppiRawUrlDataJson = "/wwwhome/home/jatasuor/html/Jyunioni-server/Raw-event-data/dumppiRawUrlData.json";
    
    // Write the array of links into the .json file
    $fp = fopen($dumppiRawUrlDataJson, "w");
    if (fwrite($fp, json_encode($links, JSON_PRETTY_PRINT)) !== false) {
        echo "Dumppi's URLs written succesfully to: " . $dumppiRawUrlDataJson . "\n";
    }
    fclose($fp);
}


?>