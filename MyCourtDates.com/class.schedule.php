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
//  My standard date format "Y-m-d H:i:s"
//
//  Created by Scott Brenner on 2012-04-07.
//  Copyright 2012 Scott Brenner. All rights reserved.
// 


// This library exposes objects and methods for parseing the html DOM.
require_once( "simplehtmldom_1_5/simple_html_dom.php" );

// This library exposes objects and methods for creating ical files.
require_once( "libs/iCalcreator-2.12/iCalcreator.class.php" );

// This code declares the time zone
ini_set('date.timezone', 'America/New_York');
date_default_timezone_set ( 'America/New_York');


class schedule
{
//     The Clerk's site provides three date ranges:
//     future only (default) -- date_range=future
//     future and past 6 months -- date_range=prior6
//     future and past year -- date_range=prior12

    // property declaration    
        
    // An array that holds all the attorneyIds asociated with this attorney.
    protected $verbose = true;
    protected $userBarNumber = null;
    protected $isUserAuthorized = false;
    protected $fName = null;
	protected $lName = null;
	protected $mName = null;
	protected $userBarNumNACs = array();

	protected $curAttorneyId = null; // used to att attorneyId to NAC.
	protected $addOnAttorneyIds = array();
	protected $activeCases = array();
	protected $activeNACs = 0;
	protected $NACSummaryStyle = null;
	protected $lastUpdated = null;
	
	// Method Definitions
	function __construct( $userBarNumber ) {
	    if ( $this->verbose ) echo  __METHOD__ . "\n";
        $isUserAuthorized = self::subscriberAuthorized( $userBarNumber );
        $this->userBarNumber = $userBarNumber;
        $this->curAttorneyId = $userBarNumber;
        echo "\tuserBarNumber: " . $this->userBarNumber ."\n";
        if ( self::isScheduleStale( $userBarNumber ) ){
            // Get html from stie.
            $htmlStr = self::queryClerkSite( $userBarNumber );
            self::removeCruft( $htmlStr );  // Pass by ref. modifies $htmlStr
            self::extractAttorneyName( $htmlStr );
            echo "\t"
                . self::getAttorneyFName() . " "
                . self::getAttorneyMName() . " "
                . self::getAttorneyLName()
                . "\n";
            $this->userBarNumNACs =  self::parseNACTables( $htmlStr );
        }
        else{
            // Get data from dB
            echo "\tGetting data from dB.\n";
        }
        usort( $this->userBarNumNACs, 'self::compareNACDate');
	}
	protected function subscriberAuthorized( $barNum ){
        // returns BOOL
        include("passwords/todayspo_MyCourtDates.php");
        try{
            $dbh = mysql_connect( $dbHost, $dbReader, $dbReaderPassword) or die(mysql_error());
            mysql_select_db( $db ) or die( mysql_error() );    
        }
        catch(PDOException $e){
            echo $e->getMessage();
            echo "<br><br>Database $db -- NOT -- loaded successfully .. ";
            die( "<br><br>Query Closed !!! $error");
        }
        $query =   "SELECT subExpire 
                    FROM user_tbl
                    WHERE userBarNumber = \"$barNum\"
                    LIMIT 1;";
        $result = mysql_query( $query, $dbh ) or die( mysql_error() );
        $row = mysql_fetch_assoc( $result );
        mysql_close( $dbh );
        if ( $row["subExpire"] < date( "Y-m-d H:i:s" ) ) { return true; }    
        return false;
    }
	protected function vintage( $barNumber ){
	    if ( $this->verbose ) echo  __METHOD__ . "\n";
        include("passwords/todayspo_MyCourtDates.php");
        // Query the db for additional ids and append them to the array.
        try{
            $dbh = mysql_connect( $dbHost, $dbReader, $dbReaderPassword) or die(mysql_error());
            mysql_select_db( $db ) or die( mysql_error() );    
        }
        catch(PDOException $e){
            echo $e->getMessage();
            echo "<br><br>Database $db -- NOT -- loaded successfully .. ";
            die( "<br><br>Query Closed !!! $error");
        }
        $query =   "SELECT 	addOnBarNumber, MAX( vintage ) AS vintage
                    FROM   	addOnBarNumber_tbl
                    WHERE 	addOnBarNumber = \"$barNumber\"";
        $result = mysql_query( $query, $dbh ) or die( mysql_error() );
        $row = mysql_fetch_assoc( $result );
        mysql_close( $dbh );
        if ( $row["vintage"] == null) {
            echo "\tReturning false from ." . __METHOD__ . "\n";
            return false;
        }
        return date( "Y-m-d H:i:s", strtotime( $row["vintage"] ) );
	}
	protected function queryClerkSite( $attorneyId ){
	    if ( $this->verbose ) echo  __METHOD__ . "\n";
        return file_get_contents(
    "http://www.courtclerk.org/attorney_schedule_list_print.asp?court_party_id="
    . $attorneyId . "&date_range=prior6" );
	}
	protected function removeCruft( &$htmlStr ){
	    if ( $this->verbose ) echo  __METHOD__ . "\n";
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
    }
	protected function isScheduleStale( $barNumber ){
	    if ( $this->verbose ) echo  __METHOD__ . "\n";
        $now = new DateTime( );
        $schedVintage = self::vintage( $barNumber );
        if ( $schedVintage == false ){ return true; }
        $schedVintage = new DateTime( $schedVintage );
        echo "\tschedVintage after converting to dateTime: " 
            .  $schedVintage->format( 'Y-m-d H:i:s' ) . "\n";
        echo $now->getTimestamp() . "\n";
        echo $schedVintage->getTimestamp(). "\n";
        echo ( $now->getTimestamp()  - $schedVintage->getTimestamp() );
        $elapsedMinutes = ( $now->getTimestamp()  - $schedVintage->getTimestamp() ) / 60;

        echo "\tInterval: $elapsedMinutes minutes.\n";
        
        echo "\tIs the vintage less than 90 minutes ago?\n";
        // ============================================
        // = Is the vintage less than 90 minutes ago? =
        // ============================================
        if ( $elapsedMinutes < 90 ) {
            echo "\tThe db is pretty fresh. It was updated only "
                . $elapsedMinutes
                . " mintues ago.\n";
            return false;
        }
        echo "\tNo.\n\tIs the vintage past 4:00 today?\n";
        // ===================================
        // = Is the vintage past 4:00 today? =
        // ===================================
        $today4 = new DateTime( strtotime( "Today at 4:00 pm" ) );
        $intervalSince4 = $schedVintage->diff( $today4 );
        $elapsedMinutesFromFourToday =  ( $schedVintage->getTimestamp() - $today4->getTimestamp() ) / 60;
        echo "\tMinutes elpased since 4:00 today $elapsedMinutesFromFourToday.\n";
        if ( $elapsedMinutesFromFourToday > 0 ) {
            echo "\tIt is NOT the weekend and the db data 
                    was updated after 4:00 today.\n";
            echo "\t$schedVintage->format( 'Y-m-d H:i:s' )  is greater than " 
                . strtotime( "Today at 4:00 pm" ). ".\n";       
            return False;
        }
        echo "\tNo.\n\tIs it the weekend with a vintage past 4:00 Friday?\n";
        // ======================================
        // = Is vintage after 4:00 last Friday? =
        // ======================================        
        $lastFriday = new DateTime( strtotime( "Last friday at 4:00 pm" ) );
        $elapsedMinutesSinceFridayAtFour = ( 
            $schedVintage->getTimestamp() - $lastFriday->getTimestamp() ) / 60;
        echo "\tMinutes elapsed since last Friday at 4:00: " . $elapsedMinutesSinceFridayAtFour. "\n";
        // is today the weekend AND vitage is greater than Friday at 4?
        echo "Today is the weekend?" . self::isWeekend( $now->format( 'Y-m-d H:i:s' ));
        echo "Sched vintag is after 4 on last Friday?" . $elapsedMinutesSinceFridayAtFour > 0;
        if (    self::isWeekend( $now->format( 'Y-m-d H:i:s' ) )
                && $elapsedMinutesSinceFridayAtFour > 0 ) {
            echo "\tIt is the weekend and the db data was updated after 4:00 on Friday.\n";
            return false;
        }
        echo "\tThe schedule for $this->userBarNumber is stale.\n";
        return true;
	}
    protected function extractAttorneyName( $htmlStr ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
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
        // echo "Lname: " . $this->lName ;
        // echo "fname: " . $this->fName;
        // echo "Mname: " . $this->mName;
	}
    protected function parseNACTables( &$htmlStr ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        $tempNACs = array();
 	    $index = 0;
 	    while ( True ) {
            $NACTableIndexStart = strpos (
                $htmlStr , "<table id=\"NAC\">", $index );
            if ( $NACTableIndexStart === false ){ 
                break;
            }
            $NACTableIndexEnd = strpos (
                $htmlStr , "</table>", $NACTableIndexStart );
            $NACTableLength = $NACTableIndexEnd - $NACTableIndexStart + 8;
            $NACTableStr = substr (
                $htmlStr , $NACTableIndexStart, $NACTableLength );
            // echo "NacTable starts at $NACTableIndexStart, ends at $NACTableIndexEnd, and is $NACTableLength characters in length.\n Here is what is extracted:\n\n$NACTableStr\n\n";
            $index = $NACTableIndexStart + 1; // Advance index so that next strpos will find next table
            
            // Convert html string to DOM obj.
            $NACTableObj = str_get_html( $NACTableStr );
                        
            // Convert the NAC table into an indexed array.
			$NAC = self::convertNACtoArrayAndStoreInDb( $NACTableObj );

			// Count active NAC and case numbers.
			if ( $NAC[ "active" ] ) {
				$this->activeNACs += 1;
				// Count cases and the NACs/case. If key doesn't exist create it before incrementing.
				if ( array_key_exists ( "caseNumber" , $NAC ) ) $this->activeCases[ $NAC[ "caseNumber" ] ] = 0;
				$this->activeCases[ $NAC[ "caseNumber" ] ] += 1;
			}
			
			// Add the NAC to the temp array of NACs.
			array_push( $tempNACs, $NAC );
		}   //  End of the while loop
		// update the schedule vintage in db
		self::setVintage( $type = "barNumber" );
		//  Sort NAC array by date.
		usort( $tempNACs, 'self::compareNACDate');
		return $tempNACs;
	}
	protected function compareNACDate($a, $b){
	    if ( $this->verbose ) echo  __METHOD__ . "\n";
		if ( $a["timeDate"] == $b["timeDate"] ) return 0;
		return ( $a["timeDate"] < $b["timeDate"] ) ? -1 : 1;
	}
	protected function isWeekend( $date ) {
	    if ( $this->verbose ) echo  __METHOD__ . "\n";
        return ( date('N', strtotime( $date ) ) >= 6 );
    }
	protected function convertNACtoArrayAndStoreInDb( &$NACTable ){
	    if ( $this->verbose ) echo  __METHOD__ . "\n";
		$NAC= array();
	    
        // ========================================
        // = First add NAC_tbl normalized fields. =
        // ========================================
		//  Get date and Time; create dateTime object
		$NAC[ "caseNumber" ] = $NACTable->find('td', 2 )->find('a', 0 )->innertext;
        
        $date = $NACTable->find('td', 0 )->innertext;
		$date = substr( $date, 5);	
		$time = $NACTable->find('td', 1 )->innertext;
		$time = substr( $time, 5);
		$NAC[ "timeDate" ] =  date ( "Y-m-d H:i:s", strtotime( $date . $time ));
		
	    //	Determine if NAC is active
		$active = substr( $NACTable->find('td', 4 )->innertext, 8);
		$NAC[ "active" ] = false;
		if ( $active == "A"){ $NAC["active"] = true; }
		
		$location = $NACTable->find('td', 5 )->innertext;
		$NAC[ "location" ] = substr($location, 10);
		
		$setting = 	$NACTable->find('td', 6 )->innertext;
		$NAC[ "setting" ]  = substr( $setting, 13 );
		
		// Add NAC to NAC_tbl
		self::updateNACIntoDb( $NAC );
	
	    $NAC[ "attorneyId" ] = $this->curAttorneyId;
        // extract individual party names
		$caption = $NACTable->find('td', 3 )->innertext;
    	$vs = strpos( $caption , "vs." );
    	$NAC [ "plaintiffs" ]   = substr ( $caption, 9 , $vs - 10 );
    	$NAC [ "defendants" ]   = substr ( $caption , $vs + 4 );
        		
        // print_r($NAC);
		return $NAC;
	}
    protected function updateNACIntoDb( $theNAC ){
	    if ( $this->verbose ) echo  __METHOD__ . "\n";
        include( "passwords/todayspo_MyCourtDates.php" );
        // Connect to the db
        try{
            $dbh = mysql_connect( 
                $dbHost, $dbAdmin, $dbAdminPassword ) or die(mysql_error());
            mysql_select_db( $db ) or die( mysql_error() );
        }
        catch(PDOException $e){
            echo $e->getMessage();
            echo "<br><br>Database $db -- NOT -- loaded successfully .. ";
            die( "<br><br>Query Closed !!! $error");
        }
        //  create a string of column names from array keys
        $columns = implode( ", ", array_keys( $theNAC ) );
        $escaped_values = array_map( 
            'mysql_real_escape_string', array_values( $theNAC ) );
        //  Create a string of comma-separated values enclosed in quotes.
        $values = implode("\", \"", $escaped_values);
        $values = "\"" . $values . "\"";
        $query =   "INSERT INTO NAC_tbl ( $columns ) 
                    VALUES ( $values )
                    ON DUPLICATE KEY 
                    UPDATE active = \"" . $theNAC[ "active" ] . "\"";
                    
        // echo "\t" . $query . "\n";
        $result = mysql_query( $query, $dbh ) or die( mysql_error() );
        $values = "\"" . $this->userBarNumber
                    . "\"," 
                    . "\"" 
                    . $theNAC[ "caseNumber" ] . "\"";        
        $query =   "INSERT INTO barNumberCaseNumber_tbl ( userBarNumber, caseNumber )  
                    VALUES ( $values )
                    ON DUPLICATE KEY 
                    UPDATE caseNumber = \"" . $theNAC[ "caseNumber" ] . "\"";
        echo $query;
        $result = mysql_query( $query, $dbh ) or die( mysql_error() );        
        mysql_close( $dbh );
        return true;
	}
	function getICS( $outputType = 1, $sumStyle = "ncslj" ){
	    if ( $this->verbose ) echo  __METHOD__ . "\n";
        
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
        $calName = "Hamilton County Schedule for " . self::getFullName( ) . ".";
        $v->setProperty( "x-wr-calname", $calName );
        $v->setProperty( "X-WR-CALDESC", "This is " . self::getFullName( ) . "'s schedule for Hamilton County Common Courts.  It covers " . "firstDateReq" . " through " . "lastDateReq" . ". It was created at " . date("F j, Y, g:i a") );

        foreach ( $this->userBarNumNACs as $NAC ) {
            print_r($NAC);
            // Build stateTimeDate
            $year = date ( "y", $NAC[ "timeDate" ] );
            $month = date ( "m", $NAC[ "timeDate" ] );
            $day = date ( "d", $NAC[ "timeDate" ] );
            $hour = date ( "H", $NAC[ "timeDate" ] );
            $minutes = date ( "i", $NAC[ "timeDate" ] );
            $seconds = "00";

            // Create the event object
            $UId = strtotime("now") 
                    . $NAC[ "caseNumber"]
                    . "@cms.halilton-co.org";
            $e = & $v->newComponent( 'vevent' );    // initialize a new EVENT
            $e->setProperty( 'summary', $self::createSummary( $NAC, $sumStyle) );   // set summary/title
            $e->setProperty( 'categories', 'Court_dates' );      // catagorize
            $e->setProperty( 'dtstart', $year, $month, $day, $hour, $minutes, $seconds );     // 24 dec 2006 19.30
            $e->setProperty( 'duration', 0, 0, 0, 15 );         // 3 hours
            $e->setProperty( 'description', self::getNACDescription( $NAC ) );     // describe the event
            $e->setProperty( 'location', $NAC[ "location" ] );    // locate the event
        }   
        switch ( $outputType ){
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
    public function getFullName( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        $fullName = $this->fName . " ";
        if ( empty( $this->mName ) ){
            $fullName .= $this->lName;
            return $fullName;
        }
        $fullName .= $this->mName . " " . $this->lName;
        return $fullName;
    }
    protected function setVintage( $type, $caseNumber = null ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        include("passwords/todayspo_MyCourtDates.php");
        try{
            $dbh = mysql_connect( $dbHost, $dbAdmin, $dbAdminPassword) or die(mysql_error());
            mysql_select_db( $db ) or die( mysql_error() );    
        }
        catch(PDOException $e){
            echo $e->getMessage();
            echo "<br><br>Database $db -- NOT -- loaded successfully .. ";
            die( "<br><br>Query Closed !!! $error");
        }
        $now = new DateTime( );
        $nowFormated = $now->format( 'Y-m-d H:i:s' );
        switch ( $type ) {
            case 'barNumber':
                $query =   "INSERT INTO addOnBarNumber_tbl (
                                        userBarNumber, 
                                        addOnBarNumber)
                            VALUES ($this->userBarNumber, 
                                    $this->curAttorneyId )
                            ON DUPLICATE KEY 
                            UPDATE vintage = \"$nowFormated\"";
                break;
            case 'caseNumber':
                // unimplemented
                echo "\tINSERT INTO addOnBarNumber_tbl (
                                        column1, 
                                        column2, 
                                        column3)
                            VALUES ($this->userBarNumber, 
                                    $curAttorneyId, 
                                    $now->format( 'Y-m-d H:i:s' ) )
                            ON DUPLICATE KEY 
                            UPDATE column3 = \""
                                . $now->format( 'Y-m-d H:i:s' )  
                                . "\"\n";
                break;
            default:
                echo "\tno proper type was passed to "  . __METHOD__ . ".\n";
                break;
        }
        // echo $query;
        $result = mysql_query( $query, $dbh ) or die( mysql_error() );
        echo "\tRecords affected: " . mysql_affected_rows() . "\n";
        mysql_close( $dbh );
        return true;
    }













// ============================================
// = Functions below this line are not in use =
// ============================================





    /**
     * subsrciberAuthorized( $barNum )
     *
     * Takes a bar number and returns true if the bar number subscriber is authorized to
     * request a full schedule.
     *
     * @return Bool
     * @author Scott Brenner
     **/
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
        catch( PDOException $e )
        {
            echo $e->getMessage();
            echo "<br><br>Database $db -- NOT -- loaded successfully .. ";
            die( "<br><br>Query Closed !!! $error");
        }
        
        $query = "SELECT addOnBarNumber
                    FROM addOnBarNumber_tbl 
                    WHERE userBarNumber = \"$principalAttorneyId\"";
        // echo $query;

        $result = mysql_unbuffered_query( $query ) or die( mysql_error() );
        while( $row = mysql_fetch_array( $result, MYSQL_ASSOC )){
            $this->attorneyIds[] = $row[ "addOnBarNumber" ];
        }
        mysql_close( $dbh );
	}
	
	/**
	 * This function takes a NAC array and insert the NAC data into
	 * the db.  They keys must match the column names.
	 *
	 * @return void
	 * @author Scott Brenner
	 **/
	protected function pathToLocalHtml( &$attorneyId ){
	    return "attnySchedules/" . $attorneyId . "_SchedFile.html";
	}
	/**
	 * Getters
	*/

    // Event building functions
    function lookUpJudge( &$location, &$caseNumber  ){
        $locationJudges = array(
            "H.C. COURT HOUSE ROOM 485" => "J. Luebbers",
            "H.C. COURT HOUSE ROOM 495" => "J. Allen",
            "H.C. COURT HOUSE ROOM 360" => "J. Martin",
            "H.C. COURT HOUSE ROOM 340" => "J. Myers",
            "H.C. COURT HOUSE ROOM 560" => "J. Nadel",
            "RM 164, CT HOUSE, 1000 MAIN ST" => "BRAD GREENBERG",
        );
        // Check array for judge name
        if ( array_key_exists ( $location, $locationJudges ) ){
            return $locationJudges[ $location ];
        }
        return "Judge Unknown.";
        //  If no judge found query clerk's site
        //  Instantiate CaseInfo object, which will query "http://www.courtclerk.org/case_summary.asp?sec=party&casenumber=" . 
        //  and add the case info including the judge's name to the db.

        // $c = new  CaseInfo( &$caseNumber );
        // return $c->getJudge();
    }
    function createAbbreviatedSetting( $setting ){
        // create an associative array mapping setting to abv
        $abreviations = array(
            "NAC" => "Abreviation",
            "PLEA OR TRIAL SETTING" => "PTS",
            "CMC INITIAL CASE MANAGEMENT" => "CMC",
            "ARRAIGNMENT" => "ARGN",
            "CASE MANAGEMENT CONFERENCE" => "CMC",
            "SCHEDULING CONFERENCE" => "CMC",
            "SENTENCE" => "SENT",
            "SENTENCING" => "SENT",
            "REPORT" => "RPT",
            "STATUS REPORT" => "RPT",
            "DSC/DISPOSITION SCHEDULING CON" => "DSC",
            "JURY TRIAL" => "JT",
            "PROBATION VIOLATION" => "PV",
            "TELEPHONE REPORT" => "TELE. RPT",
            "CIVIL PROTECTION ORDER HEARING" => "CPO HRG",
            "ENTRY" => "FE",
            "FINAL ENTRY" => "FE",
            "MOTION FOR SUMMARY JUDGMENT" => "MSJ",
            "PRE-TRIAL" => "PT",
            "BENCH TRIAL" => "BT",
            "PLEA" => "PLEA",
            "DISP SCHEDULING CONFERENCE" => "DSC",
            "POST-CONVICTION WARRANT RETURN" => "WRNT RTN",
            "MEDIATION CONFERENCE" => "ADR",
            "EXPUNGEMENT" => "EXPNG",
            "PROBABLE CAUSE HEARING" => "PV",
            "IN PROGRESS, JURY TRIAL" => "JT",
            "MOTION FOR JUDGMENT DEBTOR" => "J DEBT",
            "MOTION TO SUPPRESS" => "MOT SUPP",
            "MOTION" => "MOT",
            "PROBABLE CAUSE HEARING, PROBATION VIOLATION" => "PV",
            "TELEPHONE SCHEDULING CONF" => "TELE RPT",
            "ORDR OF APPRNCE/JDGMNT DEBTOR" => "J DEBT",
            "DSC/PLEA OR TRIAL SETTING" => "PTS",
            "CASE MANAGEMENT CONFERENCE, INITIAL" => "CMC",
            "HEARING" => "HRG",
            "DSC/PRETRIAL" => "PT",
            "DECISION" => "DECISION",
            "DSC/TRIAL SETTING" => "DSC",
            "COMMUNITY CONTROL VIOLATION" => "PV",
            "REPORT, COMMERICAL CASE" => "RPT",
            "FORMAL PRE-TRIAL" => "PT",
            "TRIAL OR DISMISSAL" => "TR-DISM",
            "TELEPHONE REPORT, DEFAULT" => "TELE RPT",
            "PROBATION VIOLATION, SENTENCE" => "PV",
            "ENTRY OF DISMISSAL" => "DISM",
            "SETTLEMENT ENTRY" => "FE",
            "REPORT OR ENTRY" => "RPT",
            "REPORT, COMMERCIAL DOCKET" => "RPT",
            "PROBABLE CAUSE HEARING, COMMUNITY CONTROL VIOLATION" => "PV",
            "RE-SENTENCING" => "SNTC",
            "TRIAL, TO COURT" => "BT",
            "MOTION TO DISMISS" => "MOT DISM",
            "CASE MANAGEMENT CONFERENCE, COMMERCIAL DOCKET" => "CMC",
            "REPORT, ON MEDIATION" => "RPT",
            "GARNISHMENT HEARING" => "GARNISH",
            "DECISION DUE" => "DECISION DUE",
            "MOTION FOR JUDICIAL RELEASE" => "M. J. REL.",
            "CASE MANAGEMENT CONFERENCE, ON CROSS CLAIM" => "CMC",
            "RECEIVER'S REPORT" => "RCVR RPT",
            "FORFEITURE HEARING" => "FORFT HRG",
            "MOTIONS" => "MOT",
            "COMPETENCY HEARING" => "COMP HRG",
            "IN PROGRESS, BENCH TRIAL" => "BT",
            "TRIAL SETTING" => "TR SETTING",
            "PRE-CONVICTION CAPIAS RETURN, PLEA OR TRIAL SETTING" => "PTS",
            "MOTION FOR SUMMARY JUDGMENT, OR DISMISSAL" => "MSJ",
            "MOTION TO SUPPRESS, & JURY TRIAL" => "MOT SUPP",
            "DISCOVERY" => "DSCVY"
            );
        if ( array_key_exists ( $setting , $abreviations ) ){
            return $abreviations[ $setting ];
        }
        return $setting;
    }
    function createSummary( &$NAC, $sumStyle = "ncslj" ){
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
    	
    	if ( $NAC[ "active" ] ) {
    	    $summary = null;
    	}else{
            $summary = "INACTIVE-";
    	}
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
    				$summary = $summary .  $NAC[ "caseNumber" ];
    				break;
    			case 's':
                    $summary = $summary .  $NAC[ "setting" ];
    				break;
    			case 'l':
    				$summary = $summary .  $NAC[ "location" ];
    				break;
    			case 'j':
    				$summary = $summary .  self::lookUpJudge( $NAC[ "location" ], $NAC[ "caseNumber" ]  );
    				break;
    			case 'S':
    				$summary = $summary .  self::createAbbreviatedSetting( $NAC[ "setting" ] );
    				break;
    			default:
    				break;
    		}
            $summary .=  "--";
    	}
    	// remove last two dashes from $summary and return it.
    	return substr( $summary , 0, -2 );
    }
    function getNACDescription( $NAC ){
    	$description = "\nPlaintiffs Counsel:" . self::retrieveProsecutors( $NAC ) . 
    		"\nDefense Counsel:" . self::retrieveDefense( $NAC ) .  "\n" . self::retrieveCause( $NAC )  . 
    		"\n" . self::getHistURI( $NAC[ "caseNumber" ]) . "\n\n" . $NAC[ "plaintiffs" ] . " v. " . $NAC["defendants"] .
    		"\n\nAs of " . $this->lastUpdated;
    	
    	$description = $NAC[ "plaintiffs" ] . " v. " . $NAC[ "defendants" ] . "\n\n" . self::getHistURI( $NAC[ "caseNumber" ]) . "\n\nAs of " . $this->lastUpdated;
        
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
	function usedDB( &$attoneyId ){
	    return $this->usedDB[ $attoneyId ];
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
}

?>