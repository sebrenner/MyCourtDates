<?php
// This class provides methods for creating and exporting schedules.
require_once( "class.schedule.php" );

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
        if ( empty( $this->userData['fName'] ) ) {
            $this->queryDbUserData();
        }
        return $this->userData['fName'];
    }
    public function setMName( &$mName ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        $this->userData['mName'] = $mName;
        return "true";
    }
    public function getMName( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        if ( empty( $this->userData['mName'] ) ) {
            return null;
        }
        return $this->userData['mName'];
    }
    public function setLName( &$lName ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        $this->userData['lName'] = $lName;
        return "true";
    }
    public function getLName( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        if ( empty( $this->userData['lName'] ) ) {
            $this->queryDbUserData();
        }        
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
    public function getIcsSchedule( $outputType = null, $sumStyle = null ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        //  If the schedule is null then instatiate one.
        if ( empty( $this->schedule ) ) {
            $this->schedule = new schedule(
                    $this->userData["userBarNumber"],
                    self::getAddOnCases(),
                    self::getAddOnBarNumbers() );
        }
        
        // send the getICS message to the schedule object
        // I's leaving the paramater our for now.  I will have to figure
        // out how to get them in there.
        $this->schedule->getICS( );   
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
        
        // echo "normalized barNumber:" . $this->userData[ "userBarNumber" ] . "\n";
        
        self::pullUserInfoFromDb();
        
        // if user is user is authorized i.e., subscribed;
        if ( self::subscriberAuthorized( $this->userData[ "userBarNumber" ] )) {
            self::getAddOnBarNumbers();
            self::getAddOnCases();
        }
        // at this point we should have a user with all their schedules populated.
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
     * queryDbUserData function
     * Returns true if it successfully populates the users data.
     * It first tries to pull data from db, if it can't it scrapes
     * the clerk's site.
     *
     * @return bool
     * @author Scott Brenner
     **/
    function queryDbUserData(){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        if ( !mysql_ping( $this->dbObj ) ) {
            echo 'Lost connection, exiting queryDbUserData';
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
        
        // No data in Db, create a schedule object and get the user data
        // from the clerk's site.
        // Put name in Db.
        echo "No user data in Db.\n";
        if ( empty( $this->schedule ) ) {
            $this->schedule = new schedule(
                    $this->userData["userBarNumber"],
                    self::getAddOnCases(),
                    self::getAddOnBarNumbers() );
        }
        
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

$b = new user( "PP68519" );

// echo $b->getUserBarNumber();
// echo "\n\nAm I here?";
echo $b->getFullName();
echo $b->getfName();
echo $b->getmName();
echo $b->getlName();
echo $b->getPhone();
echo $b->getMobile();
print_r ( $b->getAddress() );
echo $b->getSubBegin();
echo $b->getSubExpire();
echo $b->getUserNotes();
echo $b->queryDbUserData();


?>