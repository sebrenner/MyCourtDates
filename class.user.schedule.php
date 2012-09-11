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
require_once("libs/simplehtmldom_1_5/simple_html_dom.php");

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
    protected $source = null;
    protected $activeNACs = 0;
    protected $myNow;
    protected $scrapeStatus = 'none';

    // Method Definitions
    function __construct($userBarNumber, $verbose, $rangeFlag = self::PAST_6)
    {
        $this->verbose = $verbose;
        set_time_limit ( 90 );
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        $this->myNow = new DateTime();
        // set custom "now" for testing
        // $this->myNow = new DateTime(date('Y-m-d H:i:s', strtotime('last friday +6hours')));        
        // echo "\t" . $this->myNow->format('Y-m-d H:i:s') . "\n";
        $this->userBarNumber = $userBarNumber;
        $this->timeFrame = $rangeFlag;
        if (self::isScheduleStale()){
            self::scrapeClerkSite();
            // if schedule is stale then site will be scraped and 
            // $this->event will be populated.  This takes care of serving
            // up a clanendar that show the errors, e.g., no event, invalid bar number.
        }else{
            self::selectAttorneyName();
            if ($this->scrapeStatus=='success') {
                self::selectEventsFromDB();
            }else{
                $this->events=self::returnErrorSchedule($this->scrapeStatus);
            }
        }
	}
    protected function scrapeClerkSite()
    {
        if ($this->verbose) echo  __METHOD__ . "\n";
        // Get html from site.
        $htmlStr = self::getClerkSiteHtml();
        if ($this->verbose){
            echo "\tposition returned for invalid: ". strpos($htmlStr, 'The attorney ID that you entered was invalid.') ."\n";
            echo "\tposition returned for no sched: ". strpos($htmlStr, 'No schedules were found for the specified attorney.') ."\n";
        }
        if (strpos($htmlStr, 'The attorney ID that you entered was invalid.')) {
            if ($this->verbose){
              echo "\n\n\tThe attorney ID that you entered was invalid.\n";
            }
            $this->events = self::returnErrorSchedule('invalid');
            $this->scrapeStatus='invalid'; 
            self::storeVintage();
            return false;
        }elseif (strpos($htmlStr, 'No schedules were found for the specified attorney.')) {
            if ($this->verbose){
              echo "\n\n\tNo schedules were found for the specified attorney.\n";
            }
            self::removeCruft($htmlStr);  // Pass by ref. modifies $htmlStr
            self::extractAttorneyName($htmlStr);
            self::storeAttorneyName();
            $this->events = self::returnErrorSchedule('emptySchedule');
            $this->scrapeStatus='emptySchedule';
            self::storeVintage();
            return false;
        }else{
            if ($this->verbose){
              echo "\tA valid schedule was found.\n";
            }
            $this->scrapeStatus='success';
            self::removeCruft($htmlStr);  // Pass by ref. modifies $htmlStr
            self::extractAttorneyName($htmlStr);
            $this->events =  self::parseNACTables($htmlStr);
            self::storeEvents();
            self::storeVintage();
            self::storeAttorneyName();
            return true;
        }
    }
    protected function selectEventsFromDB()
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
        switch ($this->timeFrame) {
            case self::FUTURE:
                $earliestDate=date('Y-m-d', strtotime('today'));
                break;
            case self::PAST_YR:
                $earliestDate=date('Y-m-d', strtotime('-1year'));
                break;
            case self::PAST_6:
            default:
                $earliestDate=date('Y-m-d', strtotime('-6months'));
                break;
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
                    WHERE attorneyId = '$this->userBarNumber'
                    AND timeDate >= '$earliestDate'";
        if ($this->verbose) {echo "\t$query\n";}
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
    protected function selectAttorneyName()
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
        $query =   "SELECT  fName, 
                            mName, 
                            lName
                    FROM user_tbl
                    WHERE userBarNumber = \""
                    . $this->userBarNumber
                    . "\"";
        if ($this->verbose) {echo "\t$query\n";}
        $result = mysql_query($query, $dbh) or die(mysql_error());
        // $result = mysql_unbuffered_query( $query ) or die( mysql_error() );
        // Loop through results and add to $events
        while( $row = mysql_fetch_array( $result, MYSQL_ASSOC )){
            // echo "\tHere we are in the loop.\n";
            // print_r($row);
            $this->lName = $row['lName'];
            $this->fName = $row['fName'];
            $this->mName = $row['mName'];
        }
        mysql_close($dbh);
        return false;
    }
    protected function logCurl($startRequest, $info, $method)
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
        $info['requestTime']=$startRequest->format('Y-m-d H:i:s');
        echo "\n";
        print_r($info). "\n";
        $query="insert into `todayspo_MyCourtDates`.`curlLog` ( 
                        `download_content_length`, 
                        `url`, 
                        `request_size`, 
                        `size_upload`, 
                        `filetime`, 
                        `redirect_count`, 
                        `redirect_time`, 
                        `pretransfer_time`, 
                        `total_time`, 
                        `namelookup_time`, 
                        `speed_download`, 
                        `speed_upload`, 
                        `http_code`, 
                        `upload_content_length`,  
                        `starttransfer_time`, 
                        `requestTime`, 
                        `size_download`, 
                        `ssl_verify_result`, 
                        `header_size`, 
                        `content_type`, 
                        `connect_time`, 
                        `redirect_url`,
                        `method`
                        )
                VALUES ("
                    . "'" . $info['download_content_length'] . "',"
                    . "'" . $info['url'] . "',"
                    . "'" . $info['request_size'] . "',"
                    . "'" . $info['size_upload'] . "',"
                    . "'" . $info['filetime'] . "',"
                    . "'" . $info['redirect_count'] . "',"
                    . "'" . $info['redirect_time'] . "',"
                    . "'" . $info['pretransfer_time'] . "',"
                    . "'" . $info['total_time'] . "',"
                    . "'" . $info['namelookup_time'] . "',"
                    . "'" . $info['speed_download'] . "',"
                    . "'" . $info['speed_upload'] . "',"
                    . "'" . $info['http_code'] . "',"
                    . "'" . $info['upload_content_length'] . "',"
                    . "'" . $info['starttransfer_time'] . "',"
                    . "'" . $info['requestTime'] . "',"
                    . "'" . $info['size_download'] . "',"
                    . "'" . $info['ssl_verify_result'] . "',"
                    . "'" . $info['header_size'] . "',"
                    . "'" . $info['content_type'] . "',"
                    . "'" . $info['connect_time'] . "',"
                    . "'" . $info['redirect_url'] . "',"
                    . "'" . $method . "' );";
        if ($this->verbose) { echo   "\n$query\n";}
        $result = mysql_query( $query, $dbh ) or die( mysql_error() );
        mysql_close( $dbh );
    }
	protected function getClerkSiteHtml()
    {
        if ($this->verbose) echo  __METHOD__ . "\n";
        $URI = 'http://www.courtclerk.org/attorney_schedule_list_print.asp?court_party_id='
        . $this->userBarNumber;
        switch ($this->timeFrame) {
            case self::PAST_YR:
                $URI .= '&date_range=prior12';
                break;
            case self::FUTURE:
                $URI .= '&date_range=future';
                break;
            case self::PAST_6:
            default:
                $URI .= '&date_range=prior6';
                break;
	    }
	    $defaults = array( 
            CURLOPT_URL => $URI,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 22,
            CURLOPT_HEADER => 0, 
            CURLOPT_FRESH_CONNECT => 1, 
            CURLOPT_FORBID_REUSE => 1, 
        ); 
        $ch = curl_init(); 
        curl_setopt_array($ch, $defaults);
        $startRequest = new DateTime();
        if(! $htmlScrape = curl_exec($ch)) 
        { 
           // Couldn't scrape clerk's site.  What do we do?
           trigger_error("The clerk's site couldn't be scraped.  See class.user.schedule.php.");
        } 
        $info = curl_getinfo($ch);
        curl_close($ch);
        self::logCurl($startRequest, $info, __METHOD__);
        $this->source = "Clerk's site";
        return $htmlScrape;
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
	    
        // If there is no vintage return true (stale)
        if (!self::vintage()) return true;
        
        $vintageAgeMinutes = ($this->myNow->getTimestamp() - $this->dBVintage->getTimestamp()) / 60;
        if ($this->verbose) {
            printf("\tInterval: %.1f minutes.\n", $vintageAgeMinutes);
        }
        
        // If vintage less than 90 minutes ago, return false (not stale)
        if ($vintageAgeMinutes < 90){
            if ($this->verbose) {
                printf("\tThe db is pretty fresh. It was updated only %.1f mintues ago.\n", $vintageAgeMinutes);
            }
            return false;
        }

        // If vintage after 4:00 today, return false (not stale)
        $fourPMToday = new DateTime(date('Y-m-d H:i:s', strtotime('Today +16hours')));        
        if ($this->dBVintage->getTimestamp() > $fourPMToday->getTimestamp()) {
            if ($this->verbose) {
                echo "\tThe db data was updated after 4:00 today.\n";
            }
            return false;
        }
	    
        // If vintage after 4:00 yesterday and current time is before 8:00 today, return false (not stale)
        $fourPMYesterday = new DateTime(date('Y-m-d H:i:s', strtotime('Yesterday +16hours')));
        $eightAMToday = new DateTime(date('Y-m-d H:i:s', strtotime('Today +8hours')));
        if ($this->dBVintage->getTimestamp() > $fourPMYesterday->getTimestamp() &&
                $this->myNow->getTimestamp() < $eightAMToday->getTimestamp() ) {
            if ($this->verbose) {
                echo "\tThe db data was updated after 4:00 yesterday and it is before 8:00am today.\n";
            }
            return false;
        }
        
        // If today is weekend and vintage is after 4:00 last Friday, return false (not stale)
        $lastFriday = new DateTime(date('Y-m-d H:i:s', strtotime('Last friday +16hours')));
        
        if (self::isWeekend() && 
                $this->dBVintage->getTimestamp() > $lastFriday->getTimestamp()) {
            if ($this->verbose) {
                echo "\tIt is the weekend and the db data was updated after 4:00 Friday.\n";
            }            
            return false;
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
			if ($NAC['active']) {
				$this->activeNACs += 1;
				// Count cases and the NACs/case. If key doesn't exist create it before incrementing.
				if (array_key_exists ("caseNumber" , $NAC)) $this->activeCases[ $NAC['caseNumber'] ] = 0;
				$this->activeCases[ $NAC['caseNumber'] ] += 1;
			}
			
			// Add the NAC to the temp array of NACs.
			array_push($tempNACs, $NAC);
		}   //  End of the while loop
		return $tempNACs;
	}
	protected function isWeekend()
	{
	    if ($this->verbose) echo  __METHOD__ . "\n";
        return ($this->myNow->format('N') >= 6);
    }
	protected function convertNACtoArray(&$NACTable)
	{
	    if ($this->verbose) echo  __METHOD__ . "\n";
		$NAC= array();
	    
        // ========================================
        // = First add NAC_tbl normalized fields. =
        // ========================================
		$NAC['attorneyID'] = $this->userBarNumber;
		
		//  Get date and Time; create dateTime object
		$NAC['caseNumber'] = $NACTable->find('td', 2)->find('a', 0)->innertext;
        
        $date = $NACTable->find('td', 0)->innertext;
		$date = substr($date, 5);	
		$time = $NACTable->find('td', 1)->innertext;
		$time = substr($time, 5);
		$NAC['timeDate'] =  date ("Y-m-d H:i:s", strtotime($date . $time));
		
	    //	Determine if NAC is active
		$active = substr($NACTable->find('td', 4)->innertext, 8);
		$NAC['active'] = false;
		if ($active == "A"){ $NAC["active"] = true; }
		
		$location = $NACTable->find('td', 5)->innertext;
		$NAC['location'] = substr($location, 10);
		
		$setting = 	$NACTable->find('td', 6)->innertext;
		$NAC['setting']  = substr($setting, 13);

		$caption = $NACTable->find('td', 3)->innertext;
    	$vs = strpos($caption , "vs.");
    	$NAC ['plaintiffs']   = substr ($caption, 9 , $vs - 10);
    	$NAC ['defendants']   = substr ($caption , $vs + 4);
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
        $query =   "SELECT  addOnBarNumber, error, MAX(vintage) AS vintage
                    FROM    addOnBarNumber_tbl
                    WHERE   addOnBarNumber = '$this->userBarNumber'";
        if ($this->verbose) { echo   "\t$query\n";}
        $result = mysql_query($query, $dbh) or die(mysql_error());
        $row = mysql_fetch_assoc($result);
        mysql_close($dbh);
        if ($row["vintage"] == null) {
            $this->dBVintage = null;
            if ($this->verbose) { 
                echo "\tDb does not contain vintage\n";
            }
            return false;
        }else{
            // echo "\trow[vintage]:" . $row['vintage'] ."\n";
            // $this->dBVintage = new DateTime(strtotime($row['vintage']));
            $this->dBVintage = new DateTime($row['vintage']);
            $this->scrapeStatus = $row['error'];
            if ($this->verbose) { 
                echo "\tdBVintage currently is set to: "
                . $this->dBVintage->format('Y-m-d H:i:s')
                . ".\n";

            }
            return true;
        }
    }
    protected function storeAttorneyName()
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
        $query =   "INSERT INTO user_tbl (userBarNumber, fName, mName, lName) 
                    VALUES ('$this->userBarNumber', 
                            '$this->fName',
                            '$this->mName',
                            '$this->lName')
                            ON DUPLICATE KEY 
                            UPDATE  fName = VALUES(fName), 
                                    mName = VALUES(MName), 
                                    lName = VALUES(LName)";
        if ($this->verbose) { echo "\t$query\n";}
        $result = mysql_query($query, $dbh) or die(mysql_error());
        mysql_close($dbh);
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
        $now = new dateTime();
        $vintage = $now->format('Y-m-d H:i:s');
        $query =   "INSERT INTO addOnBarNumber_tbl (userBarNumber, addOnBarNumber, vintage, error) 
                    VALUES ('$this->userBarNumber', 
                            '$this->userBarNumber',
                            '$vintage',
                            '$this->scrapeStatus' ) 
                    ON DUPLICATE KEY 
                    UPDATE vintage = '$vintage'";
        if ($this->verbose) { echo   "\t$query\n";}
        $result = mysql_query($query, $dbh) or die(mysql_error());
        mysql_close($dbh);
    }
    protected function returnErrorSchedule($error)
    {
        if ($this->verbose) { echo  __METHOD__ . "\n";}
        $tempNACs=array();
        $NAC= array();
        $NAC['attorneyID'] = $this->userBarNumber;
        $NAC['active'] = true;
        $NAC['location'] = "No valid location";
        $NAC['caseNumber']="ERROR";
        // Create three events to warn user
        for ($i=-1; $i <3 ; $i++) { 
            $NAC['timeDate'] =  date ("Y-m-d H:i:s", strtotime("now +70 minutes +$i days"));
            switch ($error) {
                case 'invalid':
                    $NAC ['setting']  = "CourtClerk.org indicates $this->userBarNumber is not a valid Attorney Id.";
                    $NAC ['plaintiffs']   = "Confirm Attorney Id at http://www.courtclerk.org/attorney_schedule.asp.";
                    $NAC ['defendants']   = "Visit http://MyCourtDates.com for more information.";
                    break;
                case 'emptySchedule':
                    $NAC ['setting']  = "No events listed for $this->userBarNumber.";
                    $NAC ['plaintiffs']   = "Confirm bar number on http://courtclerk.org.";
                    $NAC ['defendants']   = "Visit http://MyCourtDates.com for more information.";
                    break;
                default:
                    # code...
                    break;
            }
            array_push($tempNACs, $NAC);
		}
		return $tempNACs;
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
        if ($this->verbose) { echo  __METHOD__ . "\n";}
        return $this->fName;
    }
    public function getLName()
    {
        if ($this->verbose) { echo  __METHOD__ . "\n";}
        return $this->lName;
    }
    public function getMName()
    {
        if ($this->verbose) { echo  __METHOD__ . "\n";}
        return $this->mName;
    }
    public function getEvents()
    {
        // print_r($this->events);
        if ($this->verbose) { echo  __METHOD__ . "\n";}
        return $this->events;
    }
    public function getActiveNACCount()
    {
        if ($this->verbose) { echo  __METHOD__ . "\n";}
        return $this->activeNACs;
    }
    public function getScrapeStatus()
    {
        if ($this->verbose) { echo  __METHOD__ . "\n";}
        return $this->scrapeStatus;
    }
    public function getNACCount()
    {
        if ($this->verbose) { echo  __METHOD__ . "\n";}
        return sizeof ($this->activeNACs);
    }
    public function getInactiveNACCount()
    {
        if ($this->verbose) { echo  __METHOD__ . "\n";}
        return self::getNACCount() - $this->activeNACs;
    }
}

// $attorneyIds[] = "PP68519";      //  BURROUGHS/KATIE/M
// $attorneyIds[] = "P68519";       //  BURROUGHS/KATIE/M
// $attorneyIds[] = "P77000";       //  HEile
// $attorneyIds[] = "68519";        //  BURROUGHS/KATIE/M
// $attorneyIds[] = "71655";        //  RUBENSTEIN/SCOTT/A
// $attorneyIds[] = "74457";       //  MARY JILL DONOVAN
// $attorneyIds[] = "76220";        //  JACKSON/CHRISTOPHER/L
// $attorneyIds[] = "67668";        //  HARRIS/RODNEY/J
// $attorneyIds[] = "dsafes";       //  Bad attorney id
// $attorneyIds[] = "83943";        //  Empty Schedule
// // 
// foreach ($attorneyIds as $attorneyId) {
//     echo "\n\n****New schedule $attorneyId***\n";
//     $a = new barNumberSchedule ($attorneyId, true, 0);
//     echo $a->getFullName() . "\n";
//     echo "Schedule status: " . $a->getScrapeStatus(). "\n";
//     // print_r($a->getEvents());
// }

?>
