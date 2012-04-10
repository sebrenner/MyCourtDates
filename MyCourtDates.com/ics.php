<?php 
// 
//  ics.php
//  This script takes the following arguments:
//  AttorneyId - the pricipal id of the attorney whose schedule is to be built 
//  
//  The other information neede to prepare the ics file can be pulled from the db or flat file.
//  It may make sense to move it to the URI paramaters, but for now it seems smatert to keep it
//  in a db, or maybe a flat file.
//
//  Created by Scott Brenner on 2012-04-06.
//  Copyright 2012 Scott Brenner. All rights reserved.
// 

// This class exposes objects and methods for converting courtClerk.org pages to stand data formats.
require_once( "attorneySchedule.Class.php" );

function getICS( &$NAC_object, $outputType, $sumStyle ){
	// This command will cause the script to serve any output compressed
	// with either gzip or deflate if accepted by the client.
    // echo "getICS";
	ob_start('ob_gzhandler');

	// initiate new CALENDAR
	$v = new vcalendar( array( 'unique_id' => 'Court Schedule' ));
    // echo "new calendar initiated.";
    
	// Set calendar properties
	$v->setProperty( 'method', 'PUBLISH' );
	$v->setProperty( "X-WR-TIMEZONE", "America/New_York" );
	$calName = "Hamilton County Schedule for [TK ATTORNEY]";
    $v->setProperty( "x-wr-calname", $calName );
    $v->setProperty( "X-WR-CALDESC", "This is [TK ATTORNEY NAME]'s schedule for Hamilton County Common Courts.  It covers " . "firstDateReq" . " through " . "lastDateReq" . ". It was created at " . date("F j, Y, g:i a") );

	foreach ( $NAC_object->getNACs() as $NAC ) {
        // Build stateTimeDate
        $year = date ( "y", $NAC[ "timeDate" ] );
        $month = date ( "m", $NAC[ "timeDate" ] );
        $day = date ( "d", $NAC[ "timeDate" ] );
        $hour = date ( "H", $NAC[ "timeDate" ] );
        $minutes = date ( "i", $NAC[ "timeDate" ] );
        $seconds = "00";

        // Create the event object
        $UId = strtotime("now") . "[TK caseNumber]"  . "@cms.halilton-co.org";
        $e = & $v->newComponent( 'vevent' );                // initiate a new EVENT
        $e->setProperty( 'summary', $NAC_object->createSummary( $NAC, $sumStyle) );   // set summary/title
        $e->setProperty( 'categories', 'Court_dates' );      // catagorize
        $e->setProperty( 'dtstart', $year, $month, $day, $hour, $minutes, $seconds );     // 24 dec 2006 19.30
        $e->setProperty( 'duration', 0, 0, 0, 15 );         // 3 hours
        $e->setProperty( 'description', $NAC_object->getNACDescription( $NAC ) );     // describe the event
        $e->setProperty( 'location', $NAC[ "location" ] );    // locate the event
    }	
	switch ( $outputType ) {
	    case 0:
	        $v->returnCalendar();           // generate and redirect output to user browser
	        break;
	    case 1:
	        $str = $v->createCalendar();    // generate and get output in string, for testing?
	        echo $str;
	        // echo "<br />\n\n";
	        break;
	    case 3:     //JSON Data
	        print "{\"aaData\":" . json_encode($events) . "}";
	        break;
	}
}

if(isset( $_GET["id"] )){
    $a =  new AttorneySchedule( $_GET["id"] );
    
    getICS( $a, 0, "Spdcnlsj" );
}
else{
    echo    "You must provide an attorneyd id in the URI,\n
            e.g., MyCourtDates.com/ics.php?id=69613.";
    $a =  new AttorneySchedule( "73125" );
    getICS( $a, 0, "Spdcnlsj" );
}

?>