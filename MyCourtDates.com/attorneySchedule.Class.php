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
    	$vs = strpos( $caption , "vs." );
    	$NAC [ "plaintiffs" ]   = substr ( $caption, 9 , $vs - 10 );
    	$NAC [ "defendants" ]   = substr ( $caption , $vs + 4 );
        		
		//	Determine if NAC is active
		$active = substr( $NACTable->find('td', 4 )->innertext, 8);
		$NAC[ "active" ] = false;
		if ( $active == "A"){ $NAC["active"] = true; }
		
		$location = $NACTable->find('td', 5 )->innertext;
		$NAC[ "location" ] = substr($location, 10);
		
		$setting = 	$NACTable->find('td', 6 )->innertext;
		$NAC[ "setting" ]  = substr( $setting, 13 );
        // print_r($NAC);
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
    function lookUpJudge( &$location, &$caseNum  ){
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

        // $c = new  CaseInfo( &$caseNum );
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
    				$summary = $summary .  $NAC[ "caseNum" ];
    				break;
    			case 's':
                    $summary = $summary .  $NAC[ "setting" ];
    				break;
    			case 'l':
    				$summary = $summary .  $NAC[ "location" ];
    				break;
    			case 'j':
    				$summary = $summary .  self::lookUpJudge( $NAC[ "location" ], $NAC[ "caseNum" ]  );
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
    		"\n" . self::getHistURI( $NAC[ "caseNum" ]) . "\n\n" . $NAC[ "plaintiffs" ] . " v. " . $NAC["defendants"] .
    		"\n\nAs of " . $this->lastUpdated;
    	
    	$description = $NAC[ "plaintiffs" ] . " v. " . $NAC[ "defendants" ] . "\n\n" . self::getHistURI( $NAC[ "caseNum" ]) . "\n\nAs of " . $this->lastUpdated;
        
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
}

?>