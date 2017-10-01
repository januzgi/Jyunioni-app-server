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

$thisMonthFile = '/Users/JaniS/Sites/Jyunioni/linkkiThisMonthRawEventData.txt';
$nextMonthFile = '/Users/JaniS/Sites/Jyunioni/linkkiNextMonthRawEventData.txt';

// Write the contents back to the file
file_put_contents($thisMonthFile, $thisContent);
file_put_contents($nextMonthFile, $nextContent);

echo "Following files written succesfully:<br>" . $thisMonthFile . "<br>" . $nextMonthFile;


?>