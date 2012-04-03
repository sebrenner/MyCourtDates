<?php 
echo "<html><head><title>Testing Table Scraping</title>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">
<style type=\"text/css\">
.inactive
{
background:yellow;
}
</style>
</head><body>";

// This library creates fuctions for parseing the html DOM.
require_once( "simplehtmldom_1_5/simple_html_dom.php" );

// This code declares the time zone
ini_set('date.timezone', 'America/New_York');

function getSchedTble( $uri ){
	$html = file_get_html( $uri );
	$schedTable = $html->find('table', 2);
	return $schedTable;
}
function getAttorneyName( &$schedTable ){
	$attorneyTable = $schedTable->find('table', 0);
	$attorney = $attorneyTable->find('td', 1)->innertext;
	$attorney = explode( "/" , $attorney );
	$fName = $attorney[1];
	$lName = $attorney[0];
	$mName = $attorney[2];
	$m2Name = $attorney[3];
	return $attorney;
}
function getNACs( &$schedTable ){
	// drop the outermost table
	$tableOfNACTables = $schedTable->find( 'table', 1);
	// Get the inntertext of the table containing the nac tables
	// and convert it to an dom object
	$tableOfNACTables = $tableOfNACTables->innertext;
	$tableOfNACTables = str_get_html ( $tableOfNACTables );
	
	//	Iterate through the array of tables convert each tableOfNACTables
	foreach( $tableOfNACTables->find('table') as $NACTable ) {
 		$NAC = convertNACtoArray( $NACTable );
 		$NACs[] = $NAC;
 	}
 	return $NACs;
}
function convertNACtoArray( &$NACTable ){
	// echo "<p>Here is a NAC</p>";
	// echo $NACTable;
	$NAC= array();

	//	Get date and Time; create dateTime object
	$date = 		$NACTable->find('td', 0 )->innertext;
	$date =			substr( $date, 26);	
	$time = 		$NACTable->find('td', 1 )->innertext;
	$time =			substr( $time, 26);
	$NAC[timeDate] = 	strtotime( $date . $time );

	$NAC[caseNum] = 		$NACTable->find('td', 2 )->find('a', 0 )->innertext;

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
	
	$desc = 	$NACTable->find('td', 6 )->innertext;
	$NAC[ desc ]  =	substr( $desc, 30 );
	
	return $NAC;
}
function printTable( &$NACs){
	echo "<table border = 1>";
	echo "<tr><th>Status</th> <th>Date/Time</th>	<th>Case Caption</th>	<th>Description</th><th>Location</th></tr>";
	foreach( $NACs as $nac ) {
		// If case is active leav the background a regular color
		// If inacive color dim it. with a class selector
		if ( $nac["active"]) {
			echo "<tr class=\"activeNAC\">";
			echo "<td>" . "Active" . "</td>";
		}
		else{
			echo "<tr class=\"inactiveNAC\">";
			echo "<td>" . "Inactive" . "</td>";
		}
		echo "<td>"; 
		echo date( "D M j Y g:ia", $nac[ "timeDate" ] );
		echo "</td>";

		echo "<td>" . "<a class ='popup' href = 'http://www.courtclerk.org/case_summary.asp?sec=sched&casenumber=" . str_replace (" ", "", $nac["caseNum"]) . "' target='_blank'>" . $nac["caseNum"] . " - " . $nac["caption"]. "</a>" . "</td>";

		echo "<td>" . $nac["desc"] . "</td>";
		echo "<td>" . $nac["location"] . "</td>";
		echo "</td>";
	}
	echo "</table>";
}

//	Greg's schedule of ~130 NAC
$schedTable = getSchedTble( "http://www.courtclerk.org/attorney_schedule_list_print.asp?court_party_id=61854&date_range=" );

// $schedTable = getSchedTble( "print.html" );
$attorneyName = getAttorneyName( $schedTable );
print_r( $attorneyName );
printTable( getNACs( $schedTable ) );

// print_r ( $NACs );

echo "</body></html>";
?>

