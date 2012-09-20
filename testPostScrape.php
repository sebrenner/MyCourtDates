<?php
//
// A very simple PHP example that sends a HTTP POST to a remote site
//

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL,"http://www.courts.state.co.us/Courts/Docket_Print.cfm");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS,
"County_ID=62&Location_ID=73&Court=Both&Date=&Firstname=&Case_Num=&Lastname=&Bar_Number=12309&Division=&Records_Per_Page=0&Page=1&SortIndex=1&Sort1=Hearing_Date&Sort2=Hearing_Time&Sort3=Lastname&Sort1dir=ASC&Sort2dir=ASC&Sort3dir=ASC");

$result =  curl_exec ($ch);
echo "scott did this.";
echo $result;
curl_close ($ch); 
?>
