<?php

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
    
    
    // Get attorney name from the schedule:
    // $attyName = self::scrapeClerkSchedule( $barNumber );
    $this->userData["lName"] = $this->schedule->getLName();
    $this->userData["fName"] = $this->schedule->getFName();
    $this->userData["mName"] = $this->schedule->getMName();
    
    return true;
}

?>

