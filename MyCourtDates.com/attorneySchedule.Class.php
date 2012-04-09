<?php 
// 
//  attorneySchedule.Class.php
//  This class defines an object that respresents an attorney's Hamilton County
//  Court schedule.  It may include scheduled events for both that attorney's 
//  schedule as well as the schedules of other attorneys.
//  
//  The database information:
//      Database: todayspo_MyCourtDates
//          Added user todayspo_MyCtAdm with password rE&!m0xW{raH 
//          Added user todayspo_MyCtRdr with password x5[KKE,Dw7.}
//
//  Created by Scott Brenner on 2012-04-07.
//  Copyright 2012 Scott Brenner. All rights reserved.
// 


// This library exposes objects and methods for parseing the html DOM.
require_once( "simplehtmldom_1_5/simple_html_dom.php" );

// This library exposes objects and methods for creating ical files.
require_once( "iCalcreator-2.10.23/iCalcreator.class.php" );

// This code declares the time zone
ini_set('date.timezone', 'America/New_York');
date_default_timezone_set ( 'America/New_York');


class AttorneySchedule
{
//     The Clerk's site provides three date ranges:
//     future only (default) -- date_range=future
//     future and past 6 months -- date_range=prior6
//     future and past year -- date_range=prior12

    // property declaration    
        
    // An array that holds all the attorneyIds asociated with this attorney.
	protected $attorneyIds = array();
	protected $localHtmlPaths = array();
	protected $activeCases = array();
	protected $usedLocal = array();
	protected $NACs = array();	// An array of all future NAC and the last six months of NAC.
	protected $activeNACs = 0;
	protected $curAttorneyId = ""; // used to att attorneyId to NAC.
	protected $fName = null;
	protected $lName = null;
	protected $mName = null;
	protected $NACSummaryStyle = null;
	protected $lastUpdated = null;
	
	// Method Definitions
	function __construct( $principalAttorneyId ) {
        $this::collectAttorneyIds( $principalAttorneyId );
        // print_r( $this->attorneyIds );

	    // Loop through each attorneyId and get the html schedule
	    foreach( $this->attorneyIds as $attorneyId ) {
	        $this->curAttorneyId = $attorneyId;
	        if ( self::localExpired( $attorneyId ) ){
	            // Get html from stie.
                // echo "Getting html from Clerk's site.";
	            $htmlStr = $this::queryClerkSite( $attorneyId );
	            $this->usedLocal[$attorneyId] = false;
            }
            else{
                // Get html from local file.
                // echo "Getting html from local directory.";
                $htmlStr = file_get_contents( $this::pathToLocalHtml( $attorneyId ) );
	            $this->usedLocal[ $attorneyId ] = true;
            }
            
            // Remove cruft from html string
            $this::removeCruft( $htmlStr );

            // Get Attorney Name
            $this::extractAttorneyName( $htmlStr );

            // Parse NAC tables, converting each into and NAC array and adding it to NACs array.
            $this->NACs = array_merge( $this->NACs, $this::parseNACTables( $htmlStr ) );
        }
        usort( $this->NACs, 'self::compareDate');
	}
	protected function queryClerkSite( &$attorneyId ){
	    // Get html file.
        $htmlStr = file_get_contents ( "http://www.courtclerk.org/attorney_schedule_list_print.asp?court_party_id=" . $attorneyId . "&date_range=prior6" );
        
        // Save raw html file as string.
        $fh = fopen( $this::pathToLocalHtml( $attorneyId ), 'w') or die ("Can't open file: $this::pathToLocalHtml( $attorneyId ).");
        fwrite( $fh, $htmlStr );
        fclose( $fh );
        
        return $htmlStr;
	}
	protected function removeCruft( &$htmlStr ){
        // echo "Length of htmlStr before str_replace:" . strlen($htmlStr) . "\n";
        $replacements = array(
                    "\r\n" => "",
                    "\n" => "",
                    "\r" => "", 
                    "<strong>" => "",
                    "align=\"center\"" => "",
                    "</strong>" => "",
                    " colspan=\"2\"" => "", 
                    " colspan=\"3\"" => "", 
                    " colspan=\"4\"" => "",
                    " class=\"row1\"" => "",
                    "\r\n" => "",
                    "\n" => "",
                    "\r" => "",
                    " class=\"row2\"" => "",
                    " rowspan=\"3\"" => "",
                    "<BR>" => " ",
                    "/case_summary.asp?casenumber=" => "#",
                    "/case_summary.asp?sec=doc&casenumber=" => "#",
                    "\t" => " ",
                    "\r\n" => "",
                    "\n" => "",
                    "\r" => "",
                    "> <" => "><",
                    " cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\"" =>  " id=\"NAC\"",
                    "\r\n" => "",
                    "\n" => "",
                    "\r" => ""
        );
        foreach ($replacements as $key => $value) {
    	    $htmlStr = str_replace( $key, $value, $htmlStr, $count);
            // echo "Replaced $count instances of \"$key\"\n";
    	    while ( strpos( $htmlStr, "  " ) ) {
        	    $htmlStr = str_replace( "  ", " ", $htmlStr, $count);
                // echo "Removed $count double spaces.\n";
            }
        }
        // echo "Length of htmlStr after str_replace:" . strlen($htmlStr). "\n";
        
        // Save de-crufted html file as string--for error checking.
        // $myFile = "temp/". $this->attorneyIds[0] ."_SchedFile_Parsed.html";
        // $fh = fopen($myFile, 'c') or die("can't open file");
        // fwrite( $fh, $htmlStr );
        // fclose( $fh );
    }
	protected function collectAttorneyIds( $principalAttorneyId ){
		// This function will get all the attorney ids of all the shedules
		// that shoud be included with this principal attorney id.
        
        // set the first item as the principalAttorneyId
        $this->attorneyIds[0] = $principalAttorneyId;
        
        // db connection informaation.  Eventually this will be moved to an include file.
        $dbHost = "todayspodcast.com";
        $db = "todayspo_MyCourtDates";
        
        $dbReader = "todayspo_MyCtRdr";
        $dbReaderPassword = "x5[KKE,Dw7.}";
        
        $dbAdmin = "todayspo_MyCtAdm";
        $dbAdmin = "rE&!m0xW{raH";
        include("passwords/todayspo_MyCourtDates.php");
        
        // Query the db for additional ids and append them to the array.

        try 
        {
            $dbh = mysql_connect( $dbHost, $dbReader, $dbReaderPassword) or die(mysql_error());
            mysql_select_db( $db ) or die( mysql_error() );    
        }

        catch(PDOException $e)
        {
            echo $e->getMessage();
            echo "<br><br>Database $db -- NOT -- loaded successfully .. ";
            die( "<br><br>Query Closed !!! $error");
        }
        
        $query = "SELECT addOnAttorneyId
                    FROM addOnAttorneyIDTable 
                    WHERE principalAttorneyId=\"$principalAttorneyId\"";
        // echo $query;

        $result = mysql_unbuffered_query($query) or die(mysql_error());
        while( $row = mysql_fetch_array( $result, MYSQL_ASSOC )){
                $this->attorneyIds[] = $row[ "addOnAttorneyId" ];
        }
        mysql_close( $dbh );
	}
 	protected function extractAttorneyName( &$htmlStr ){
	    $attyNameIndexStart = strpos ( $htmlStr , "Attorney Name:" ) + 48;  // offset to get to actual name
        $attyNameIndexEnd = strpos ( $htmlStr , "Attorney ID:") - 18; // offset to get to end of actual name
        $attyNameLength = $attyNameIndexEnd - $attyNameIndexStart;
        // echo "\nThe attorney name starsts at $attyNameIndexStart.  It ends at $attyNameIndexEnd.\n It is $attyNameLength char long.\n";
        $attyName = substr ( $htmlStr , $attyNameIndexStart, $attyNameLength );
        // echo "The attyNameTableStr: $attyName\n";
		$attyName = explode( "/" , $attyName );
		if ( array_key_exists( 0, $attyName ) ) $this->lName = $attyName[0];
		if ( array_key_exists( 1, $attyName ) ) $this->fName = $attyName[1];
		if ( array_key_exists( 2, $attyName ) ) $this->mName = $attyName[2];
	}
    protected function parseNACTables( &$htmlStr ){
        //  Create an array to hold all the NACs
        $tempNACs = array();
 	    $index = 0;
 	    while ( True ) {
            $NACTableIndexStart = strpos ( $htmlStr , "<table id=\"NAC\">", $index );
            if ( $NACTableIndexStart === false ){ 
                break;
            }
            $NACTableIndexEnd = strpos ( $htmlStr , "</table>", $NACTableIndexStart );
            $NACTableLength = $NACTableIndexEnd - $NACTableIndexStart + 8;
            $NACTableStr = substr ( $htmlStr , $NACTableIndexStart, $NACTableLength );
            // echo "NacTable starts at $NACTableIndexStart, ends at $NACTableIndexEnd, and is $NACTableLength characters in length.\n Here is what is extracted:\n\n$NACTableStr\n\n";
            $index = $NACTableIndexStart + 1; // Advance index so that next strpos will find next table
            
            // Convert html string to DOM obj.
            $NACTableObj = str_get_html( $NACTableStr );
                        
            // Convert the NAC table into an indexed array.
			$NAC = self::convertNACtoArray( $NACTableObj );

			// Count active NAC and case numbers.
			if ( $NAC[ "active" ] ) {
				$this->activeNACs += 1;
				// Count cases and the NACs/case. If key doesn't exist create it before incrementing.
				if ( array_key_exists ( "caseNum" , $NAC ) ) $this->activeCases[ $NAC[ "caseNum" ] ] = 0;
				$this->activeCases[ $NAC[ "caseNum" ] ] += 1;
			}
			
			// Add the NAC to the temp array of NACs.
			array_push( $tempNACs, $NAC );
		}   //  End of the while loop
		
		//  Sort NAC array by date.
		usort( $tempNACs, 'self::compareDate');
		return $tempNACs;
	}
	protected function compareDate($a, $b){
		if ( $a["timeDate"] == $b["timeDate"] ) return 0;
		return ( $a["timeDate"] < $b["timeDate"] ) ? -1 : 1;
	}
	protected function isWeekend( $date ) {
        return ( date('N', strtotime( $date ) ) >= 6 );
    }
	protected function convertNACtoArray( &$NACTable ){
		$NAC= array();
	
		//	Get date and Time; create dateTime object
		$date = 		$NACTable->find('td', 0 )->innertext;
		$date =			substr( $date, 5);	
		$time = 		$NACTable->find('td', 1 )->innertext;
		$time =			substr( $time, 5);
		
		$NAC[ "timeDate" ] = 	strtotime( $date . $time );
	
	    $NAC[ "attorneyId" ] = $this->curAttorneyId;
	
		$NAC[ "caseNum" ] = $NACTable->find('td', 2 )->find('a', 0 )->innertext;
	
        // extract individual party names
		$caption = $NACTable->find('td', 3 )->innertext;
        // echo "\ncaption: $caption";
    	$vs = strpos( $caption , "vs." );
    	$NAC [ "plaintiffs" ]   = substr ( $caption, 9 , $vs - 9 );
    	$NAC [ "defendants" ]   = substr ( $caption , $vs + 4 );
        // echo "\ndefendants: " . $NAC [ "defendants" ];
        		
		//	Determine if NAC is active
		$active = $NACTable->find('td', 4 )->innertext;
		$active = substr($active, 8);
		$NAC[ "active" ] = 0;
		if ( $active == "A"){
			$NAC["active"] = true;
		}
		
		$location = 	$NACTable->find('td', 5 )->innertext;
		$NAC[ "location" ] = 	substr($location, 27);
		
		$setting = 	$NACTable->find('td', 5 )->innertext;
		$NAC[ "setting" ]  =	substr( $setting, 12 );
		return $NAC;
	}
	protected function pathToLocalHtml( &$attorneyId ){
	    return "attnySchedules/" . $attorneyId . "_SchedFile.html";
	}
	protected function localExpired( &$attorneyId ){
	    // Takes an attonrey Id.
	    // Returns true is the local copy is too old or non-existant.
	    // Retruns false if the local copy is fresh.
        $fileName = $this::pathToLocalHtml( $attorneyId );
	    if ( file_exists( $fileName ) ) {
	        $fileVintage = strftime( "%b %d %Y %X", filectime( $fileName ) );
	        $fileVintage = new DateTime(  );
            
            // echo "fileVintage: $fileVintage";
	        
	        $now = new DateTime( );
	        
	        $interval = $fileVintage->diff( $now );
	        $mintues = $interval->format('%i');

            // $fileVintage = date("Y m d  H:i:s", filectime( $fileName ));
            // $now = date("Y m d  H:i:s", filectime( $fileName ));
            // $interval = $fileVintage->diff( $now );
            // $mintues = $interval->format('%i');

            if ( $this::isWeekend( $now->format('Y-m-d H:i:sP') ) && $fileVintage > strtotime( "Last friday at 4:00 pm" ) ) {
                echo strtotime( "Last friday at 4:00 pm" );
                return False;
            }
            
            if ( $fileVintage > strtotime( "Today at 4:00 pm" ) ) {
                strtotime( "Today at 4:00 pm" );
                return False;
            }
            
            if ( $interval < 90 ) {
                echo "interval: $interval";
                return False;
            }
            return true;
        }
        return true;    // no file exists
	}
	/**
	 * Getters
	*/

    // Event building functions
    protected function createAbbreviatedSetting( $setting ){
    	return "Abv Setting [TK]";
    }
    protected function createSummary( &$NAC, $sumStyle ){
    	/*
    	sumStyle takes a string of the following characters.  Each character
    	is a token, representing a piece of NAC data that can be include in 
    	the summary.
    		d = defendant
    		p = plaintiff
    		c = caption
    		n = case number
    		s = setting
    		l = location
    		j = judge
    		S = abreviated setting
    		L = abreviated location
    	*/
    	$summary = null;
    	for ($i=0; $i < strlen( $sumStyle )  ; $i++) {		
    		switch ( mb_substr( $sumStyle, $i, 1) ) {
    			case 'd':
    				$summary = $summary .  $NAC[ "defendants" ];
    				break;
    			case 'p':
    				$summary = $summary .  $NAC[ "plaintiffs" ];
    				break;
    			case 'c':
    				$summary = $summary .  $NAC[ "plaintiffs" ] . " v. " . $NAC[ "defendants" ];
    				break;
    			case 'n':
    				$summary = $summary .  $NAC[ "caseNum" ];
    				break;
    			case 's':
                    // $summary = $summary .  $NAC[ "summary" ];
    				break;
    			case 'l':
    				$summary = $summary .  $NAC[ "location" ];
    				break;
    			case 'j':
    				$summary = $summary .  $NAC[ "judge" ];
    				break;
    			case 'S':
    				$summary = $summary .  self::createAbbreviatedSetting( $NAC[ "setting" ] );
    				break;
    			default:
    				break;
    		}
    		$summary = $summary .  " |-| ";
    	}
    	return $summary;
    	return "[TK]summary???";
    }
    protected function getNACDescription( $NAC ){
    	$description = "\nPlaintiffs Counsel:" . self::retrieveProsecutors( $NAC ) . 
    		"\nDefense Counsel:" . self::retrieveDefense( $NAC ) .  "\n" . self::retrieveCause( $NAC )  . 
    		"\n" . self::getHistURI( $NAC[ "caseNum" ]) . "\n\n" . $NAC["plaintiffs"] . " v. " . $NAC["defendants"] .
    		"\n\nAs of " . $this->lastUpdated;
    	return $description;
    }

    // Case-specific getters
    protected function retrieveCause( &$NAC ){
    	return "[TK] cause";
    }
    protected function retrieveProsecutors( &$NAC ){
    	return "[TK] PROSECUTOR";
    }
    protected function retrieveDefense( &$NAC ){
    	return "[TK] DEFENSE";
    }

	//	URI getters
	function getSumURI( &$cNum){
		return "http://www.courtclerk.org/case_summary.asp?casenumber=" . rawurlencode($cNum);
	}
	function getDocsURI( &$cNum){
		return "http://www.courtclerk.org/case_summary.asp?sec=doc&casenumber=" . rawurlencode($cNum);
	}
	function getCaseSchedURI( &$cNum){
		return "http://www.courtclerk.org/case_summary.asp?sec=sched&casenumber=" . rawurlencode($cNum);
	}
	function getHistURI( &$cNum){
		return "http://www.courtclerk.org/case_summary.asp?sec=history&casenumber=" . rawurlencode($cNum);
	}

	// Attorney Getters
	function getPrincipalAttorneyId(){
		return $this->attorneyIds[ 0 ];
	}
	function usedLocal( &$attoneyId ){
	    return $this->usedLocal[$attoneyId];
	}
	function getAttorneyLName(){
		return $this->lName;
	}
	function getAttorneyFName(){
		return $this->fName;
	}
	function getAttorneyMName(){
		return $this->mName;
	}

	// NACs getters
	function getNACs(){
		return $this->NACs;
	}
	function getNACCount(){
		return count( $this->NACs) ;
	}
	function getActiveNACCount(){
		return $this->activeNACs;
	}
	function getNacTimeFrame(){
		$timeFrame = array(
			"earliestDate" 	=> null,
			"lastDate"		=> null
		);
		$timeFrame[ "earliestDate" ] = $this->NACs[0][ "timeDate" ];
		$timeFrame[ "lastDate" ] = $this->NACs[ count( $this->NACs ) - 1 ][ "timeDate" ];
		return $timeFrame;
	}
	function getEarliestDate(){
		$first = reset ( $this->NACs );
		return $first[ "timeDate" ];
	}
	function getLastDate(){
		$last = end( $this->NACs );
		return $last[ "timeDate" ];
	}
	function getActiveCaseCount(){
		return count( $this->activeCases );
	}
	function getActiveCaseNumbers(){
		return $this->activeCases;
	}

	// Output getters
	function getJSON(){
	    return json_encode( $NACS);
	}
	function getICS( $outputType, $sumStyle ){
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
	
		foreach ( $this->NACs as $NAC ) {
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
            $e->setProperty( 'summary', $this::createSummary( $NAC, $sumStyle) );   // set summary/title
            $e->setProperty( 'categories', 'Court_dates' );      // catagorize
            $e->setProperty( 'dtstart', $year, $month, $day, $hour, $minutes, $seconds );     // 24 dec 2006 19.30
            $e->setProperty( 'duration', 0, 0, 0, 15 );         // 3 hours
            $e->setProperty( 'description', self::getNACDescription( $NAC ) );     // describe the event
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
}

?>