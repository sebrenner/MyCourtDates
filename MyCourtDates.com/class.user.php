<?php
// This class provides methods for creating and exporting schedules.
require_once( "class.user.schedule.php" );

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
    protected $verbose = false;
    protected $dbObj = null;
    protected $userData = array(
        "userBarNumber" => null,
        "fName" => null,
        "mName" => null,
        "lName" => null,
        "email" => null,
        "phone" => null,
        "mobile" => null,
        "street" => null,
        "street2" => null,
        "city" => null,
        "state" => null,
        "zip" => null,
        "subStart" => null,
        "subExpire" => null,
        "subNotes" => null
    );
    protected $userEvents = null;
    protected $scheduleObj = null;
    protected $addOnBarNumbers;
    protected $addOnCaseNumbers;    
    
    // setters and getters for attorney contact information
    public function getFullName( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        if ( empty( $this->userData['fName'] ) && empty( $this->userData['mName'] ) && empty( $this->userData['lName'] ) ){ return false; }
        $fullName = $this->userData['fName'] . " ";
        if ( empty( $this->userData['mName'] )){
            $fullName .= $this->userData['lName'];
            return $fullName;
        }
        $fullName .= $this->userData['mName'] . " " . $this->userData['lName'];
        return $fullName;
    }
    public function getUserBarNumber(){
        if ( $this->verbose ) echo  __METHOD__ . "\n";        
        return $this->userData['userBarNumber'];
    }
    public function setFName( &$fName ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        $this->userData['fName'] = $fName;
        return "true";
    }
    public function getFName( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        if ( empty( $this->userData['fName'] ) ) {
            $this->initializeUserInfo();
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
            $this->initializeUserInfo();
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
        $this->userData['street']   = $address['street'];
        $this->userData['street2']  = $address['street2'];
        $this->userData['city']     = $address['city'];
        $this->userData['state']    = $address['state'];
        $this->userData['zip']      = $address['zip'];
        return true;
    }
    public function getAddress( ){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        $address['street']      = $this->userData['street'];
        $address['street2']     = $this->userData['street2'];
        $address['city']        = $this->userData['city'];
        $address['state']       = $this->userData['state'];
        $address['zip']         = $this->userData['zip'];
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
                    . $this->userData['userBarNumber']
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
    public function getActiveNACCount(){
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        // print_r($this->scheduleObj);
        return $this->scheduleObj->getActiveNACCount();
    }
    public function getAddOnBarNumbers( )
    {
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        if ( empty( $this->addOnBarNumbers ) ) {
            $query  = " SELECT addOnBarNumber
                        FROM addOnBarNumber_tbl
                        WHERE userBarNumber = \""
                        . $this->userData['userBarNumber']
                        . "\"";
            self::connectReadDb();
            $result = mysql_unbuffered_query( $query ) or die( mysql_error() );
            while( $row = mysql_fetch_array( $result, MYSQL_ASSOC )){
                $this->addOnBarNumbers[] = $row['addOnBarNumber'];
            }
        }
        if (! empty( $this->addOnBarNumbers) ) {
            sort( $this->addOnBarNumbers );
        }
        return $this->addOnBarNumbers;
    }
    public function setAddOnCase( $caseNum, $prefs = null, $alarm = null )
    {
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        // probably need to use this method to set prefs for user's own bar number.
        $caseNum = self::normalizeCaseNumber( $caseNum );
        
        $query = "REPLACE INTO barNumberCaseNumber_tbl(
            barNumber,
            caseNumber,
            alarm
            ) 
            VALUES ( "
            . $this->userData['userBarNumber']
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
    public function getAddOnCases( )
    {
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        if ( empty( $this->addOnCaseNumbers ) ) {
            $query  = " SELECT caseNumber
                        FROM addOnCaseNumber_tbl
                        WHERE userBarNumber = \""
                        . $this->userData['userBarNumber']
                        . "\"";
            self::connectReadDb();
            $result = mysql_unbuffered_query( $query ) or die( mysql_error() );
            while( $row = mysql_fetch_array( $result, MYSQL_ASSOC )){
                $this->addOnCaseNumbers[] = $row['caseNumber'];
            }
        }
        if (! empty( $this->addOnCaseNumbers ) ) {
            sort( $this->addOnCaseNumbers );
        }
        return $this->addOnCaseNumbers;
    }

    // getters
    public function getFullSchedule( )
    {
        return "true";
    }
    public function getUserSchedule( )
    {
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        // Get only the user's own schedule
        return $this->userEvents;
    }
    public function setAddOnSchedules( )
    {
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        // Get the user's addOn barNum schedules only
        return "true";
    }
    public function getAddOnCasesSchedules( )
    {
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        // Get the user's addOn case schedules only
        return "true";
    }
    function __construct ( $uBarNumber, $verbose){
        $this->verbose = $verbose;
        if ( $this->verbose ) echo  __METHOD__ . "\n";
        try {
            if ($uBarNumber == '') { throw new Exception('badBarNumber');}
        } catch (Exception $e) {
            echo '\tCaught exception: ',  $e->getMessage(), "\n";
            echo "\t" . __METHOD__ . "The passed value is '$uBarNumber'.\n";
        }
        // Normalize the bar number
        $this->userData['userBarNumber'] = self::normalizeBarNumber( $uBarNumber );
        $this->scheduleObj = new barNumberSchedule( $this->userData['userBarNumber'], $verbose = $this->verbose );
        $this->userData['fName'] = ucwords(strtolower($this->scheduleObj->getFName()));
        $this->userData['mName'] = ucwords(strtolower($this->scheduleObj->getMName()));
        $this->userData['lName'] = ucwords(strtolower($this->scheduleObj->getLName()));
        // print_r($this->userData);
        $this->userEvents = $this->scheduleObj->getEvents();
    }
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
        
        if ( $row['subExpire'] < date( "Y-m-d H:i:s" ) ) { return true; }
        
        return false;
    }
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
                . $this->userData['userBarNumber'] . "\"";
        $result = mysql_query( $query, $this->dbObj ) or die( mysql_error() );
        if( mysql_num_rows( $result ) == 0 ) { return false; }      // return false if no db record exists.        
        $row = mysql_fetch_assoc( $result );
        $row['userBarNumber'] = $this->userData['userBarNumber'];
        $this->userData = $row;
        return true;
    }
    
    /**
     * initializeUserInfo function
     * This function is called the first time a getter, other than a req for the user
     * bar number, is called, i.e., the fist time the getPhone() is called.  It will
     * pull the data from the db, if there.  If no data is available, it will instantiate a 
     * schedule and get the user's name from the schedule.  When this function return all 
     * class user variables will be set to their value or to "", not null.
     * This function returns true on success.
     * 
     *
     * @return bool
     * @author Scott Brenner
     **/
    protected function initializeUserInfo(){
        if (self::pullUserInfoFromDb()){
            return true;
        }
        // Create a schedule
    }
    	
}  // END class 

// $b = new user( "73125" ); // Nefflin
// $b = new user( "76220", true ); // Jackson, Chris
// // $b = new user( "82511" ); // ??
// $b = new user( "74457", true ); // MARY JILL DONOVAN
// // $b = new user( "86612" );    //  SMITH/J/STEPHEN
// echo $b->getFullName() . "\n";
// echo $b->getfName();
// echo $b->getmName();
// echo $b->getlName();
// echo $b->getPhone();
// echo $b->getMobile();
// print_r ( $b->getAddress() );
// print_r ( $b->getUserSchedule() );


// echo $b->getSubBegin();
// echo $b->getSubExpire();
// echo $b->getUserNotes();
// 
?>