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
require_once("simplehtmldom_1_5/simple_html_dom.php");

// This code declares the time zone
ini_set('date.timezone', 'America/New_York');
date_default_timezone_set ('America/New_York');


class barNumberSchedule
{
//     The Clerk's site provides three date ranges:
//     future only (default) -- date_range=future
//     future and past 6 months -- date_range=prior6
//     future and past year -- date_range=prior12

    // property declaration
    const LOGIC = 0;
    const DB    = 1;
    const CLERK = 2;
    const FUTURE = 0;
    const PAST_6 = 1;
    const PAST_YR = 2;
    
    protected $verbose = false;
    protected $userBarNumber = null;
    protected $fName = null;
	protected $lName = null;
	protected $mName = null;
	protected $events = array();
	protected $dBVintage = null;
	protected $timeFrame = null;
	protected $sourceFlag = null;
	protected $source = null;
	protected $activeNACs = 0;
	
	// Method Definitions
	function __construct($userBarNumber, $verbose, $sourceFlag = self::LOGIC, $rangeFlag = self::FUTURE)
	{
        $this->verbose = $verbose;
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        // echo "\tverbose:$this->verbose\n";
        $this->userBarNumber = $userBarNumber;
        $this->timeFrame = $rangeFlag;
        $this->sourceFlag = $sourceFlag;
        // echo "\tuserBarNumber: " . $this->userBarNumber ."\n";
        switch ($this->source) {
            case self::DB:
                self::selectFromDB();
                break;
            case self::CLERK:
                self::parseHTML();
                break;
            case self::LOGIC:
            default:
                if (self::isScheduleStale()){
                    self::parseHTML();
                }else{
                    self::selectFromDB();
                }
                break;
        }
        
        // echo "\tThe barNumberSchedule was successully __constructed using $this->source:\n";
        // echo "\t\t" . self::getFullName() ."\n";
        // echo "\t\tTotal events: " . self::getNACCount() ."\n";
        // echo "\t\tActive events: " . self::getActiveNACCount() ."\n";
        // echo "\t\tInactive events: " . self::getInactiveNACCount() ."\n";
	}
    protected function parseHTML()
    {
        if ($this->verbose) echo  __METHOD__ . "\n";
        // Get html from site.
        $htmlStr = self::queryClerkSite();
        self::removeCruft($htmlStr);  // Pass by ref. modifies $htmlStr
        self::extractAttorneyName($htmlStr);
        // echo "\t"
        //     . self::getFName() . " "
        //     . self::getMName() . " "
        //     . self::getLName() . "\n";
        $this->events =  self::parseNACTables($htmlStr);
        self::storeEvents();
        self::storeVintage();
    }
    protected function selectFromDB()
    {
        if ($this->verbose) echo  __METHOD__ . "\n";
        include("passwords/todayspo_MyCourtDates.php");
        try{
            $dbh = mysql_connect($dbHost, $dbReader, $dbReaderPassword) or die(mysql_error());
            mysql_select_db($db) or die(mysql_error());    
        }
        catch(PDOException $e){
            echo $e->getMessage();
            echo "<br><br>Database $db -- NOT -- loaded successfully .. ";
            die("<br><br>Query Closed !!! $error");
        }
        $query =   "SELECT  active, 
                            caseNumber, 
                            timeDate, 
                            setting, 
                            location, 
                            plaintiffs, 
                            defendants, 
                            attorneyId
                    FROM NAC_tbl
                    WHERE attorneyId = \""
                    . $this->userBarNumber
                    . "\"";
        if ($this->verbose) { echo   "\n$query\n";}
        $result = mysql_query($query, $dbh) or die(mysql_error());
        // $result = mysql_unbuffered_query( $query ) or die( mysql_error() );
        // Loop through results and add to $events
        while( $row = mysql_fetch_array( $result, MYSQL_ASSOC )){
            
            // echo "\tHere we are in the loop.\n";
            // print_r($row);
            array_push ($this->events, $row);
        }
        // print_r($this->events);
        mysql_close($dbh);
        $this->source = "database";
        return false;
    }
	protected function queryClerkSite()
	{
	    if ($this->verbose) echo  __METHOD__ . "\n";
	    switch ($this->timeFrame) {
	       case self::PAST_YR:
	           return file_get_contents(
               "http://www.courtclerk.org/attorney_schedule_list_print.asp?court_party_id="
               . $this->userBarNumber . "&date_range=prior12");
	           break;
	       case self::FUTURE:
	           return file_get_contents(
               "http://www.courtclerk.org/attorney_schedule_list_print.asp?court_party_id="
               . $this->userBarNumber . "&date_range=future");
	           break;
	       case self::PAST_6:
	       default:
	           return file_get_contents(
               "http://www.courtclerk.org/attorney_schedule_list_print.asp?court_party_id="
               . $this->userBarNumber . "&date_range=prior6");
	           break;
	    }
        $this->source = "Clerk's site";
	}
	protected function removeCruft(&$htmlStr)
	{
	    if ($this->verbose) echo  __METHOD__ . "\n";
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
    	    $htmlStr = str_replace($key, $value, $htmlStr, $count);
            // echo "Replaced $count instances of \"$key\"\n";
    	    while (strpos($htmlStr, "  ")) {
        	    $htmlStr = str_replace("  ", " ", $htmlStr, $count);
                // echo "Removed $count double spaces.\n";
            }
        }
    }
	protected function isScheduleStale()
	{
	    if ($this->verbose) echo  __METHOD__ . "\n";
        $now = new DateTime();
        self::vintage();
        // echo "\tIn " . __METHOD__ . ". this->dBVintage:\t";
        // echo gettype($this->dBVintage);        
        // echo "\t" . $this->dBVintage->format('Y-m-d H:i:s') . "\n";
        
        if ($this->dBVintage === null)
        {
            if ($this->verbose) { echo "\t\tin isStale: self::vintage returned === null.\n";
            echo "\tIn if null in " . __METHOD__ . ". this->dBVintage:\t";
            echo gettype($this->dBVintage);
            echo "\tVintage in ". __METHOD__ . $this->dBVintage->format('Y-m-d H:i:s') . "\n";
            echo "\t\t$this->dBVintage->format('Y-m-d H:i:s')\n";
        }
            // Set the vintage to now and return true, i.e., schedule is stale b/c this 
            // is the first request for this attorney id.            
            $this->dBVintage = $now;
            return true;
        }
        
        if ($this->verbose) { 
            echo "\tschedVintage after converting to dateTime: " 
                .  $this->dBVintage->format('Y-m-d H:i:s') . "\n";
            // echo $now->getTimestamp() . "\n";
            // echo $this->dBVintage->getTimestamp(). "\n";
            echo "\t" .  ($now->getTimestamp() - $this->dBVintage->getTimestamp()) . "\n";
        }
        $elapsedMinutes = ($now->getTimestamp()  - $this->dBVintage->getTimestamp()) / 60;
        
        if ($this->verbose) {        
            echo "\tInterval: $elapsedMinutes minutes.\n";
            echo "\tIs the vintage less than 90 minutes ago?\n";
        }
        // ============================================
        // = Is the vintage less than 90 minutes ago? =
        // ============================================
        if ($elapsedMinutes < 90)
        {
            if ($this->verbose) {
                echo "\tThe db is pretty fresh. It was updated only "
                . $elapsedMinutes
                . " mintues ago.\n";
            }
            return false;
        }
        if ($this->verbose) { echo " No.\n\tIs the vintage past 4:00 today?\n";}
        // ===================================
        // = Is the vintage past 4:00 today? =
        // ===================================
        $today4 = new DateTime(strtotime("Today at 4:00 pm"));
        $intervalSince4 = $this->dBVintage->diff($today4);
        $elapsedMinutesFromFourToday =  ($this->dBVintage->getTimestamp() - $today4->getTimestamp()) / 60;
        if ($this->verbose) { echo "\tMinutes elpased since 4:00 today (a negative # means that the the schedule was not updated after 4:00 today): $elapsedMinutesFromFourToday.\n";}
        if ($elapsedMinutesFromFourToday > 0) {
            // echo "\tIt is NOT the weekend and the db data 
                    // was updated after 4:00 today.\n";
            // echo "\t$this->dBVintage->format('Y-m-d H:i:s')  is greater than " 
                // . strtotime("Today at 4:00 pm"). ".\n";       
            return False;
        }
        if ($this->verbose) { echo " No.\n\tIs it the weekend with a vintage past 4:00 Friday?\n";}
        // ======================================
        // = Is vintage after 4:00 last Friday? =
        // ======================================        
        $lastFriday = new DateTime(strtotime("Last friday at 4:00 pm"));
        $elapsedMinutesSinceFridayAtFour = (
            $this->dBVintage->getTimestamp() - $lastFriday->getTimestamp()) / 60;
        if ($this->verbose) { 
            echo "\tMinutes elapsed since last Friday at 4:00: " . $elapsedMinutesSinceFridayAtFour. "\n";
            echo "is today the weekend AND vitage is greater than Friday at 4?";
            echo "\tToday is the weekend?" . self::isWeekend($now->format('Y-m-d H:i:s'));
            echo "\tElapsed minutes since lFriday at 4:00 ?" . $elapsedMinutesSinceFridayAtFour > 0;
        }
        if (self::isWeekend($now->format('Y-m-d H:i:s')) && $elapsedMinutesSinceFridayAtFour > 0) {
            if ($this->verbose) { echo "\tIt is the weekend and the db"
            . "data was updated after 4:00 on Friday.\n";}
            return false;
        }
        if ($this->verbose) {
            echo "\tThe schedule for $this->userBarNumber is stale.\n";
            echo "\tIt is more that 90 minutes old.  It was created after 4:00 today.  And today is not a weekend with a schedule created after 4:00 on Friday.\n";
        }
        return true;
	}
    protected function extractAttorneyName($htmlStr)
    {
        if ($this->verbose) echo  __METHOD__ . "\n";
	    $attyNameIndexStart = strpos ($htmlStr , "Attorney Name:") + 48;  // offset to get to actual name
        $attyNameIndexEnd = strpos ($htmlStr , "Attorney ID:") - 18; // offset to get to end of actual name
        $attyNameLength = $attyNameIndexEnd - $attyNameIndexStart;
        // echo "\nThe attorney name starsts at $attyNameIndexStart.  It ends at $attyNameIndexEnd.\n It is $attyNameLength char long.\n";
        $attyName = substr ($htmlStr , $attyNameIndexStart, $attyNameLength);
        // echo "The attyNameTableStr: $attyName\n";
		$attyName = explode("/" , $attyName);
		if (array_key_exists(0, $attyName)) $this->lName = $attyName[0];
		if (array_key_exists(1, $attyName)) $this->fName = $attyName[1];
		if (array_key_exists(2, $attyName)) $this->mName = $attyName[2];
        // echo "Lname: " . $this->lName ;
        // echo "fname: " . $this->fName;
        // echo "Mname: " . $this->mName;
	}
    protected function parseNACTables(&$htmlStr)
    {
        if ($this->verbose) echo  __METHOD__ . "\n";
        $tempNACs = array();
 	    $index = 0;
 	    while (True) {
            $NACTableIndexStart = strpos ($htmlStr , "<table id=\"NAC\">", $index);
            if ($NACTableIndexStart === false){ break; }
            $NACTableIndexEnd = strpos (
                $htmlStr , "</table>", $NACTableIndexStart);
            $NACTableLength = $NACTableIndexEnd - $NACTableIndexStart + 8;
            $NACTableStr = substr (
                $htmlStr , $NACTableIndexStart, $NACTableLength);
            // echo "NacTable starts at $NACTableIndexStart, ends at $NACTableIndexEnd, and is $NACTableLength characters in length.\n Here is what is extracted:\n\n$NACTableStr\n\n";
            $index = $NACTableIndexStart + 1; // Advance index so that next strpos will find next table
            
            // Convert html string to DOM obj.
            $NACTableObj = str_get_html($NACTableStr);
                        
            // Convert the NAC table into an indexed array.
			$NAC = self::convertNACtoArray($NACTableObj);

			// Count active NAC and case numbers.
			if ($NAC[ "active" ]) {
				$this->activeNACs += 1;
				// Count cases and the NACs/case. If key doesn't exist create it before incrementing.
				if (array_key_exists ("caseNumber" , $NAC)) $this->activeCases[ $NAC[ "caseNumber" ] ] = 0;
				$this->activeCases[ $NAC[ "caseNumber" ] ] += 1;
			}
			
			// Add the NAC to the temp array of NACs.
			array_push($tempNACs, $NAC);
		}   //  End of the while loop
		return $tempNACs;
	}
	protected function isWeekend($date)
	{
	    if ($this->verbose) echo  __METHOD__ . "\n";
        return (date('N', strtotime($date)) >= 6);
    }
	protected function convertNACtoArray(&$NACTable)
	{
	    if ($this->verbose) echo  __METHOD__ . "\n";
		$NAC= array();
	    
        // ========================================
        // = First add NAC_tbl normalized fields. =
        // ========================================
		$NAC[ "attorneyID" ] = $this->userBarNumber;
		
		//  Get date and Time; create dateTime object
		$NAC[ "caseNumber" ] = $NACTable->find('td', 2)->find('a', 0)->innertext;
        
        $date = $NACTable->find('td', 0)->innertext;
		$date = substr($date, 5);	
		$time = $NACTable->find('td', 1)->innertext;
		$time = substr($time, 5);
		$NAC[ "timeDate" ] =  date ("Y-m-d H:i:s", strtotime($date . $time));
		
	    //	Determine if NAC is active
		$active = substr($NACTable->find('td', 4)->innertext, 8);
		$NAC[ "active" ] = false;
		if ($active == "A"){ $NAC["active"] = true; }
		
		$location = $NACTable->find('td', 5)->innertext;
		$NAC[ "location" ] = substr($location, 10);
		
		$setting = 	$NACTable->find('td', 6)->innertext;
		$NAC[ "setting" ]  = substr($setting, 13);

		$caption = $NACTable->find('td', 3)->innertext;
    	$vs = strpos($caption , "vs.");
    	$NAC [ "plaintiffs" ]   = substr ($caption, 9 , $vs - 10);
    	$NAC [ "defendants" ]   = substr ($caption , $vs + 4);
        // print_r($NAC);
		return $NAC;
	}
    protected function storeEvents()
    {
        if ($this->verbose) echo  __METHOD__ . "\n";
        include("passwords/todayspo_MyCourtDates.php");
        // Connect to the db
        try
        {
            $dbh = mysql_connect( $dbHost, $dbAdmin, $dbAdminPassword ) or die(mysql_error());
            mysql_select_db( $db ) or die( mysql_error() );
        }
        catch(PDOException $e)
        {
            echo $e->getMessage();
            echo "<br><br>Database $db -- NOT -- loaded successfully .. ";
            die( "<br><br>Query Closed !!! $error");
        }
        $values = "";
        // Build values list
        foreach ($this->events as $event)
        {
            $values .= "("
                . "'" . $event['active'] . "', "
                . "'" . $event['caseNumber'] . "', "
                . "'" . $event['timeDate'] . "', "
                . "'" . $event['setting'] . "', "
                . "'" . $event['location'] . "', "
                . "'" . $event['plaintiffs'] . "', "
                . "'" . $event['defendants'] . "', "
                . "'" . $event['attorneyID']. "' "
                . "),\n";
        }
        $values = substr($values, 0 , strlen($values)-2);
        $query =   "INSERT INTO NAC_tbl ( active, caseNumber, timeDate, 
                                    setting, location, plaintiffs, 
                                    defendants, attorneyId ) 
                    VALUES 
                        $values
                    ON DUPLICATE KEY UPDATE
                    active = VALUES(active)";
        if ($this->verbose) { echo   "\n$query\n";}
        $result = mysql_query( $query, $dbh ) or die( mysql_error() );
        mysql_close( $dbh );
    }
    protected function vintage()
    {
        if ($this->verbose) echo  __METHOD__ . "\n";
        include("passwords/todayspo_MyCourtDates.php");
        // Query the db for additional ids and append them to the array.
        try{
            $dbh = mysql_connect($dbHost, $dbReader, $dbReaderPassword) or die(mysql_error());
            mysql_select_db($db) or die(mysql_error());    
        }
        catch(PDOException $e){
            echo $e->getMessage();
            echo "<br><br>Database $db -- NOT -- loaded successfully .. ";
            die("<br><br>Query Closed !!! $error");
        }
        $query =   "SELECT  addOnBarNumber, MAX(vintage) AS vintage
                    FROM    addOnBarNumber_tbl
                    WHERE   addOnBarNumber = '$this->userBarNumber'";
        if ($this->verbose) { echo   "\n$query\n";}
        $result = mysql_query($query, $dbh) or die(mysql_error());
        $row = mysql_fetch_assoc($result);
        mysql_close($dbh);
        if ($row["vintage"] == null) {
            $this->dBVintage = null;
            if ($this->verbose) { echo "\tDb does not contain vintage " . __METHOD__ . "\n";} 
        }else{
        $this->dBVintage = new DateTime( $row["vintage"] );}
    }
    protected function storeVintage()
    {
        if ($this->verbose) { echo  __METHOD__ . "\n";}
        include("passwords/todayspo_MyCourtDates.php");
        try{
            $dbh = mysql_connect($dbHost, $dbAdmin, $dbAdminPassword) or die(mysql_error());
            mysql_select_db($db) or die(mysql_error());    
        }
        catch(PDOException $e){
            echo $e->getMessage();
            echo "<br><br>Database $db -- NOT -- loaded successfully .. ";
            die("<br><br>Query Closed !!! $error");
        }
        echo "\tVintage in self::storeVintage" . $this->dBVintage->format('Y-m-d H:i:s') . "\n";
        $vintage = $this->dBVintage->format('Y-m-d H:i:s');
        $query =   "INSERT INTO addOnBarNumber_tbl (userBarNumber, addOnBarNumber, vintage) 
                    VALUES ('$this->userBarNumber', 
                            '$this->userBarNumber',
                            '$vintage' ) 
                    ON DUPLICATE KEY 
                    UPDATE vintage = '$vintage'";
        if ($this->verbose) { echo   "\n$query\n";}
        $result = mysql_query($query, $dbh) or die(mysql_error());
        mysql_close($dbh);
    }
    public function getFullName()
    { 
        if ($this->verbose) { echo  __METHOD__ . "\n";}
        $fullName = $this->fName . " ";
        if (empty($this->mName)){
            $fullName .= $this->lName;
            return $fullName;
        }
        $fullName .= $this->mName . " " . $this->lName;
        return $fullName;
    }
    public function getFName()
    {
        return $this->fName;
    }
    public function getLName()
    {
        return $this->lName;
    }
    public function getMName()
    {
        return $this->mName;
    }
    public function getEvents()
    {
        // print_r($this->events);
        return $this->events;
    }
    public function getActiveNACCount()
    {
        return $this->activeNACs;
    }
    public function getNACCount()
    {
        return sizeof ($this->activeNACs);
    }
    public function getInactiveNACCount()
    {
        return self::getNACCount() - $this->activeNACs;
    }
}

?>