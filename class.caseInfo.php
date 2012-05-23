<?php 
// 
//
//  Created by Scott Brenner on 2012-04-07.
//  Copyright 2012 Scott Brenner. All rights reserved.
// 

// This library exposes objects and methods for parseing the html DOM.
require_once("libs/simplehtmldom_1_5/simple_html_dom.php");

// This code declares the time zone
ini_set('date.timezone', 'America/New_York');
date_default_timezone_set ('America/New_York');

class caseInfo
{
//     The Clerk's site provides three date ranges:
//     future only (default) -- date_range=future
//     future and past 6 months -- date_range=prior6
//     future and past year -- date_range=prior12

    
    protected $verbose = false;
    protected $caseNumber = null;
    protected $prosecutors = null;
    protected $defense = null;
    protected $caseDetails = array();
	
	// Method Definitions
	function __construct($caseNumber, $verbose)
	{
        $this->verbose = $verbose;
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        // set instance variables.
        $this->caseNumber = self::normalizeCaseNumber($caseNumber);

        $htmlStr=self::queryClerkSite('party');
        
        $pos = strpos($htmlStr, 'The case number that you entered was not found.');
        if ($this->verbose){
          echo "\tThe pos is $pos.  If this is false then the casenumber is valid.\n";
        } 
        if ($pos === false) {
            self::removeCruft($htmlStr);  // Pass by ref. modifies $htmlStr
            $htmlStr = self::extractCounselTable($htmlStr);
            // $this->prosecutors=self::extractProsecutors();
            // $this->defense=self::extractDefense();                    
        }else{
            echo $this->caseNumber . " is not a valid Hamilton County Clerk of Courts attorney id.";
            return false;
        }
        echo $htmlStr;
        
        
	}
	protected function normalizeCaseNumber( $dirtyCaseNumber ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        $result = "";
        $caseNumber = strtoupper ( $dirtyCaseNumber );
        $caseNumber = str_replace ( " ", "", $caseNumber);
        $caseNumber = str_split( $caseNumber );
        foreach ($caseNumber as $char) {
            if ( ctype_alnum( $char ) ) { $result .= $char; }
        }
        return $result;
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
                    . $this->caseNumber
                    . "\"";
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
    protected function logRequest(&$URI, &$start, &$end, &$html){
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
        $myStart = $start->format('Y-m-d H:i:s');
        $myEnd = $end->format('Y-m-d H:i:s');
        $mySize = strlen($html);
        $query =   "INSERT INTO reqLog_tbl (
                                    URI, 
                                    start, 
                                    end,
                                    htmlSize)
                    VALUES 
                        ('$URI', '$myStart', '$myEnd', $mySize)";
        if ($this->verbose) {echo   "\t$query\n";}
        // Insert code saving html to server directory        
        $result = mysql_query( $query, $dbh ) or die( mysql_error() );
        mysql_close( $dbh );
    }
	protected function queryClerkSite($section)
    {
        if ($this->verbose) echo  __METHOD__ . "\n";
        $URI = "http://www.courtclerk.org/case_summary.asp?sec=$section&casenumber="
        . $this->caseNumber;
        $startRequest = new DateTime();
        $htmlScrape = file_get_contents($URI);
        $endRequest = new DateTime();
        self::logRequest($URI, $startRequest, $endRequest, $htmlScrape);
        $this->source = "Clerk's site";
        return $htmlScrape;
	}
	protected function removeCruft(&$htmlStr)
	{
	    if ($this->verbose) echo  __METHOD__ . "\n";
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
                    "<tr><td>Filed Date:</td>" => "</td></tr><tr><td>Filed Date:</td>",
                    " cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\"" =>  " id=\"NAC\"",
                    "\r\n" => "",
                    " width=\"100%\" cellpadding=\"5\" cellspacing=\"0\" border=\"0\""=> " id=\"COUNSELOFRECORD\"",
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
    protected function extractCounselTable($htmlStr){
        if ($this->verbose) echo  __METHOD__ . "\n";        

        // Convert html string to DOM obj.
        $htmlObj = str_get_html($htmlStr);
        $contentTable = $htmlObj->find("table", 3);
        // echo $contentTable;
        
        // Extract the detail from case summary table
        $caseInfoTable = $htmlObj->find("table",3)->find("table",0);        
        foreach ($caseInfoTable->find('tr') as $key => $row) {
            $myKey= str_replace(':', '', $row->find('td',0)->innertext);
            if (strpos ($myKey , '<') === FALSE) {
                $this->caseDetails[$myKey] = $row->find('td',1)->innertext;
            } 
        }
        print_r($this->caseDetails);
        
        $counselInfoTable = $htmlObj->find("table",3)->find("table",3);
        // echo "\n\n***********counselInfoTable\n\n" . $counselInfoTable;

        foreach ($counselInfoTable->find('tr') as $key => $row) {
            if (strpos ($row->find('td',0)->innertext , '<') === FALSE) {
                echo "\n" . $row->find('td',0)->innertext;
                echo "\n" . $row->find('td',1)->innertext;
                echo "\n" . $row->find('td',2)->innertext;
                echo "\n" . $row->find('td',3)->innertext;
                echo "\n" . $row->find('td',4)->innertext;
            }
        }

        // $tag = 'tr';
        // for ($i=0; $i < 25 ; $i++) {
        //     echo "\n\n***$tag $i***\n\n";
        //     $rows = $counselInfoTable->find($tag, $i);
        //     echo $rows;}
    }
}


$b = new caseInfo( "B 1201206", true );
$b = new caseInfo( "A1201206", true );
?>