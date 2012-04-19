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
    protected $dbObj = null;
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
        "subExpire" => "",
        "subNotes" => ""
    );
    protected $schedule = null;
    protected $addOnBarNumbers;
    protected $addOnCaseNumbers;    
    
    // setters and getters for attorney contact information
    public function getFullName( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        if ( empty( $this->userData["fName"] ) && empty( $this->userData["mName"] ) && empty( $this->userData["lName"] ) ){ return false; }

        $fullName = $this->userData["fName"] . " ";
        if ( empty( $this->userData["mName"] )){
            $fullName .= $this->userData["lName"];
            return $fullName;
        }
        $fullName .= $this->userData["mName"] . " " . $this->userData["lName"];
        return $fullName;
    }
    public function getUserBarNumber(){
        if ( $this->verbose ) echo  __METHOD__ . "\n";        
        return $this->userData[ "userBarNumber" ];
    }
    public function setFName( &$fName ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        $this->userData['fName'] = $fName;
        return "true";
    }
    public function getFName( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        return $this->userData['fName'];
    }
    public function setMName( &$mName ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        $this->userData['mName'] = $mName;
        return "true";
    }
    public function getMName( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        return $this->userData['mName'];
    }
    public function setLName( &$lName ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        $this->userData['lName'] = $lName;
        return "true";
    }
    public function getLName( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        return $this->userData['lName'];
    }
    public function setPhone( &$phone ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        $this->userData['phone'] = $phone;
        return "true";
    }
    public function getPhone( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        return "true";
    }
    public function setMobile( &$mobile ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        $this->userData['phone'] = $mobile;
        return "true";
    }
    public function getMobile( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        return "true";
    }
    public function setAddress( &$address ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";    
        $this->userData['street']   = $address["street"];
        $this->userData['street2']  = $address["street2"];
        $this->userData['city']     = $address["city"];
        $this->userData['state']    = $address["state"];
        $this->userData['zip']      = $address["zip"];
        return true;
    }
    public function getAddress( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        $address["street"]      = $this->userData['street'];
        $address["street2"]     = $this->userData['street2'];
        $address["city"]        = $this->userData['city'];
        $address["state"]       = $this->userData['state'];
        $address["zip"]         = $this->userData['zip'];
        return $address;
    }
    public function setSubBegin( &$date ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        $this->userData['subStart'] = $date;
        return "true";
    }
    public function getSubBegin( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        return $this->userData['subStart'];
    }
    public function setSubExpire( &$date ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        $this->userData['subExpire'] = $date;
        return "true";
    }
    public function getSubExpire( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        return $this->userData['subExpire'];
    }
    public function setUserNotes( &$note ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        $this->userData['subNotes'] = $notes;
        return "true";
    }
    public function getUserNotes( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        return $this->userData['subNotes'];
    }
    public function setAddOnBarNumber( $barNum, $prefs = "", $alarm = 0  ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        // probably need to use this method to set prefs for user's own bar number.
        $barNum = self::normalizeBarNumber( $barNum );
        
        $query = "REPLACE INTO addOnBarNumber_tbl
                    ( userBarNumber, 
                    addOnbarNumber, alarms ) 
                    VALUES ( "
                    . $this->userData["userBarNumber"]
                    . ", "
                    . $barNum
                    . ", "
                    . (int) $alarms
                    . ")";
        self::connectWriteDb();
        $result = mysql_query( $query, $this->dbObj ) or die( mysql_error() );        
        //  What do you do with the result.
        return "true";
    }

    /**
     * getAddOnBarNumbers function
     * Returns the addOnBarNumbers.  The add on numbers are already stored
     * locally use those.  Otherwise, it pulls them from the db.
     *
     * @return array
     * @author Scott Brenner
     **/
    public function getAddOnBarNumbers( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        if ( empty( $this->addOnBarNumbers ) ) {
            $query  = " SELECT addOnBarNumber
                        FROM addOnBarNumber_tbl
                        WHERE userBarNumber = \""
                        . $this->userData["userBarNumber" ]
                        . "\"";
            self::connectReadDb();
            $result = mysql_unbuffered_query( $query ) or die( mysql_error() );
            while( $row = mysql_fetch_array( $result, MYSQL_ASSOC )){
                $this->addOnBarNumbers[] = $row[ "addOnBarNumber" ];
            }
        }
        if (! empty( $this->addOnBarNumbers) ) {
            sort( $this->addOnBarNumbers );
        }
        return $this->addOnBarNumbers;
    }
    public function setAddOnCase( $caseNum, $prefs = null, $alarm = null ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        // probably need to use this method to set prefs for user's own bar number.
        $caseNum = self::normalizeCaseNumber( $caseNum );
        
        $query = "REPLACE INTO barNumberCaseNumber_tbl(
            barNumber,
            caseNumber,
            alarm
            ) 
            VALUES ( "
            . $this->userData["userBarNumber"]
            . ", "
            . $caseNum
            . ", "
            . $alarm
            . " ) ";
        
        self::connectWriteDb();
        $result = mysql_query( $query, $this->dbObj ) or die( mysql_error() );        
        //  What do you do with the result?
        return "true";
    }
    public function getAddOnCases( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        if ( empty( $this->addOnCaseNumbers ) ) {
            $query  = " SELECT caseNumber
                        FROM addOnCaseNumber_tbl
                        WHERE userBarNumber = \""
                        . $this->userData[ "userBarNumber" ]
                        . "\"";
            self::connectReadDb();
            $result = mysql_unbuffered_query( $query ) or die( mysql_error() );
            while( $row = mysql_fetch_array( $result, MYSQL_ASSOC )){
                $this->addOnCaseNumbers[] = $row[ "caseNumber" ];
            }
        }
        if (! empty( $this->addOnCaseNumbers ) ) {
            sort( $this->addOnCaseNumbers );
        }
        return $this->addOnCaseNumbers;
    }

    // getters
    public function getIcsSchedule( $outputType, $sumStyle ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        //  If the schedule is null then instatiate one.
        if ( empty( $this->schedule ) ) {
            $this->schedule = new schedule(
                    $this->userData["userBarNumber"],
                    self::addOnCaseNumbers(),
                    self::addOnBarNumbers() );
        }
        // send the getICS message to the schedule object
        $this->schedule->getICS( $outputType, $sumStyle );   
    }
    
    public function getFullSchedule( ){    
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
     * subscriberAuthorized( $barNum )
     *
     * Takes a bar number and returns true if the bar number subscriber
     * is authorized to request a full schedule.
     *
     * @return Bool
     * @author Scott Brenner
     **/
    protected function subscriberAuthorized( $barNum ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
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

        $query = "SELECT subExpire FROM user_tbl where userBarNumber = \"$barNum\"  LIMIT 1;";
       
        // $result = mysql_unbuffered_query( $query ) or die( mysql_error() );
        $result = mysql_query( $query, $dbh ) or die( mysql_error() );
        $row = mysql_fetch_assoc( $result );
        mysql_close( $dbh );
        
        if ( $row["subExpire"] < date( "Y-m-d H:i:s" ) ) { return true; }
        
        return false;
    }
    
    /**
     * pullUserInfoFromDb function
     * Connects to db, and pulls all data.
     *
     * @return void
     * @author Scott Brenner
     **/
    protected function pullUserInfoFromDb (){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        if ( !self::connectReadDb() ){return false;}
        $query = "SELECT  
                    fName, 
                    mName, 
                    lName, 
                    email,
                    phone,
                    mobile,
                    street,
                    street2,
                    city,
                    state,
                    zip,
                    subStart,
                    subExpire
                FROM user_tbl
                WHERE userBarNumber = \""
                . $this->userData["userBarNumber"] . "\"";
        $result = mysql_query( $query, $this->dbObj ) or die( mysql_error() );
        $row = mysql_fetch_assoc( $result );
        $row["userBarNumber"] = $this->userData["userBarNumber"];
        $this->userData = $row;
        return true;
    }
    
    /**
     * constructor function
     * Initializes a user object by normalizing the bar number
     * and retrieving the user information, if it exists.
     *
     * @return void
     * @author Scott Brenner
     **/
    function __construct ( $uBarNumber, $verbose = true ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        
        // Normalize the bar number
        $this->userData[ "userBarNumber" ] = self::normalizeBarNumber( $uBarNumber );
        
        echo "normalized barNumber:" . $this->userData[ "userBarNumber" ] . "\n";
        
        self::pullUserInfoFromDb();
        
        // if user is user is authorized i.e., subscribed;
        if ( self::subscriberAuthorized( $this->userData[ "userBarNumber" ] )) {
            self::getAddOnBarNumbers();
            self::getAddOnCases();
        }
        // at this point we should have a user with all their schedules populated.
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
            return false;
        }
        return true;
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
    protected function normalizeBarNumber( $dirtyBarNumber ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        $result = "";
        $barNumber = strtoupper ( $dirtyBarNumber );
        $barNumber = str_replace ( " ", "", $barNumber );
        $barNumber = str_split( $barNumber );
        foreach ($barNumber as $char) {
            if ( ctype_alnum( $char ) ) { $result .= $char; }
        }
        return $result;
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

class schedule
{
//     The Clerk's site provides three date ranges:
//     future only (default) -- date_range=future
//     future and past 6 months -- date_range=prior6
//     future and past year -- date_range=prior12

    // property declaration    
        
    // An array that holds all the attorneyIds asociated with this attorney.
    protected $verbose = true;
	protected $attorneyIds = array();
	protected $localHtmlPaths = array();
	protected $activeCases = array();
	protected $usedDB = array();
	protected $NACs = array();	// An array of all future NAC and the last six months of NAC.
	protected $activeNACs = 0;
	protected $curAttorneyId = ""; // used to att attorneyId to NAC.
	protected $fName = null;
	protected $lName = null;
	protected $mName = null;
	protected $NACSummaryStyle = null;
	protected $lastUpdated = null;
	
	// Method Definitions
	function __construct( &$userAttorneyId, &$addOnCaseNumbers, &$addOnBarNumbers ) {
        self::subscriberAuthorized( $userAttorneyId );
        self::collectAttorneyIds( $userAttorneyId );
        // print_r( $this->attorneyIds );

	    // Loop through each attorneyId and get the html schedule
	    foreach( $this->attorneyIds as $attorneyId ) {
	        $this->curAttorneyId = $attorneyId;
	        if ( self::isScheduleStale( $attorneyId ) ){
	            $this->usedDB[ $attorneyId ] = false;
	            
	            // Get html from stie.
                // echo "Getting html from Clerk's site.";
	            $htmlStr = self::queryClerkSite( $attorneyId );
	            
                // Remove cruft from html string
                self::removeCruft( $htmlStr );

                // Get Attorney Name
                self::extractAttorneyName( $htmlStr );

                // Parse NAC tables, converting each into and NAC array and adding it to NACs array.
                $this->NACs = array_merge( $this->NACs, self::parseNACTables( $htmlStr ) );
            }
            else{
                // Get data from dB
                // echo "Getting html from local directory.";
                $htmlStr = file_get_contents( self::pathToLocalHtml( $attorneyId ) );
	            $this->usedDB[ $attorneyId ] = true;
            }
        }
        usort( $this->NACs, 'self::compareDate');
	}
	protected function queryClerkSite( &$attorneyId ){
	    // Get html file.
        $htmlStr = file_get_contents ( "http://www.courtclerk.org/attorney_schedule_list_print.asp?court_party_id=" . $attorneyId . "&date_range=prior6" );
        
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
    /**
     * subsrciberAuthorized( $barNum )
     *
     * Takes a bar number and returns true if the bar number subscriber is authorized to
     * request a full schedule.
     *
     * @return Bool
     * @author Scott Brenner
     **/
    protected function subscriberAuthorized( $barNum ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
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

        $query = "SELECT subExpire FROM user_tbl where userBarNumber = \"$barNum\"  LIMIT 1;";
       
        // $result = mysql_unbuffered_query( $query ) or die( mysql_error() );
        $result = mysql_query( $query, $dbh ) or die( mysql_error() );
        $row = mysql_fetch_assoc( $result );
        mysql_close( $dbh );
        
        if ( $row["subExpire"] < date( "Y-m-d H:i:s" ) ) { return true; }
        
        return false;
    }
    protected function collectAttorneyIds( $userAttorneyId ){
		// This function will get all the attorney ids of all the shedules
		// that shoud be included with this principal attorney id.
        
        // set the first item as the userAttorneyId
        $this->attorneyIds[0] = $userAttorneyId;
        
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
                    WHERE userBarNumber = \"$userAttorneyId\"";
        // echo $query;

        $result = mysql_unbuffered_query( $query ) or die( mysql_error() );
        while( $row = mysql_fetch_array( $result, MYSQL_ASSOC )){
            $this->attorneyIds[] = $row[ "addOnBarNumber" ];
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
	 * This function takes a NAC array and insert the NAC data into
	 * the db.  They keys must match the column names.
	 *
	 * @return void
	 * @author Scott Brenner
	 **/
	protected function updateNACIntoDb( $table, $array ){
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
        
        //  create a string of column names from array keys
        $columns = implode(", ", array_keys( $array ) );        
        $escaped_values = array_map( 'mysql_real_escape_string', array_values( $array ) );
        //  Create a string of comma-separated values enclosed in quotes.
        $values = implode("\", \"", $escaped_values);
        $values = "\"" . $values . "\"";
        $query = "INSERT INTO $table ( $columns ) VALUES ( $values ) ON DUPLICATE KEY UPDATE active = \"" . $array[ "active" ] . "\"";
        
        echo $query . "\n";
        $result = mysql_query( $query, $dbh ) or die( mysql_error() );
        mysql_close( $dbh );
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
	protected function pathToLocalHtml( &$attorneyId ){
	    return "attnySchedules/" . $attorneyId . "_SchedFile.html";
	}
	protected function isScheduleStale( &$attorneyId ){
	    // Takes an attonrey Id.
	    // Returns true is the local copy is too old or non-existant.
	    // Retruns false if the local copy is fresh.

	    // Query DB to see if schedule exists.


        $fileName = self::pathToLocalHtml( $attorneyId );
                
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

            if ( self::isWeekend( $now->format('Y-m-d H:i:sP') ) && $fileVintage > strtotime( "Last friday at 4:00 pm" ) ) {
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
	function getLName(){
		return $this->lName;
	}
	function getFName(){
		return $this->fName;
	}
	function getMName(){
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
	
}

$b = new user( "PP68519" );

$a = new user( "7 3?}{-)((125" );  // PP68519

echo "\ntermed:" . $a->getUserBarNumber();
echo "\ntermed:" . $b->getUserBarNumber();
?>