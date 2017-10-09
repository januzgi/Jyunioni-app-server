<?php

# URL
$PORSSI_EVENTS_URL = "http://www.porssiry.fi/tapahtumat/";

// Get Porssi's "css-events-list" HTML div's URL list to a porssiRawUrlData.json file
// The event's page list in http://www.porssiry.fi/tapahtumat/ goes to page 2 after 20 events.
// So only fetch the 20 first and not the url "http://www.porssiry.fi/tapahtumat/?pno=2"
fetchUrls($PORSSI_EVENTS_URL);


function fetchUrls($url)
{
    // Get the content of the site
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
    
    // Check the second page of events if there is one. It will be at the end of the list.
    if (strcmp(end($links), "/tapahtumat/?pno=2") === 0) {
        // Delete the link to the second page from the links list.
        array_pop($links);
        
        if (strcmp(end($links), "/tapahtumat/?pno=2") === 0) {
            // Delete possible second link to the second page from the links list.
            array_pop($links);
        }
    }
    
    // Write the array of links into the .json file
    $fp = fopen('/Users/JaniS/Sites/Jyunioni server/Raw event data/porssiRawUrlData.json', 'w');
    if (fwrite($fp, json_encode($links, JSON_PRETTY_PRINT)) !== false) {
        echo "porssiRawUrlData.json written succesfully." . "<br>";
    }
    fclose($fp);
}



/*

Event porssiEvent = null;

* Fetch each event's data using the URL array to create the Event objects.
// The event's page list in http://www.porssiry.fi/tapahtumat/ goes to page 2 after 20 events.
// So only fetch the 20 first and not the url "http://www.porssiry.fi/tapahtumat/?pno=2"
for (int j = 0; j < porssiEventUrls.size() - 2; j++) {
porssiUrl = porssiEventUrls.get(j);

// Extract relevant fields from the HTTP response and create a list of Porssi's Events
porssiEvent = porssiDetailsParser.extractPorssiEventDetails(porssiUrl);
eventsPorssi.add(porssiEvent);
}

*/


?>