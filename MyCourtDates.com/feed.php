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
ob_start('ob_gzhandler');

/**
* This class defines an object for creating an structured data feeds for attorney calendars.
* It supports ICS files, xml, and JSON data.
*/
class ICS
{
    //  Class variables.
    protected $v = null;
        
    function __construct( $NAC_object, $sumStyle ){
    	// initialize a new calendare object
    	$this->v = new vcalendar( array( 'unique_id' => 'Court Schedule' ));
        // echo "new calendar initiated.";
        
    	// Set calendar properties
    	$this->v->setProperty( 'method', 'PUBLISH' );
    	$this->v->setProperty( "X-WR-TIMEZONE", "America/New_York" );
    	
    	// Add the title and description.
        $this->v->setProperty( "x-wr-calname", $NAC_object->getAttorneyLName() . "--HamCo Courts" );
        $this->v->setProperty( "X-WR-CALDESC", "This is " . $NAC_object->getAttorneyFName() . " ". $NAC_object->getAttorneyMName() . " " . $NAC_object->getAttorneyLName() . "'s schedule for Hamilton County Courts.  It covers the last six months of scheduled court appearance and all future scheduled appearances.  It only list scheduled appearances for case where " . $NAC_object->getAttorneyLName() . " is listed as an attorney of record. It was created at " . date("F j, Y, g:i a") ) . ".";

        //  Add all events to Calendar object
        foreach ( $NAC_object->getNACs() as $NAC ) {
            // Build stateTimeDate
            $year = date ( "y", $NAC[ "timeDate" ] );
            $month = date ( "m", $NAC[ "timeDate" ] );
            $day = date ( "d", $NAC[ "timeDate" ] );
            $hour = date ( "H", $NAC[ "timeDate" ] );
            $minutes = date ( "i", $NAC[ "timeDate" ] );
            $seconds = "00";

            // Create the event object
            $e = $this->v->newComponent( 'vevent' );                // initiate a new EVENT
            $e->setProperty( 'summary', $NAC_object->createSummary( $NAC, $sumStyle) );   // set summary/title
            $e->setProperty( 'categories', 'Court_dates' );      // catagorize
            $e->setProperty( 'dtstart', $year, $month, $day, $hour, $minutes, $seconds );     // 24 dec 2006 19.30
            $e->setProperty( 'duration', 0, 0, 0, 15 );         // 3 hours
            $e->setProperty( 'description', $NAC_object->getNACDescription( $NAC ) );     // describe the event
            $e->setProperty( 'location', $NAC[ "location" ] );    // locate the event

            // Create event alarm
            if ( self::setAlarm() ) {
                // create an event alarm
                $valarm = & $e->newComponent( "valarm" );

                // reuse the event description
                $valarm->setProperty("action", "DISPLAY" );
                $valarm->setProperty("description", $e->getProperty( "summary" ));

                // set alarm to 60 minutes prior to event.
                $valarm->setProperty( "trigger", self::setAlarm() );
            }
        }
    }
    function __get ( $var ){
        switch ( $var ) {
            case 'ics' :
                $this->v->returnCalendar();       // generate and redirect output to user browser
                break;
            case 'xml':
                // return well-formed xml
                echo iCal2XML( $this->v );
                break;
            case 'json':
                echo "{\"aaData\":" . json_encode( $this->v ) . "}";
                break;
            case 'string':
                // generate and get output in string, for testing?
                echo $this->v->createCalendar();
                break;
        }
    }
    function setAlarm ( $minutes = 60){
        return "-PT" . $minutes  . "M";
    }
}

if(isset( $_GET[ "sumstyle" ] )){
    $sumStyle = $_GET[ "sumstyle" ];
}else{
    $sumStyle = "Sc";
}

if(isset( $_GET["id"] )){
    $c = new ICS( new AttorneySchedule( $_GET["id"] ), $sumStyle );
    if( isset( $_GET[ "output" ] ) ){
        $c->__get( "output" );
    }else{
        $c->__get( "ics" );
    }
}
else{
    // echo    "You must provide an attorneyd id in the URI,\ne.g., MyCourtDates.com/ics.php?id=69613.\n\n\tThe following is dummy data:";
    $c = new ICS( new AttorneySchedule( "76220" ), $sumStyle );
    if( isset( $_GET[ "output" ] ) ){
        $c->__get( "output" );
    }else{
        $c->__get( "string" );
    }
}

?>