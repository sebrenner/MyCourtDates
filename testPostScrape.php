<?php
//
// A very simple PHP example that sends a HTTP POST to a remote site
//

// This library exposes objects and methods for parseing the html DOM.
require_once("libs/simplehtmldom_1_5/simple_html_dom.php");

// This code declares the time zone
ini_set('date.timezone', 'America/Denver');
date_default_timezone_set ('America/Denver');

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL,"http://www.courts.state.co.us/Courts/Docket_Print.cfm");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS,
"County_ID=62&Location_ID=73&Court=Both&Date=&Firstname=&Case_Num=&Lastname=&Bar_Number=12309&Division=&Records_Per_Page=0&Page=1&SortIndex=1&Sort1=Hearing_Date&Sort2=Hearing_Time&Sort3=Lastname&Sort1dir=ASC&Sort2dir=ASC&Sort3dir=ASC");

$result =  curl_exec ($ch);
// echo $result;

$htmlDOM = str_get_html($result);

// create array from rows.  Each row is one event.
echo "we got almost here";
$events = array();
$i=1;
while ( true ) {
    if( $html->find("tr", $i) ){
        $e = $html->find("tr", $i);
        echo $e;
        $events[] = $e->innertext;
        $i++;
    }
    else break;
}

echo "we got here";
// print_r($events);

curl_close ($ch); 
?>
