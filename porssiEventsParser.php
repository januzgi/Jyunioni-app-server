<?php

# URL
$PORSSI_EVENTS_URL = "http://www.porssiry.fi/tapahtumat/";

// Set the default timezone to use.
date_default_timezone_set('Europe/Helsinki');


// Get Porssi's "css-events-list" HTML div's URL list to a porssiRawUrlData.json file
// The event's page list in http://www.porssiry.fi/tapahtumat/ goes to page 2 after 20 events.
// So only fetch the 20 first and not the url "http://www.porssiry.fi/tapahtumat/?pno=2"
fetchUrls($PORSSI_EVENTS_URL);


// Using the list of URL's for each event, get each event's data from it's own event page and create the .json with event attributes.
$porssiUrlsJson = file_get_contents("/Users/JaniS/Sites/Jyunioni server/Raw event data/porssiRawUrlData.json");

// Decode the .json into an array
$porssiEventUrls = json_decode($porssiUrlsJson, true);

// Get the raw event data into porssiRawEventData.txt
getRawEventData($porssiEventUrls);


// Fetch each event's raw data from the event's page
function getRawEventData($urls)
{
    // All different events data will be put into this string to be put into porssiRawEventsData.txt
    # $eventPageContents = "";
    
    // A temporary variable for single events raw data
    $tmp = "";
    
    // The parsed event details will be put into this string
    $eventDetails = "PORSSI\n";
    
    
    foreach ($urls as $url) {
        
        // Get the content of the site
        $html = file_get_contents($url);
        
        // Create a new DOMDocument instance
        $dom = new DOMDocument();
        
        // Surpress errors with the '@' and load the String containing the page's HTML to the DOMDocument object
        @$dom->loadHTML($html);
        
        // More about the xpath query usage: http://www.the-art-of-web.com/php/html-xpath-query/
        $xpath         = new DOMXpath($dom);
        $xpath_results = $xpath->query('//div[@id="content"]');
        // $contents is an instance of DOMNodeList
        
        // Put the HTML from the page into this single events raw data string
        $tmp = $dom->saveHTML($xpath_results->item(0));
        
        # Then add it to the all raw data string
        # $eventPageContents .= $tmp;
        
        // Parse the event details from the .txt
        $eventDetails .= extractPorssiEventDetails($tmp, $url);
    }
    
    /*
    # Write all events raw data into a file
    $porssiRawEventData = "/Users/JaniS/Sites/Jyunioni server/Raw event data/porssiRawEventData.txt";
    
    // Write the raw data results into porssiRawEventData.txt file.
    if (file_put_contents($porssiRawEventData, $eventPageContents) !== false) {
    echo "<br><b><i>Pörssi's raw events data written succesfully to: </i></b>" . $porssiRawEventData . "<br>";
    }
    */
    
    
    $porssiEvents = "/Users/JaniS/Sites/Jyunioni server/Parsed events/porssiEvents.txt";
    
    // Write the parsed events into porssiEvents.txt file.
    if (file_put_contents($porssiEvents, $eventDetails) !== false) {
        echo "<br><b><i>Pörssi's parsed events data written succesfully to: </i></b>" . $porssiEvents . "<br>";
    }
}


function extractPorssiEventDetails($rawInformation, $eventUrl)
{
    // Different event attributes, url is given as a parameter
    $eventName             = "";
    $startDate             = "";
    $endDate               = "";
    $eventTimestamp        = "";
    $eventInformation      = "";
    // How many lines of event information is shown
    $eventInformationLines = 5;
    
    // Skim through rawInformation line by line
    // Idea from: https://stackoverflow.com/questions/1462720/iterate-over-each-line-in-a-string-in-php
    $newline = "\r\n";
    $line    = strtok($rawInformation, $newline);
    
    while ($line !== false) {
        // Get the string until newline character
        $line = strtok($newline);
        
        while (true) {
            if (strpos($line, "<h1>") !== false) {
                $eventName = extractName($line);
                
                while (true) {
                    if (strpos($line, "dashicons dashicons-calendar-alt") !== false) {
                        // Go to the next line
                        $line      = strtok($newline);
                        $startDate = extractDate($line);
                        
                        // Go two lines onwards, endDate can be empty.
                        $line    = strtok($newline);
                        $line    = strtok($newline);
                        $endDate = extractDate($line);
                        
                        $eventTimestamp = extractTimestamp($startDate, $endDate);
                        
                        while (true) {
                            if (strpos($line, "dashicons dashicons-clock") !== false) {
                                // Go to the next line
                                $line           = strtok($newline);
                                $eventTimestamp = extractHoursToTimestamp($line, $eventTimestamp);
                                
                                while (true) {
                                    if (strpos($line, "<div class=\"row\" data-equalizer ") !== false) {
                                        // Skip the line if it contains an image
                                        if (strpos($line, "<img") !== false) {
                                            $line = strtok($newline);
                                        }
                                        
                                        // Go one line forward to the actual content
                                        $line = strtok($newline);
                                        
                                        for ($j = 0; $j < $eventInformationLines; $j++) {
                                            $line = strtok($newline);
                                            
                                            // Skip the line if it contains an image
                                            if (strpos($line, "<img") !== false) {
                                                $line = strtok($newline);
                                            }
                                            
                                            // Check if the line contains "<div" elements, then go straight to parsing the information
                                            if (strpos($line, "<div") !== false) {
                                                $eventInformation = extractEventInformation($eventInformation);
                                                break;
                                            }
                                            
                                            // Check if the line contains "</div" elements, then go straight to parsing the information
                                            if (strpos($line, "</div") !== false) {
                                                $eventInformation = extractEventInformation($eventInformation);
                                                break;
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
            $line = strtok($newline);
        }
        break;
    }
    
    // Delete mystical whitespace from $eventTimestamp
    $eventTimestamp = preg_replace("/[[:blank:]]+/", " ", $eventTimestamp);
    
    // Return what will be written into the porssiEvents.txt
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


function extractDate($line)
{
    // Example inputs:
    // pe 10.11.2017
    // </div>
    // - la 11.11.2017
    
    // If the string contains "</div>" there won't be date information
    if (strpos($line, "</div>") !== false) {
        return "empty";
    }
    
    return $line;
}


function extractTimestamp($startDate, $endDate)
{
    // Example inputs:
    // pe 01.11.2017
    // - la 11.11.2017
    
    // If it's only one day event, then $endDate === "empty"
    if (strcmp($endDate, "empty") === 0) {
        $startDate = $startDate;
        
        // Substring the shortened weekday off
        $startDate = substr($startDate, strrpos($startDate, " "));
        // echo $startDate = "01.11.2017";
        
        // Format the date and get the year and leading 0's off 
        $startDate = str_replace(".", "-", $startDate);
        $startDate = date("j.n.", strtotime($startDate));
        // echo $startDate = "1.11."
        
        return $startDate;
    }
    
    // Substring the shortened weekday off
    $startDate = substr($startDate, strrpos($startDate, " "));
    $endDate   = substr($endDate, strrpos($endDate, " "));
    
    // Format the dates and get the year and leading 0's off 
    $startDate = str_replace(".", "-", $startDate);
    $startDate = date("j.n.", strtotime($startDate));
    
    $endDate = str_replace(".", "-", $endDate);
    $endDate = date("j.n.", strtotime($endDate));
    
    $result = $startDate . " - " . $endDate;
    
    return $result;
}



function extractHoursToTimestamp($line, $eventTimestamp)
{
    
    // If the event doesn't have hours, then $line === "00:00"
    if (strcmp(trim($line), "00:00") === 0) {
        return $eventTimestamp;
    }
    
    // Check if the hours contain letters, then just return $eventTimestamp
    if (preg_match("/[a-z]/i", $line)) {
        return $eventTimestamp;
    }
    
    // Check if it's a 2 day event
    if (strpos($eventTimestamp, " - ") !== false) {
        // Example input: 
        // 06:30 - 23:00
        // or even "Koko päivä päivä"
        
        // Add the hours to the timestamp
        // Substring the hours for the startHours and endHours. Trim them at the same time.
        $startHours = trim(substr($line, 0, strpos($line, " -")));
        $endHours   = substr($line, strrpos($line, " ") + 1);
        
        // Substring the eventTimestamp
        $startDate = substr($eventTimestamp, 0, strpos($eventTimestamp, " -"));
        $endDate   = substr($eventTimestamp, strrpos($eventTimestamp, " ") + 1);
        
        // Remake the $eventTimestamp
        $eventTimestamp = $startDate . " " . $startHours;
        $eventTimestamp .= " - " . $endDate . " " . $endHours;
        
        return $eventTimestamp;
    }
    
    // If the event is just on one day
    return $eventTimestamp . $line;
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
    // Get the div's content with class "css-events-list"
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
    
    // Check the second page of events if there is one. It will be at the end of the list.
    if (strcmp(end($links), "/tapahtumat/?pno=2") === 0) {
        // Delete the link to the second page from the links list.
        array_pop($links);
        
        if (strcmp(end($links), "/tapahtumat/?pno=2") === 0) {
            // Delete possible second link to the second page from the links list.
            array_pop($links);
        }
    }
    $porssiRawUrlDataJson = "/Users/JaniS/Sites/Jyunioni server/Raw event data/porssiRawUrlData.json";
    
    // Write the array of links into the .json file
    $fp = fopen($porssiRawUrlDataJson, "w");
    if (fwrite($fp, json_encode($links, JSON_PRETTY_PRINT)) !== false) {
        echo "<b><i>porssiRawUrlData.json written succesfully to: </i></b>" . $porssiRawUrlDataJson . "<br>";
    }
    fclose($fp);
}


?>