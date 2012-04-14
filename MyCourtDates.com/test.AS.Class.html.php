<?php
// 
//  attorneySchedule.Class.Tester.php
//  This script runs tests of the attorneySchedule.Class.php
//
//  Created by Scott Brenner on 2012-04-07.
//  Copyright 2012 Scott Brenner. All rights reserved.
// 

require_once( "attorneySchedule.Class.php" );
$attorneyIds = array();
// Get parameters
if( isset( $_GET["attorneyId"] ) ){
    $attorneyIds[] = strtoupper( $_GET[ "attorneyId" ] );
}
else{
    $attorneyIds = array();
    $attorneyIds[] = "66768";    //  HARRIS/RODNEY/J
    $attorneyIds[] = "13836";   // Dan Burke no NAC
    $attorneyIds[] = "67645";     //  SMITH/J/P
    $attorneyIds[] = "69638";   // Wendy Calaway
    $attorneyIds[] = "23890";   // Tim Cutcher ~3 dozen
    $attorneyIds[] = "24523";   // Frank Osborn ~2 dozen
    $attorneyIds[] = "67668";    //  HARRIS/RODNEY/J
    $attorneyIds[] = "68519";    //  BURROUGHS/KATIE/M
    $attorneyIds[] = "40186";   // Dan Burke no NAC
    $attorneyIds[] = "51212";   // Tom Bruns
    $attorneyIds[] = "73125";   // Knefflin 9 NAC   || Peak memory usage:10053744
    $attorneyIds[] = "76537";
    $attorneyIds[] = "68804";    //  SMITH/JONATHAN/K
    $attorneyIds[] = "82511";   //     || died Memory Usage:85222232
    $attorneyIds[] = "85696";   // CUTCHER/JEFFREY/J ~2 dozen
    $attorneyIds[] = "PP69587";  // Pridemore 92 NAC || Peak memory usage:48833464
    $attorneyIds[] = "68519";    
}
?>

<!DOCTYPE html>
<!-- paulirish.com/2008/conditional-stylesheets-vs-css-hacks-answer-neither/ -->
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!-- Consider adding a manifest.appcache: h5bp.com/d/Offline -->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->
<head>
  <meta charset="utf-8">

  <!-- Use the .htaccess and remove these lines to avoid edge case issues.
       More info: h5bp.com/i/378 -->
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

  <title>Testing the Attorney Schedule Class -- HTML</title>
  <meta name="description" content="">

  <!-- Mobile viewport optimized: h5bp.com/viewport -->
  <meta name="viewport" content="width=device-width">

  <!-- Place favicon.ico and apple-touch-icon.png in the root directory: mathiasbynens.be/notes/touch-icons -->

  <link rel="stylesheet" href="css/style.css">
  <link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>


  <!-- More ideas for your <head> here: h5bp.com/d/head-Tips -->

  <!-- All JavaScript at the bottom, except this Modernizr build.
       Modernizr enables HTML5 elements & feature detects for optimal performance.
       Create your own custom Modernizr build: www.modernizr.com/download/ -->
  <script src="js/libs/modernizr-2.5.3.min.js"></script>
</head>
<body>
  <!-- Prompt IE 6 users to install Chrome Frame. Remove this if you support IE 6.
       chromium.org/developers/how-tos/chrome-frame-getting-started -->
  <!--[if lt IE 7]><p class=chromeframe>Your browser is <em>ancient!</em> <a href="http://browsehappy.com/">Upgrade to a different browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">install Google Chrome Frame</a> to experience this site.</p><![endif]-->
  <header>
	<h2>This test file will display the following clerk schedules:</h2>
	<ul>
	<?php
	
	foreach ( $attorneyIds as $attorneyId ) {
    	echo "<li><a href=\"#$attorneyId\">$attorneyId</a></li>"; 
    }
	?>
	</ul>
	</header>
  <div role="main">
  
	<div id="accordion">

<?php

foreach ( $attorneyIds as $attorneyId ) {
	// create an AttorneySchedule object
    $a =  new AttorneySchedule( $attorneyId );
	
	// create a header for the accordian section
	echo "<h3><a name=\"$attorneyId\">" . $a->getAttorneyFName() . " " . 	$a->getAttorneyMName() . " " . $a->getAttorneyLName() . 	" (" . $a->getPrincipalAttorneyId() . ")</a></h3>";

	// begin the accordianed body
	echo "<div>";
	if ( $a->usedDB( $a->getPrincipalAttorneyId() )) {
	    echo "<h4>Used local html file.</h4>";
	}else{
	    echo "<h4>Queried Clerk's site for html file.</h4>";
	}
	echo "<h3>There are " . $a->getNACCount() . " NAC on this attorney's Hamilton County Courts schedule.  " . $a->getActiveNACCount() . " are active.</h3>";

	echo "<h3>It covers " . $a->getEarliestDate() . " through " . $a->getLastDate() . ".</h3>";

	echo "<h3>There are " . $a->getActiveCaseCount() . " active cases.</h3>";

	// create a table of cases with active NAC, as well as active NAC count.
	echo "<table border=\"1\">
		<tr>
		<th>CaseNumber</th>
		<th>Active NAC's</th>
		</tr>";

	foreach ($a->getActiveCaseNumbers() as $case=>$NACCount ) {
		echo "<tr><td><a href=\"" . $a->getHistURI( $case ) . "\">" .  $case . "</a></td><td>" . $NACCount ."</td></tr>";
	}
	echo "</table>";

	// create a table of active NACs
	echo "<table border=\"1\">
		<th>Date</th>
		<th>CaseNumber</th>
		<th>Caption</th>
		<th>Setting</th>
		<th>Abv</th>
    	<th>Location</th>
		<th>AttorneyID</th>
		</tr>";
	
	foreach ($a->getNACs() as $NAC ) {
		if ( $NAC["active"]) {
			echo "<tr>";
		}else{
			echo "<TR BGCOLOR=\"#99CCFF\">";
		};
		echo "
			<td>" . $NAC[ "timeDate" ] . "</td>
			<td><a href=\"" . $a->getHistURI( $NAC["caseNum"] ) . "\">" .  $NAC["caseNum"] . "</a></td>
			<td>" . $NAC[ "plaintiffs" ] . " v. " . $NAC[ "defendants" ] ."</td>
			<td>" . $NAC[ "setting" ] ."</td>
			<td>" . $a->createAbbreviatedSetting( $NAC[ "setting" ] ) ."</td>
    		<td>" . $NAC[ "location" ] ."</td>
			<td>" . "<a href=\"" .
			"http://www.courtclerk.org/attorney_schedule_list_print.asp?court_party_id=" . 
			        $NAC[ "attorneyId" ] . 
			        "&date_range=prior6\">" .
			        $NAC[ "attorneyId" ] .
			        "</a></td></tr>";
	}
	echo "</table>";
	echo "</div>";
}
?>

  </div>
  <footer>
    <p>This file was created today.</p>
  </footer>


  <!-- JavaScript at the bottom for fast page loading -->

  <!-- Grab Google CDN's jQuery, with a protocol relative URL; fall back to local if offline -->
  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
  <script>window.jQuery || document.write('<script src="js/libs/jquery-1.7.1.min.js"><\/script>')</script>
  <script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>
  
  <script>
  $(document).ready(function() {
    $("#accordion").accordion();
  });
  </script>

  <!-- scripts concatenated and minified via build script -->
  <script src="js/plugins.js"></script>
  <script src="js/script.js"></script>
  <!-- end scripts -->

  <!-- Asynchronous Google Analytics snippet. Change UA-XXXXX-X to be your site's ID.
       mathiasbynens.be/notes/async-analytics-snippet -->
  <script>
    var _gaq=[['_setAccount','UA-XXXXX-X'],['_trackPageview']];
    (function(d,t){var g=d.createElement(t),s=d.getElementsByTagName(t)[0];
    g.src=('https:'==location.protocol?'//ssl':'//www')+'.google-analytics.com/ga.js';
    s.parentNode.insertBefore(g,s)}(document,'script'));
  </script>
</body>
</html>