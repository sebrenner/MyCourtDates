<?php 
echo "<html><head><title>Testing Table Scraping</title>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">
</head><body>";
require_once( "simplehtmldom_1_5/simple_html_dom.php" );

function getSchedTble( $uri ){
	$html = file_get_html( $uri );
	$schedTable = $html->find('table', 2);
//	echo $schedTable;
	return $schedTable;
}

function getAttorneyName( &$schedTable ){
	$attorneyTable = $schedTable->find('table', 0);
	$attorney = $attorneyTable->find('td', 1);
	return $attorney;
}

//$schedTable = getSchedTble( "http://www.courtclerk.org/attorney_schedule_list_print.asp?court_party_id=18161&date_range=" );
$schedTable = getSchedTble( "print.html" );
$attorneyName = getAttorneyName( $schedTable );

echo "</body></html>";
?>

