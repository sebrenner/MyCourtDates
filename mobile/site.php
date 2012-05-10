<?php
// If "siteName" isn't in the querystring, set the default site name to 'nettuts'
$siteName = empty($_GET['siteName']) ? 'nettuts' : $_GET['siteName'];

$siteList = array(
'MyCourtDates',
'flashtuts',
'webdesigntutsplus',
'psdtuts',
'vectortuts',
'phototuts',
'mobiletuts',
'cgtuts',
'audiotuts',
'aetuts'
);

// For security reasons. If the string isn't a site name, just change to 
// nettuts instead.
if ( !in_array($siteName, $siteList) ) {
$siteName = 'MyCourtDates';
}

$xml = simplexml_load_file( "http://localhost/~sebrenner/MyCourtDates/MyCourtDates.com/feed.php" );
$events = $xml->vcalendar->components->vevent;

// Include the view
include('views/site.tmpl.php');

