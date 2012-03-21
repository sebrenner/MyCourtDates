<?php 
// This library creates fuciton for parseing the html DOM.
require_once( "simplehtmldom_1_5/simple_html_dom.php" );

// This code declares the time zone
ini_set('date.timezone', 'America/New_York');

class AttorneySchedule
{
//     The Clerk's site provides three date ranges:
//     future only (default) -- date_range=
//     future and past 6 months -- date_range=prior6
//     future and past year -- date_range=prior12

    // property declaration    
    const clerkSchedPrintURI = "http://www.courtclerk.org/attorney_schedule_list_print.asp?court_party_id=";
    const clerkSchedPrintURIsuffix = "&date_range=prior6";
    
    // An array that holds all the clerkIDs asociated with this attorney.
//    protected $clerkIDs[ principalClerkID ] = $principalClerkID;
	protected $html = null;
	protected $NACs = array();	// An array of all future NAC and the last six months of NAC.

	protected $fName = null;
	protected $lName = null;
	protected $mName = null;
	protected $NACSummaryStyle = null;
	protected $lastUpdated = null;
		
	// method declarations
	protected function parseAttorneyName( &$schedTable ){
		$attorneyTable = $schedTable->find('table', 0);
		$attorney = $attorneyTable->find('td', 1)->innertext;
		$attorney = explode( "/" , $attorney );
		$this->lName = $attorney[0];
		$this->fName = $attorney[1];
		$this->mName = $attorney[2];
	}
	protected function getSchedTble( $uri ){
		$html = file_get_html( $uri );
		$schedTable = $html->find('table', 2);
		return $schedTable;
	}
 	protected function parseNACs( &$schedTable ){
		// drop the outermost table
		$tableOfNACTables = $schedTable->find( 'table', 1);

		// Get the inntertext of the table containing the nac tables
		// and convert it to an dom object
		$tableOfNACTables = $tableOfNACTables->innertext;
		$tableOfNACTables = str_get_html ( $tableOfNACTables );
		
		//	Iterate through the array of tables convert each tableOfNACTables
		foreach( $tableOfNACTables->find('table') as $NACTable ) {
			$NAC = self::convertNACtoArray( $NACTable );	
			array_push($this->NACs, $NAC );
		}
		usort( $NAC, 'self::compareDate');
	}
	function compareDate($a, $b){
		if ( $a[timeDate] == $b[timeDate] ) return 0;
		return ( $a[timeDate] < $b[timeDate] ) ? -1 : 1;
	}
	
	
	protected function convertNACtoArray( &$NACTable ){
		$NAC= array();
	
		//	Get date and Time; create dateTime object
		$date = 		$NACTable->find('td', 0 )->innertext;
		$date =			substr( $date, 26);	
		$time = 		$NACTable->find('td', 1 )->innertext;
		$time =			substr( $time, 26);
		$NAC[timeDate] = 	strtotime( $date . $time );
	
		$NAC[caseNum] = $NACTable->find('td', 2 )->find('a', 0 )->innertext;
	
		$caption = 		$NACTable->find('td', 3 )->innertext;
		$NAC[caption] =	substr($caption, 26);
		
		//	Determine if NAC is active
		$active = $NACTable->find('td', 4 )->innertext;
		$active = substr($active, 25);
		$NAC[ active ] = 0;
		if ( $active == "A"){
			$NAC[active] = true;
		}
		
		$location = 	$NACTable->find('td', 5 )->innertext;
		$NAC[location] = 	substr($location, 27);
		
		$summary = 	$NACTable->find('td', 6 )->innertext;
		$NAC[ summary ]  =	substr( $summary, 30 );
		
		return $NAC;
	}
	function __construct( $principalClerkID ) {
		// Get the date (html) from the URI (Clerk's site)
		$uri = self::clerkSchedPrintURI . $principalClerkID . self::clerkSchedPrintURIsuffix;
		$schedTable = self::getSchedTble( $uri );
		
		self::parseAttorneyName( $schedTable );
		self::parseNACs( $schedTable );
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
	
	function getNACs(){
		return $this->NACs;
	}
	function getNACCount(){
		return count( $this->NACs);
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
		return $this->NACs[0][ "timeDate" ];
	}
	function getLastDate(){
		return $this->NACs[ count( $this->NACs ) - 1 ][ "timeDate" ];
	}
}

$attorneySchedule =  new AttorneySchedule( "73125" );
echo "<h1>" . $attorneySchedule->getAttorneyFName() . " " . $attorneySchedule->getAttorneyMName() . " " . $attorneySchedule->getAttorneyLName() . "</h1>";
echo "<h3>There are " . $attorneySchedule->getNACCount() . " NAC on this attorney's Hamitlon County Courts schedule</h3>";

// date( "F j, Y, g:i a",  )

echo "<h3>It covers " . date( "F j, Y, g:i a",  $attorneySchedule->getEarliestDate() ) . " through " . date( "F j, Y, g:i a",  $attorneySchedule->getLastDate() ) . ".</h3>";



print_r( $attorneySchedule );
?>