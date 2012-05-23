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

// This class is only needed for testing.
require_once("class.user.php");

// This library exposes objects and methods for creating ical files.
require_once("libs/iCalcreator-2.12/iCalcreator.class.php");

/**
* This class defines an object for creating an structured data feeds for attorney calendars.
* It supports ICS files, xml, and JSON data.
*/
class ICS
{
    //  Class variables.
    protected $verbose = null;
    protected $v = null;
    protected $attorneyId = null;
    protected $fName = null;
    protected $lName = null;
    protected $mName = null;
    protected $sumStyle = "ncslj";
    protected $reminderInterval = null;
    function __construct(&$eventsArray, &$attorneyId, &$attorneyName, $sumStyle, $reminderInterval, $verbose){
        $this->verbose = $verbose;
        if ($this->verbose) {
            echo  __METHOD__ . "\n";
            echo "\tpassed values:\n";
            echo "\t\tsumStyle: $sumStyle\n"
                ."\t\treminderInterval: $reminderInterval\n"
                ."\t\tverbose: $verbose\n"
                ."\t\tattorneyId:  $attorneyId\n"
                ."\t\tattorneyName:\n\t\t";
            print_r($attorneyName);                
            echo "\t\teventsArray:\n\t\t";
            print_r($eventsArray);
        };
        $this->attorneyId = $attorneyId;
        $this->fName = $attorneyName['fName'];
        $this->mName = $attorneyName['mName'];
        $this->lName = $attorneyName['lName'];
        if ($sumStyle != null) {
            $this->sumStyle = $sumStyle;
        }
        if ($reminderInterval != null) {
            self::makeReminderInterval($reminderInterval);
            // echo "\tHere is the reminder interval: $this->reminderInterval\n";
        }
        // initialize a new calendar object
        $this->v = new vcalendar(array('unique_id' => 'MyCourtDates.com'));
        // echo "new calendar initiated.";
        
        // Set calendar properties
        $this->v->setProperty('method', 'PUBLISH');
    	$this->v->setProperty("X-WR-TIMEZONE", "America/New_York");
    	
    	// Add the title and description.
        $this->v->setProperty("x-wr-calname", $this->lName . " -- Hamilton County Courts");
        $this->v->setProperty("X-WR-CALDESC", "This schedule for Hamilton County Courts lists scheduled
            appearances for cases where " . $this->fName . " ". $this->mName . " " . $this->lName . " is listed
            as an attorney of record. It was created at " . date("D F j Y g:i a")) . ".";
        // echo "Just before the loop.";
        //  Add all events to Calendar object
        foreach ($eventsArray as $NAC) {
            // print_r($NAC);
            // Build stateTimeDate
            $start = getdate(strtotime($NAC['timeDate']));
            // Build stateTimeDate
            $year = date ("Y", strtotime($NAC["timeDate"]));
            $month = date ("m", strtotime($NAC["timeDate"]));
            $day = date ("d", strtotime($NAC["timeDate"]));
            $hour = date ("H", strtotime($NAC["timeDate"]));
            $minutes = date ("i", strtotime($NAC["timeDate"]));
            $seconds = "00";
            
            // Create the event object
            $e = $this->v->newComponent('vevent');    // initiate a new EVENT
            $e->setProperty('summary', self::createSummary($NAC));   // set summary/title
            $e->setProperty('categories', 'Court_dates');      // catagorize
            $e->setProperty('dtstart', $year, $month, $day, $hour, $minutes, $seconds);     // 24 dec 2006 19.30
            $e->setProperty('duration', 0, 0, 0, 15);         // 15 minutes
            $e->setProperty('description', self::makeDescription($NAC));     // describe the event
            $e->setProperty('location', $NAC['location']);    // locate the event

            // Create event alarm
            if ($this->reminderInterval != null) {
                // create an event alarm
                $valarm = & $e->newComponent("valarm");

                // reuse the event description
                $valarm->setProperty("action", "DISPLAY");
                $valarm->setProperty("description", $e->getProperty("summary"));

                // set alarm to 60 minutes prior to event.
                $valarm->setProperty("trigger", $this->reminderInterval);
            }
        }
    }
    protected function makeDescription(&$NAC){
    	if ($this->verbose) echo  __METHOD__ . "\n";
    	$description  = $NAC['plaintiffs'] . " v. " . $NAC['defendants'] . "\n\n";
    	$description .= $NAC['setting'] . "\n\n";
        $description .= "\nPlaintiffs Counsel:" . self::retrieveProsecutors($NAC) . 
    		"\nDefense Counsel:" . self::retrieveDefense($NAC) .  "\n" . self::retrieveCause($NAC)  . 
    		" \n " . self::getHistURI($NAC['caseNumber']) . "\n" .
    		"\n\nAs of " . date('D M j Y') . ".";
        return $description;
    }
    // Case-specific getters
    protected function retrieveCause(&$NAC)
    {
    	return "[TK] CAUSE";
    }
    protected function retrieveProsecutors(&$NAC)
    {
    	return "[TK] PROSECUTOR";
    }
    protected function retrieveDefense(&$NAC)
    {
    	return "[TK] DEFENSE";
    }
    //	URI getters
	function getSumURI(&$cNum)
	{
		return "http://www.courtclerk.org/case_summary.asp?casenumber=" . rawurlencode($cNum);
	}
	function getDocsURI(&$cNum)
	{
		return "http://www.courtclerk.org/case_summary.asp?sec=doc&casenumber=" . rawurlencode($cNum);
	}
	function getCaseSchedURI(&$cNum)
	{
		return "http://www.courtclerk.org/case_summary.asp?sec=sched&casenumber=" . rawurlencode($cNum);
	}
	function getHistURI(&$cNum)
	{
		return "http://www.courtclerk.org/case_summary.asp?sec=history&casenumber=" . rawurlencode($cNum);
	}
    protected function createAbbreviatedSetting(&$setting){
        if ($this->verbose) echo  __METHOD__ . "\n";
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
        if (array_key_exists ($setting , $abreviations)){
            return $abreviations[ $setting ];
        }
        return $setting;
    }
    protected function createSummary(&$NAC){
        if ($this->verbose) echo  __METHOD__ . "\n";
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
    		a = abreviated setting
    		L = abreviated location  ???
    	*/
    	
    	if ($NAC['active']) {
    	    $summary = null;
    	}else{
            $summary = "INACTIVE-";
    	}
    	for ($i=0; $i < strlen($this->sumStyle)  ; $i++) {
    		switch (mb_substr($this->sumStyle, $i, 1)) {
    			case 'd':
    				$summary = $summary .  $NAC['defendants'];
    				break;
    			case 'p':
    				$summary = $summary .  $NAC['plaintiffs'];
    				break;
    			case 'c':
    				$summary = $summary .  $NAC['plaintiffs'] . " v. " . $NAC['defendants'];
    				break;
    			case 'n':
    				$summary = $summary .  $NAC['caseNumber'];
    				break;
    			case 's':
                    $summary = $summary .  $NAC['setting'];
    				break;
    			case 'l':
    				$summary = $summary .  $NAC['location'];
    				break;
    			case 'j':
    				$summary = $summary .  self::lookUpJudge($NAC['location'], $NAC['caseNumber']);
    				break;
    			case 'a':
    				$summary = $summary .  self::createAbbreviatedSetting($NAC['setting']);
    				break;
    			default:
    			    $summary = $summary .  ' Ω ';
    				break;
    		}
            $summary .=  " ~ ";
    	}
    	// remove last two dashes from $summary and return it.
    	return substr($summary , 0, -2);
    }
    function __get ($var){
        if ($this->verbose) echo  __METHOD__ . "\n";
        switch ($var) {
            case 'ics' :
                $this->v->returnCalendar();       // generate and redirect output to user browser
                break;
            case 'xml':
                // return well-formed xml
                echo iCal2XML($this->v);
                break;
            case 'json':
                echo json_encode($this->v);
                break;
            case 'string':
                // generate and get output in string, for testing?
                echo $this->v->createCalendar();
                break;
        }
    }
    protected function lookUpJudge(&$location, &$caseNumber){
        if ($this->verbose) echo  __METHOD__ . "\n";
        $locationJudges = array(
            "H.C. COURT HOUSE ROOM 485" => "J. Luebbers",
            "H.C. COURT HOUSE ROOM 495" => "J. Allen",
            "H.C. COURT HOUSE ROOM 360" => "J. Martin",
            "H.C. COURT HOUSE ROOM 340" => "J. Myers",
            "H.C. COURT HOUSE ROOM 560" => "J. Nadel",
            "RM 164, CT HOUSE, 1000 MAIN ST" => "BRAD GREENBERG",
);
        // Check array for judge name
        if (array_key_exists ($location, $locationJudges)){
            return $locationJudges[ $location ];
        }
        return "Judge Unknown.";
    }
    protected function makeReminderInterval($reminderInterval){
        if ($this->verbose) echo  __METHOD__ . "\n";
        $interval = DateInterval::createFromDateString($reminderInterval);
        // echo "\tInterval in " . __METHOD__ . " " . $interval->format('%i');
        $totalMinutes = $interval->format('%i'); // add minutes
        // echo "\tTotal minutes: $totalMinutes\n";
        $totalMinutes += $interval->format('%h') * 60; // add hours as minutes
        // echo "\tTotal minutes: $totalMinutes\n";
        $totalMinutes += $interval->format('%a') * 60 * 24; // add totaldays as minutes
        // echo "\tTotal minutes: $totalMinutes\n";
        $totalMinutes = "-PT" . $totalMinutes  . "M";
        // echo "\tTotal minutes: $totalMinutes\n";
        $this->reminderInterval = $totalMinutes;
        // echo "Object this->reminderInterval: " . $this->reminderInterval;
    }
}
?>