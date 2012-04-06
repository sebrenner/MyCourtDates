<?php 
// This library exposes objects and methods for parseing the html DOM.
require_once( "simplehtmldom_1_5/simple_html_dom.php" );

// This library exposes objects and methods for creating ical files.
// require_once( "iCalcreator-2.10.23/iCalcreator.class.php" );

// This code declares the time zone
ini_set('date.timezone', 'America/New_York');

class AttorneySchedule
{
//     The Clerk's site provides three date ranges:
//     future only (default) -- date_range=future
//     future and past 6 months -- date_range=prior6
//     future and past year -- date_range=prior12

    // property declaration    
        
    // An array that holds all the attorneyIds asociated with this attorney.
	protected $attorneyIds = array();
	protected $activeCases = array();
	protected $NACs = array();	// An array of all future NAC and the last six months of NAC.
	protected $activeNACs = 0;

	protected $fName = null;
	protected $lName = null;
	protected $mName = null;
	protected $NACSummaryStyle = null;
	protected $lastUpdated = null;
	
	// Method Definitions
	function __construct( $principalAttorneyId ) {
	    $this->attorneyIds = self::collectAttorneyIds( $principalAttorneyId );
		// print_r ($this->attorneyIds);
	    
	    // Loop through each attorneyId and get the html schedule
	    foreach( $this->attorneyIds as $attorneyId ) {
	        if ( $this::localExpired( $attorneyId ) ){
	            $NACsTemp = $this::queryClerkSite( $attorneyId );
	            $this->NACs = array_merge( $this->NACs, $NACsTemp );
            }
            else{
                /* Run code to get data from local db.
                    Retrieve attonrey name
                    Retrieve NACs.
                BREAK OUT OF FOR LOOP.
                */
            }
            usort( $this->NACs, 'self::compareDate');
            // print_r( $this->NACs );
        }
	}
	protected function queryClerkSite( &$attorneyId ){
	    // Get html file.
        $URI = "http://www.courtclerk.org/attorney_schedule_list_print.asp?court_party_id=" . $this->attorneyIds[0] . "&date_range=prior6";
        // $URI = "temp2/" . $this->attorneyIds[0] . "_SchedFile.html";
        
        // echo "        Attempting to open $URI\n";
        $htmlStr = file_get_contents ( $URI );
        // echo "$URI opened.\n";
        // // Save html file as string--for error checking.
        // $myFile = "temp/" . $this->attorneyIds[0] . "_SchedFile.html";
        // $fh = fopen($myFile, 'c') or die("can't open file");
        // fwrite( $fh, $htmlStr );
        // fclose( $fh );

        // Remvoe cruft from html string
        $this::removeCruft( $htmlStr );

        // Get Attorney Name
        $this::extractAttorneyName( $htmlStr );

        // Parse NAC tables, converting each into and NAC array and adding it to NACs array.
        return $this::parseNACTables( $htmlStr );        
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
		// return array ( 0 => $principalAttorneyId );
		$attorneyIds = array();
        $attorneyIds[] = $principalAttorneyId;
		return $attorneyIds;
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
                echo "breaking";
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
				// Count cases and the NACs/case.
				$this->activeCases[ $NAC[ "caseNum" ] ] += 1;  // This throws a PHP Notice if index doesn't exist.
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
	protected function convertNACtoArray( &$NACTable ){
		$NAC= array();
	
		//	Get date and Time; create dateTime object
		$date = 		$NACTable->find('td', 0 )->innertext;
		$date =			substr( $date, 5);	
		$time = 		$NACTable->find('td', 1 )->innertext;
		$time =			substr( $time, 5);
		$NAC[ "timeDate" ] = 	strtotime( $date . $time );
	
		$NAC[ "caseNum" ] = $NACTable->find('td', 2 )->find('a', 0 )->innertext;
	
		$caption = 		$NACTable->find('td', 3 )->innertext;
		$NAC[ "caption" ] =	substr($caption, 9);
		
		//	Determine if NAC is active
		$active = $NACTable->find('td', 4 )->innertext;
		$active = substr($active, 8);
		$NAC[ "active" ] = 0;
		if ( $active == "A"){
			$NAC["active"] = true;
		}
		
		$location = 	$NACTable->find('td', 5 )->innertext;
		$NAC[ "location" ] = 	substr($location, 27);
		
		$setting = 	$NACTable->find('td', 6 )->innertext;
		$NAC[ "setting" ]  =	substr( $setting, 12 );
		
		return $NAC;
	}
	protected function localExpired( &$attorneyId ){
	    // This fuction will determine if the Clerk's site must be queried
	    // Takes an attonrey Id.
	    // Returns true is the local copy is too old or non-existant.
	    // Treruns false if the lcoal copy is fresh.
	    return true;
	}
	/**
	 * Getters
	*/

    // Event building functions
    protected function getPartyNames( &$NAC ){
    	$vs = strpos( $NAC[ "caption" ] , "vs." );
    	$parties = array(
    		"plaintiff" 	=> substr ( $NAC[ "caption" ], 0 , $vs),
    		"defendant"		=> substr ( $NAC[ "caption" ] , $vs + 4 )
    	);
    	return $parties;
    }
    protected function getDefendant( &$parties ){
    		return $parties[ "defendant" ];
    }
    protected function getPlaintiff( &$parties ){
    		return $parties[ "plaintiff" ];
    }
    protected function getAbbreviatedSetting( $setting ){
    	return "Abv Setting [TK]";
    }
    protected function getSummary( &$NAC, $sumStyle ){
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
    	$summary = null;
    	for ($i=0; $i < strlen( $sumStyle )  ; $i++) {		
    		switch ( mb_substr( $sumStyle, $i, 1) ) {
    			case 'd':
    				$summary = $summary . self::getDefendant( self::getPartyNames( $NAC ) );
    				break;
    			case 'p':
    				$summary = $summary . self::getPlaintiff( self::getPartyNames( $NAC ) );
    				break;
    			case 'c':
    				$summary = $summary .  $NAC[ "caption" ];
    				break;
    			case 'n':
    				$summary = $summary .  $NAC[ "caseNum" ];
    				break;
    			case 's':
    				$summary = $summary .  $NAC[ "summary" ];
    				break;
    			case 'l':
    				$summary = $summary .  $NAC[ "location" ];
    				break;
    			case 'j':
    				$summary = $summary .  $NAC[ "judge" ];
    				break;
    			case 'S':
    				$summary = $summary .  self::getAbbreviatedSetting( $NAC[ "setting" ] );
    				break;
    			default:
    				break;
    		}
    		$summary = $summary .  " |-| ";
    	}
    	return $summary;
    	return "[TK]summary???";
    }
    protected function getNACDescription( $NAC ){
    	$description = "\nPlaintiffs Counsel:" . self::retrieveProsecutors( $NAC ) . 
    		"\nDefense Counsel:" . self::retrieveDefense( $NAC ) .  "\n" . self::retrieveCause( $NAC )  . 
    		"\n" . self::getHistURI( $NAC[ "caseNum" ]) . "\n\n" . $NAC["caption"] .
    		"\n\nAs of " . $this->lastUpdated;
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
	function getSchedURI( &$cNum){
		return "http://www.courtclerk.org/case_summary.asp?sec=sched&casenumber=" . rawurlencode($cNum);
	}
	function getHistURI( &$cNum){
		return "http://www.courtclerk.org/case_summary.asp?sec=history&casenumber=" . rawurlencode($cNum);
	}

	// Attorney Getters
	function getPrincipalAttorneyId(){
		return $this->attorneyIds[ 0 ];
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
	function getICSFile( $outputType, $sumStyle ){
		// This command will cause the script to serve any output compressed
		// with either gzip or deflate if accepted by the client.
		ob_start('ob_gzhandler');
		
		// initiate new CALENDAR
		$v = new vcalendar( array( 'unique_id' => 'Court Schedule' ));

		// Set calendar properties
		$v->setProperty( 'method', 'PUBLISH' );
		$v->setProperty( "X-WR-TIMEZONE", "America/New_York" );
		$calName = "Hamilton County Schedule for [TK ATTORNEY]";
	    $v->setProperty( "x-wr-calname", $calName );
	    $v->setProperty( "X-WR-CALDESC", "This is [TK ATTORNEY NAME]'s schedule for Hamilton County Common Courts.  It covers " . "firstDateReq" . " through " . "lastDateReq" . ". It was created at " . date("F j, Y, g:i a") );
	
		foreach ( $this->NACs as $NAC ) {
			// Build stateTimeDate
	        $year = substr ( $NAC[ "timeDate" ] , 0 , 4);
	        $month = substr ( $NAC["timeDate"] , 5 , 2);
	        $day = substr ( $NAC["timeDate"] , 8 , 2);
	        $hour = substr ( $NAC["timeDate"] , 11 , 2);
	        $minutes = substr ( $NAC["timeDate"] , 14 , 2);
	        $seconds = "00";
			
			// Create the event object
			$UId = strtotime("now") . "[TK caseNumber]"  . "@cms.halilton-co.org";
	        $e = & $v->newComponent( 'vevent' );                // initiate a new EVENT
	        $e->setProperty( 'summary', $this::getSummary( $NAC, $sumStyle) );   // set summary/title
	        $e->setProperty( 'categories', 'Court_dates' );      // catagorize
	        $e->setProperty( 'dtstart', $year, $month, $day, $hour, $minutes, 00 );     // 24 dec 2006 19.30
	        $e->setProperty( 'duration', 0, 0, 0, 15 );         // 3 hours
	        $e->setProperty( 'description', self::getNACDescription( $NAC ) );     // describe the event
	        $e->setProperty( 'location', $NAC[ "location" ] );    // locate the event
		}
		
		switch ( $outputType ) {
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

function outputICS( $clerkId ){
	$a =  new AttorneySchedule( $clerkId );
	$a->getICSFile( 0, "Spdcnlsj" );
}
function testClass ( $clerkId ){
	$a =  new AttorneySchedule( $clerkId );
	echo "<html><head><title>Schedule for " . $a->getAttorneyLName() . "</title></head><body>";
	
	echo "<h1>" . $a->getAttorneyFName() . " " .
	 	$a->getAttorneyMName() . " " . $a->getAttorneyLName() . 
		"<br />Principal Clerk Id: " . $a->getPrincipalAttorneyId() . " </h1>";

	echo "<h3>There are " . $a->getNACCount() . " NAC on this attorney's Hamilton County Courts schedule.  " . $a->getActiveNACCount() . " are active.</h3>";

	echo "<h3>It covers " . date( "F j, Y, g:i a",  $a->getEarliestDate() ) . " through " . date( "F j, Y, g:i a",  $a->getLastDate() ) . ".</h3>";

	echo "<h3>There are " . $a->getActiveCaseCount() . " active cases.</h3>";

	// Print a bulleted list of cases with active NAC, as well as active NAC count.
	echo "<table border=\"1\">
		<tr>
		<th>CaseNumber</th>
		<th>Active NAC's</th>
		</tr>";

	foreach ($a->getActiveCaseNumbers() as $case=>$NACCount ) {
		echo "<tr><td><a href=\"" . $a->getHistURI( $case ) . "\">" .  $case . "</a></td><td>" . $NACCount ."</td></tr>";
	}
	echo "</table>";

	// Print a bulleted list of active NACs
	echo "<table border=\"1\">
		<th>Date</th>
		<th>CaseNumber</th>
		<th>Caption</th>
		<th>Setting</th>
		<th>Location</th>
		</tr>";

	foreach ($a->getNACs() as $NAC ) {
		if ( $NAC["active"]) {
			echo "<tr>";
		}else{
			echo "<TR BGCOLOR=\"#99CCFF\">";
		};
		echo "
			<td>" . date( "F j, Y, g:i a",  $NAC[ "timeDate" ] ) . "</td>
			<td><a href=\"" . $a->getHistURI( $NAC["caseNum"] ) . "\">" .  $NAC["caseNum"] . "</a></td>
			<td>" . $NAC[ "caption" ] ."</td>
			<td>" . $NAC[ "setting" ] ."</td>
			<td>" . $NAC[ "location" ] ."</td>
			</tr>";
	}
	echo "</table>";

	// print_r( $a );
	echo "</body></html>";
}

/**
 * These urls are not being parsed successfully:
 * 	http://www.courtclerk.org/attorney_schedule_list_print.asp?court_party_id=82511&date_range=prior6
 *
 * These one are working:
 * 	http://www.courtclerk.org/attorney_schedule_list_print.asp?court_party_id=PP69587&date_range=prior6 	This worked NOW.
 *	http://www.courtclerk.org/attorney_schedule_list_print.asp?court_party_id=76537&date_range=prior6
 *	http://www.courtclerk.org/attorney_schedule_list_print.asp?court_party_id=73125&date_range=prior6
 *
 * I can't figure out what gives.  I think that the problem is related to parsing the html table 
 * when it is too big.
 * 
 * @author Scott Brenner
 */
echo "Initial Memory Usage:" . memory_get_usage() . "\n"; 
// testClass ( "76537" );
// testClass ( "73125" );		// Knefflin 9 NAC 	|| Peak memory usage:10053744
// testClass ( "PP69587" );		// Pridemore 92 NAC || Peak memory usage:48833464
// testClass ( "82511" );		//					|| died Memory Usage:85222232
// $a =  new AttorneySchedule( "51212" );
testClass ( "PP69587" );      // Tom Bruns 131 NAC|| Peak memory usage:67108864

echo "Final   Memory Usage:" . memory_get_usage() . "\n";
echo "Peak memory usage:" . memory_get_peak_usage ();
echo "Peak memory usage:" . memory_get_peak_usage (true);
?>