<?php
// This library exposes objects and methods for parseing the html DOM.
require_once( "simplehtmldom_1_5/simple_html_dom.php" );

// This code declares the time zone
ini_set('date.timezone', 'America/New_York');
date_default_timezone_set ( 'America/New_York');


/**
 * user class
 *
 * @package MyCourtDates.com
 * @author Scott Brenner
 * 
 **/
 
class user
{
    // internal properties
    protected $verbose = true;
    protected $userData = array(
        "userBarNumber" => "", 
        "fName" => "", 
        "mName" => "", 
        "lName" => "", 
        "email" => "",
        "phone" => "",
        "mobile" => "",
        "street" => "",
        "street2" => "",
        "city" => "",
        "state" => "",
        "zip" => "",
        "subStart" => "",
        "subExpire" => ""
    );
    protected $userShedule;
    protected $addOnSchedules;
    protected $addOnCases;
    
    // setters and getters for attorney contact information
    public function getFullName( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
return "true";
    }
    public function getUserBarNumber(){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
return $this->userData['userBarNumber'];
    }
    
    public function setFName( $fName ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
return "true";
    }
    public function getFName( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
return "true";
    }

    public function setMName( $mName ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        return "true";
    }
    public function getMName( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        return "true";
    }

    public function setLName( $lName ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        return "true";
    }
    public function getLName( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        return "true";
    }

    public function setPhone( $phone ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        return "true";
    }
    public function getPhone( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        return "true";
    }

    public function setMobile( $mobile ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        return "true";
    }
    public function getMobile( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        return "true";
    }

    public function setAddress( $Address ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        // An associative array
        $setAddress = array( "setAddress" => "true" );
        return $setAddress;
    }
    
    public function getAddress( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        return "true";
    }
        
    public function setSubBegin( $date ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        return "true";
    }
    public function getSubBegin( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        return "true";
    }
    
    public function setSubExpire( $date ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        return "true";
    }
    public function getSubExpire( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        return "true";
    }
    
    public function setUserNotes( $note ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        return "true";
    }
    public function getUserNotes( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        return "true";
    }
    
    public function setAddOnBarNumber( $barNum, $prefs  ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        // probably need to use this method to set prefs for user's own bar number.
        return "true";
    }

    public function getAddOnBarNumbers( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        // returns array
        $getAddOnBarNumbers = array( "getAddOnBarNumbers" => "true" );
        return $getAddOnBarNumbers;
    }

    public function setAddOnCase( $caseNum, $prefs ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        return "true";
    }
    public function getAddOnCases( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        // returns array
        $getAddOnCases = array( "getAddOnCases" => "true" );
        return $getAddOnCases;
    }

    // getters
    public function getFullSchedule( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        return "true";
    }
    public function getUserSchedule( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        // Get only the user's own schedule
        $getUserSchedule = array( "getUserSchedule" => "true" );
        return $getUserSchedule;

    }
    public function setAddOnSchedules( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        // Get the user's addOn barNum schedules only
        return "true";
    }
        
    public function getAddOnCasesSchedules( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        // Get the user's addOn case schedules only
        return "true";
    }
    
    /**
     * constructor function
     * Creates a user object
     *
     * @return void
     * @author Scott Brenner
     **/
    function __construct ( $userBarNumber, $verbose = true ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        $this->userData[ "userBarNumber" ] = self::normalizeBarNumber( $userBarNumber );
        $this->userData["userBarNumber"] . ".\n";
        // Query Db for user properties.  If not in db, get from Clerk's site.
        try {
            self::connectReadDb();
        } catch (Exception $e) {
            echo "Couldn't connect to db.  Error:\n";
            echo $e;
        }
        self::populateUserData();
        self::populateUserSchedule();
        self::populateAddOnSchedules();
        self::populateAddOnCases();
        self::closeDbConnection();
    }

    /**
     * connectWriteDb function
     * Takes no arguments creates a connection to the db for reading and writing.
     * Returns true if the connection is created.
     *
     * @return bool
     * @author Scott Brenner
     **/
    protected function connectWriteDb(){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        include("passwords/todayspo_MyCourtDates.php");
        // Query the db for additional ids and append them to the array.
        try
        {
            $this->dbObj = mysql_connect( $dbHost, $dbAdmin, $dbAdminPassword ) or die(mysql_error());
            mysql_select_db( $db ) or die( mysql_error() );    
        }
        catch(PDOException $e)
        {
            echo $e->getMessage();
            echo "<br><br>Database $db -- NOT -- loaded successfully .. ";
            die( "<br><br>Query Closed !!! $error");
        }
    }

    /**
     * connectReadDb function
     * Takes no arguments creates a connection to the db for reading.
     * Returns true if the connection is created.
     *
     * @return bool
     * @author Scott Brenner
     **/
    protected function connectReadDb(){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        include("passwords/todayspo_MyCourtDates.php");
        
        // Query the db for additional ids and append them to the array.
        try
        {
            $this->dbObj = mysql_connect( $dbHost, $dbReader, $dbReaderPassword) or die(mysql_error());
            mysql_select_db( $db ) or die( mysql_error() );    
        }
        catch(PDOException $e)
        {
            echo $e->getMessage();
            echo "<br><br>Database $db -- NOT -- loaded successfully .. ";
            die( "<br><br>Query Closed !!! $error");
        }
        
    }
    /**
     * closeDbConnection function
     * Closes the active connection
     *
     * @return void
     * @author Scott Brenner
     **/
    protected function closeDbConnection(){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        mysql_close( $this->dbObj );
    }

    /**
     * populates the user's schedule  function
     * returns an array of events from Either from the dB or the Clerk's site
     * if data is from the clerk's site it will be put into db.
     *
     * @return void
     * @author Scott Brenner
     **/
    function populateUserSchedule(){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        return true;
    }

    /**
     * populateAddOnSchedules function
     * populates addo 
     *
     * @return void
     * @author Scott Brenner
     **/
    function populateAddOnSchedules(){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        return true;
        self::closeDbConnection();
    }
    
    /**
     * populateAddOnCases function
     * returns an array events for the addOn cases
     *
     * @return void
     * @author Scott Brenner
     **/
    function populateAddOnCases(){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        return true;
    }
    
    
    /**
     * queryClerkSite function
     * Takes an barNumber and returns the html of the clerk's schedule page.
     *
     * @return string
     * @author Scott Brenner
     **/
    protected function queryClerkSite( &$barNumber ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
	    // Get html file.
        return file_get_contents ( "http://www.courtclerk.org/attorney_schedule_list_print.asp?court_party_id=" . $barNumber . "&date_range=prior6" );
    }
	
    /**
     * removeCruft function
     * Takes an html string of the Clerk's schedule page and removes the 
     * unhelpful html code and white-space characters.
     * 
     * @return string
     * @author Scott Brenner
     **/
    protected function removeCruft( &$htmlStr ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        if ( $this->verbose ){
            echo "Length of htmlStr before str_replace:" . strlen($htmlStr) . "\n";
        }
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
        foreach ( $replacements as $key => $value) {
    	    $htmlStr = str_replace( $key, $value, $htmlStr, $count);
            // echo "Replaced $count instances of \"$key\"\n";
    	    while ( strpos( $htmlStr, "  " ) ) {
        	    $htmlStr = str_replace( "  ", " ", $htmlStr, $count);
                // echo "Removed $count double spaces.\n";
            }
        }
        if ( $this->verbose ) {
            echo "Length of htmlStr after str_replace:" . strlen($htmlStr). "\n";
        }
    }
    
    /**
     * convertNACtoArray function
     * Takes an NAC table object and return NAC array
     *
     * @return array
     * @author Scott Brenner
     **/
    protected function convertNACtoArray( &$NACTable ){
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
		self::updateNACIntoDb( "NAC_tbl", $NAC );
	
	    $NAC[ "attorneyId" ] = $this->curAttorneyId;
        // extract individual party names
		$caption = $NACTable->find('td', 3 )->innertext;
    	$vs = strpos( $caption , "vs." );
    	$NAC [ "plaintiffs" ]   = substr ( $caption, 9 , $vs - 10 );
    	$NAC [ "defendants" ]   = substr ( $caption , $vs + 4 );
        		
        // print_r($NAC);
		return $NAC;
	}
	
	/**
	 * updateNACIntoDb function
	 * Takes a table and an array and updates them in to the db.
	 * @return void
	 * @author Scott Brenner
	 **/
	protected function updateNACIntoDb( $table, $array ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        include("passwords/todayspo_MyCourtDates.php");
        self::closeDbConnection();
        self::connectWriteDb();
        
        //  create a string of column names from array keys
        $columns = implode(", ", array_keys( $array ) );        
        $escaped_values = array_map( 'mysql_real_escape_string', array_values( $array ) );
        //  Create a string of comma-separated values enclosed in quotes.
        $values = implode("\", \"", $escaped_values);
        $values = "\"" . $values . "\"";
        $query = "INSERT INTO $table ( $columns ) VALUES ( $values ) ON DUPLICATE KEY UPDATE active = \"" . $array[ "active" ] . "\"";
        
        echo $query . "\n";
        $result = mysql_query( $query, $dbh ) or die( mysql_error() );
        self::closeDbConnection();
        
	}
	
	/**
	 * compareDate function
	 * Takes two dates.
	 * Returns 0 if the dates are equal, -1 if $a is older then $b else 1.
	 * @return int
	 * @author Scott Brenner
	 **/
	protected function compareDate($a, $b){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
		if ( $a["timeDate"] == $b["timeDate"] ) return 0;
		return ( $a["timeDate"] < $b["timeDate"] ) ? -1 : 1;
	}
	
	
    /**
     * parseNACTables function
     * Takes a de-crufted html string and returns a array of NAC
     *
     * @return array
     * @author Scott Brenner
     **/
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

            $index = $NACTableIndexStart + 1; // Advance index so that next strpos will find next table
            
            // Convert html string to DOM obj.
            $NACTableObj = str_get_html( $NACTableStr );
                        
            // Convert the NAC table into an indexed array.
			$NAC = self::convertNACtoArray( $NACTableObj );

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
		
		//  Sort NAC array by date.
		usort( $tempNACs, 'self::compareDate');
		return $tempNACs;
	}
    
    
    /**
     * scrapeClerkSchedule function
     * Takes a bar number and returns an array of NAC
     *
     * @return void
     * @author Scott Brenner
     **/
    function scrapeClerkSchedule( &$barNumber ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        // echo "Getting html from Clerk's site.";
        $htmlStr = self::queryClerkSite( $barNumber );
        
        // Remove cruft from html string
        self::removeCruft( $htmlStr );

        // Parse NAC tables, converting each into
        // and NAC array and adding it to NACs array.
        $this->userShedule = self::parseNACTables( $htmlStr );
        
        // Get Attorney Name
        return self::extractAttorneyName( $htmlStr );
    }

    /**
     * extractAttorneyName function
     * Takes an html string of the clerk schedule
     * Returns an array of name information 0= lname, 1 =fname, 2=mname;
     *
     * @return array
     * @author Scott Brenner
     **/
    protected function extractAttorneyName( &$htmlStr ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
	    $attyNameIndexStart = strpos ( $htmlStr , "Attorney Name:" ) + 48;  // offset to get to actual name
        $attyNameIndexEnd = strpos ( $htmlStr , "Attorney ID:") - 18; // offset to get to end of actual name
        $attyNameLength = $attyNameIndexEnd - $attyNameIndexStart;
        // echo "\nThe attorney name starsts at $attyNameIndexStart.  It ends at $attyNameIndexEnd.\n It is $attyNameLength char long.\n";
        $attyName = substr ( $htmlStr , $attyNameIndexStart, $attyNameLength );
        // echo "The attyNameTableStr: $attyName\n";
		$attyName = explode( "/" , $attyName );
		return $attyName;
	}
    

    /**
     * normalizeBarNumber function
     * Takes a bar number and normalizes
     *
     * @return string
     * @author Scott Brenner
     **/
    protected function normalizeBarNumber( $userBarNumber ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        $barNumber = "";
        $userBarNumber = strtoupper ( $userBarNumber );
        $userBarNumber = str_replace ( " ", "", $userBarNumber );
        $userBarNumber = str_split( $userBarNumber );
        foreach ($userBarNumber as $char) {
            if ( ctype_alnum( $char ) ) { $barNumber .= $char; }
        }
        return $barNumber;
    }
    /**
     * populateUserData function
     * Returns true if it successfully populates the users data.
     * It first tries to pull data from db, if it can't it scrapes
     * the clerk's site.
     *
     * @return bool
     * @author Scott Brenner
     **/
    protected function populateUserData(){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        if ( !mysql_ping( $this->dbObj ) ) {
            echo 'Lost connection, exiting populateUserData';
        }

        $query = "SELECT * FROM user_tbl WHERE userBarNumber = \"" . $this->userData["userBarNumber"] .  "\"";

        $result = mysql_query( $query, $this->dbObj ) or die( mysql_error() );
        // if result contains data, populate properties and return true
        var_dump( $result );
        if ( mysql_num_rows($result) ){
            echo "\$result evaluates to true\n";
            $this->userData = mysql_fetch_assoc( $result );
            return true;
        }
        
        // No data in Db, scrape clerk's site for schedule and attorney name
        // Put name in Db.
        echo "No user data in Db.\n";
        $attyName = self::scrapeClerkSchedule( $barNumber );
        if ( array_key_exists( 0, $attyName ) ){
            $this->userData["lName"] = $attyName[0];
        }
		if ( array_key_exists( 1, $attyName ) ){
		    $this->userData["fName"] = $attyName[1];
		}
		if ( array_key_exists( 2, $attyName ) ){
		    $this->userData["mName"] = $attyName[2];
        }
        
        return true;
    }
 
 }  // END class 

$a = new user( "pp7 3?}{-)((125" );
echo $a->getUserBarNumber();
?>