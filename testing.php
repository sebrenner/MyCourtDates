<?php 
echo "<html><head><title>Testing Table Scraping</title>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">
</head><body>";

// This library creates fuciton for parseing the html DOM.
require_once( "simplehtmldom_1_5/simple_html_dom.php" );

// This code declares the time zone
ini_set('date.timezone', 'America/New_York');

function getSchedTble( $uri ){
	$html = file_get_html( $uri );
	$schedTable = $html->find('table', 2);
//	echo $schedTable;
	return $schedTable;
}

function getAttorneyName( &$schedTable ){
	$attorneyTable = $schedTable->find('table', 0);
	$attorney = $attorneyTable->find('td', 1);
	echo $attorney;
	return $attorney;
}

function getfirstNAC( &$schedTable ){
	$firstNAC = $schedTable->find('table', 2);
//	echo $firstNAC;
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
 		print_r ( $NAC );
 	}
}

function convertNACtoArray( &$NACTable ){
	echo "<p>Here is a NAC</p>";
	echo $NACTable;
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


$schedTable = getSchedTble( "http://www.courtclerk.org/attorney_schedule_list_print.asp?court_party_id=61854&date_range=" );
//$schedTable = getSchedTble( "print.html" );
$attorneyName = getAttorneyName( $schedTable );
//$firstNAC = getfirstNAC( $schedTable );
$NACS = getNACs( $schedTable );


echo "</body></html>";
?>

