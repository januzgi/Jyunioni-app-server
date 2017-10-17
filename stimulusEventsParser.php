<?php

# URL
$STIMULUS_EVENTS_URL = "http://stimulus.fi/ilmoittautuminen.php";

// Set the default timezone to use.
date_default_timezone_set('Europe/Helsinki');


// Get Stimulus' "ilmo_content" HTML div's URL list to a stimulusRawUrlData.json file
fetchUrls($STIMULUS_EVENTS_URL);

// Using the list of URL's, get each event's data from it's own event page and parse the event attributes.
$stimulusUrlsJson = file_get_contents("/Users/JaniS/Sites/Jyunioni server/Raw event data/stimulusRawUrlData.json");

// Decode the .json into an array
$stimulusEventUrls = json_decode($stimulusUrlsJson, true);


// Get the raw event data into stimulusRawEventData.txt and extract the event details into stimulusEvents.txt
getRawEventData($stimulusEventUrls);


// Fetch each event's raw data from the event's page
function getRawEventData($urls)
{
    // All different events data will be put into this string to be put into stimulusRawEventData.txt
    # $eventPageContents = "";

    // A temporary variable for single events raw data
    $tmp               = "";
    
    // The parsed event details will be put into this string
    $eventDetails = "STIMULUS\n";
    
    
    foreach ($urls as $url) {
        
        // Get the content of the site
        $html = file_get_contents($url);
        
        // Create a new DOMDocument instance
        $dom = new DOMDocument();
        
        // Surpress errors with the '@' and load the String containing the page's HTML to the DOMDocument object
        @$dom->loadHTML($html);
        
        // More about the xpath query usage: http://www.the-art-of-web.com/php/html-xpath-query/
        // Get the div's content with id "ilmo_content"
        $xpath         = new DOMXpath($dom);
        $xpath_results = $xpath->query('//div[@id="ilmo_content"]');
        // $contents is an instance of DOMNodeList
        
        // Put the HTML from the page into this single events raw data string
        $tmp = $dom->saveHTML($xpath_results->item(0));

        # Then add it to the all raw data string
        # $eventPageContents .= $tmp;
        
        // Parse the event details from the .txt
        $eventDetails .= extractStimulusEventDetails($tmp, $url);
    }
    
    /*
    # Get all events raw data into a file.
    $stimulusRawEventData = "/Users/JaniS/Sites/Jyunioni server/Raw event data/stimulusRawEventData.txt";
    
    // Write the raw data results into stimulusRawEventData.txt file.
    if (file_put_contents($stimulusRawEventData, $eventPageContents) !== false) {
        echo "<br><b><i>Stimulus' raw events data written succesfully to: </i></b>" . $stimulusRawEventData . "<br>";
    }
    */
    
    
    $stimulusEvents = "/Users/JaniS/Sites/Jyunioni server/Parsed events/stimulusEvents.txt";
    
    // Write the parsed events into stimulusEvents.txt file.
    if (file_put_contents($stimulusEvents, $eventDetails) !== false) {
        echo "<br><b><i>Stimulus' parsed events data written succesfully to: </i></b>" . $stimulusEvents . "<br>";
    }
    
}


function extractStimulusEventDetails($rawInformation, $eventUrl)
{
    // Different event attributes, url is given as a parameter
    $eventName             = "";
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
            if (strpos($line, "<p class=\"tapaht_otsikko\" ") !== false) {
                $eventName = extractName($line);
                
                while (true) {
                    if (strpos($line, "<h4 class=\"halffloat\">Ajankohta:") !== false) {
                        $eventTimestamp = extractTimestamp($line);
                        
                        while (true) {
                            if (strpos($line, "<br class=\"clear\"") !== false) {
                                // Skip to the first line of the <p> element where the event information is.
                                $line = strtok($newline);
                                
                                // Limit the amount of text in the event information to $eventInformationLines of <p> elements.
                                for ($j = 0; $j < $eventInformationLines; $j++) {
                                    $line = strtok($newline);
                                    
                                    // Skip the line if it contains "<div class="
                                    if (strpos($line, "<div class=") !== false) {
                                        $line = strtok($newline);
                                        // Skip the line if it contains "<div class="
                                        if (strpos($line, "<div class=") !== false) {
                                            $line = strtok($newline);
                                        }
                                    }
                                    
                                    // Skip the line if it contains "<div id="
                                    if (strpos($line, "<div id=") !== false) {
                                        $line = strtok($newline);
                                        // Skip the line if it contains "<div id="
                                        if (strpos($line, "<div id=") !== false) {
                                            $line = strtok($newline);
                                        }
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
    
    // Return what will be written into the stimulusEvents.txt file
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
    // <h4 class="halffloat">Ajankohta: 17.10.2017 klo. 16:00</h4>
    $startDate = "";
    $endDate   = "";
    
    $line = trim(htmlspecialchars_decode((strip_tags($line))));
    // echo $line = "Ajankohta: 17.10.2017 klo. 16:00"
    
    // Substring "Ajankohta: " off
    $line = substr($line, strpos($line, " ") + 1);
    // echo $line = "17.10.2017 klo. 16:00"
    
    // Substring "klo. " off
    $line = preg_replace("/klo. /", "", $line);
    // echo $line = "17.10.2017 16:00"
    
    // Substring year off. Four digits after a '.' will be deleted
    $line = preg_replace("/.\d{4}/", ".", $line);
    // echo $line = "17.10. 16:00"
    
    return $line;
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
    $articles = $xpath->query('.//div[@class="tapahtuma_nosto"]');
    // $articles is an instance of DOMNodeList
    
    // Get all links in div with id "tapahtuma_nosto". Create an array instance.
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
    
    $stimulusRawUrlDataJson = "/Users/JaniS/Sites/Jyunioni server/Raw event data/stimulusRawUrlData.json";
    
    // Write the array of links into the .json file
    $fp = fopen($stimulusRawUrlDataJson, "w");
    if (fwrite($fp, json_encode($links, JSON_PRETTY_PRINT)) !== false) {
        echo "<b><i>stimulusRawUrlData.json written succesfully to: </i></b>" . $stimulusRawUrlDataJson . "<br>";
    }
    fclose($fp);
}


?>