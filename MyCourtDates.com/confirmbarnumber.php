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

  <title></title>
  <meta name="description" content="">

  <!-- Mobile viewport optimized: h5bp.com/viewport -->
  <meta name="viewport" content="width=device-width">

  <!-- Place favicon.ico and apple-touch-icon.png in the root directory: mathiasbynens.be/notes/touch-icons -->

  <link rel="stylesheet" href="css/style.css">

  <!-- More ideas for your <head> here: h5bp.com/d/head-Tips -->

  <!-- All JavaScript at the bottom, except this Modernizr build.
       Modernizr enables HTML5 elements & feature detects for optimal performance.
       Create your own custom Modernizr build: www.modernizr.com/download/ -->
  <script src="js/libs/modernizr-2.5.0.min.js"></script>
</head>
<body>
  <!-- Prompt IE 6 users to install Chrome Frame. Remove this if you support IE 6.
       chromium.org/developers/how-tos/chrome-frame-getting-started -->
  <!--[if lt IE 7]><p class=chromeframe>Your browser is <em>ancient!</em> <a href="http://browsehappy.com/">Upgrade to a different browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">install Google Chrome Frame</a> to experience this site.</p><![endif]-->
  <header>

  </header>
  <div role="main">
<?php 
// 
//  confirmSubscriberId.php
//  MyCourtDates
//
//  This php script queries the clerk's site 
//   (courtclerk.org/attorney_schedule_list_print.asp?
//    court_party_id=69587&date_range=) 
//  using the Bar ID, returns the attorney name and the next three event.  
//  Then it displays the events and asks the user to confirm that the bar 
//  number is correct.
//  
//  Created by Scott Brenner on 2012-04-08.
//  Copyright 2012 Scott Brenner. All rights reserved.
// 

require_once("class.user.php");
require_once("functions.case.php");

// Get parameters
if( isset( $_GET['barNumber'] ) ){
    $attorneyId = strtoupper( $_GET['barNumber'] );
}
else{
    echo "\t\tusing default value for testing.\n";
    $attorneyId = '73125';
}

if( isset( $_GET['sumStyle'] ) ){
    $sumStyle = $_GET['sumStyle'];
}
else{
  echo "error no sumStyle was provided.  Using default style.";
  $sumStyle = 'csn';
}

// The user object
// function __construct($userBarNumber, $verbose, $sourceFlag = self::LOGIC, $rangeFlag = self::FUTURE)

$a =  new user($attorneyId, false);

// Output attorney name and next three events.
echo "<h2>The Clerk of Court\'s website indicates that attorney id: $attorneyId belongs to ". 
        $a->getFName() . ' ' . $a->getMName() . ' ' . $a->getLName() . '.</h2>';

echo "<h4>The Hamilton County Clerk of Court's lists " . $a->getActiveNACCount() . " active scheduled court appearances.</h4>";

// Query db, if barnumber is already in the database, then show the old prefs and the new prefs.
echo "this space will show old prefs if prefs are being updated.";

echo "<h4>The next <strong>three</strong> scheduled court appearances:</h4>";

// Print a table list of active NACs
echo "<table border=\"1\">
  <th>Date</th>
  <th>CaseNumber</th>
  <th>Caption</th>
  <th>Setting</th>
  <th>Location</th>
  <th>AttorneyID</th>
  </tr>";

$counter = 0;
foreach ($a->getUserSchedule() as $NAC ) {
    // skip past NACs
    if ( date( "Y m d", strtotime($NAC['timeDate'])) < date( "Y m d" ) ) {continue;}

    if ( $NAC["active"]) {
        echo "<tr>";
    }else{
        echo "<TR BGCOLOR=\"#99CCFF\">";
    };
    echo "
      <td>" . date( "F j, Y, g:i a",  strtotime($NAC['timeDate']) ) . "</td>
      <td><a href=\"" . getHistURI( $NAC['caseNumber']) . "\">" .  $NAC['caseNumber'] . "</a></td>
      <td>" . $NAC['plaintiffs'] . " v. " . $NAC['defendants'] ."</td>
      <td>" . $NAC['setting'] ."</td>
      <td>" . $NAC['location'] ."</td>
      <td>" . "<a href=\"" .
              "http://www.courtclerk.org/attorney_schedule_list_print.asp?court_party_id=" . 
              $attorneyId . 
              "&date_range=prior6\">" .
              $attorneyId .
              "</a></td></tr>";
    $counter ++;
    // only show the next 3 NACs 
    if ( $counter > 2 ) {  break; }
}
echo "</table>";

// Output confirmation form inputs
?>
<h2>Links:</h2>
<?php
echo "<p>Add schedule to <a href='webcal://ics.php?id=$attorneyId'>iPhone</a></p>";
$gCalURL='http://www.google.com/calendar/render?cid=' . rawurlencode("http://mycourtdates.com/ics.php?id=$attorneyId");
echo "<p>Add schedule to <a href='$gCalURL' target='_blank'>Google Calendar</a></p>";
echo "<p>Add schedule to <a href='ics.php?id=$attorneyId'>Outlook</a></p>";
echo "<p><a href='ics.php?id=$attorneyId'>iCal Feed</a></p>";
echo "<p><a href='json.php?id=$attorneyId'>JSON Feed</a></p>";
?>
<h2>Wrong Schedule?</h2>
    <p>If this attorney schedule is not the correct one you can search again:</p>
    <form action="confirmbarnumber.php" method="get" accept-charset="utf-8">
        <label for="barNumber">Bar Number</label><input type="text" name="barNumber" id="barNumber">
        <h2>Summary Style</h2>
        <h5>This is text that will appear as the title of the Court settings.</h5>
        <fieldset>
          <legend>Preferences</legend>
        <table class="form-table">
        	<tr>
        		<td align='left'><label><input name="sumStyle" type="radio" checked value="csn" class="sumStyle"  /> Default (<emp>Caption ~ Full  Setting ~ Case Number</emp>)</label></td>
        		<td>e.g., <code>Marbury v. Madison ~ Motion For Summary Judgment ~ A 1308713</code></td>
        	</tr>
        	<tr>
        		<td align='left'><label><input name="sumStyle" type="radio" value="da" class="sumStyle"  /> Prosecutor/Criminal Defense Style (<emp>Defendant ~ Abbreviated Setting</emp>)</label></td>
        		<td>e.g., <code>Madison ~ DSC</code></td>
        	</tr>
        	<tr>
        		<td align='left'><label><input name="sumStyle" type="radio" value="can" class="sumStyle"  /> Abbreviated (<emp>Caption ~ Abbreviated Setting ~ Case Number</emp>)</label></td>
        		<td>e.g., <code>Marbury v. Madison ~ FE ~ A 1308713</code></td>
        	</tr>
        	<tr>
        		<td align='left'><label><input name="sumStyle" type="radio" value="cal" class="sumStyle"  /> Abbreviated (<emp>Caption ~ Abbreviated Setting ~ Location</emp>)</label></td>
        		<td>e.g., <code>Marbury v. Madison ~ FE ~ H.C. Court House Room 485</code></td>
        	</tr>
        	
          <!-- <tr>
              <td align='left'>
                  <label><input name="sumStyle" id="custom_sumStyle" type="radio" value="custom" class="sumStyle"  checked='checked' />
                  Custom Structure</label>
              </td>
              <td>
                              <input name="permalink_structure" id="permalink_structure" type="text" value="/%category%/%postname%" class="regular-text code" />
              </td>
          </tr> -->
        </table>
    </fieldset>          
        <h2>Reminders</h2>
        <h4>Indicate whether you want your calendar to remind you of scheduled events:</h4>
        <tr>
            <td align='left'>
          <label><input name="reminders" type="checkbox" value="true" class="reminders"  /> Reminders (<emp>Caption ~ Abbreviated Setting ~ Location</emp>)</label>
            </td>
            <td>
                <input name="reminderInterval" id="reminderInterval" type="text" value="15 minutes" class="regular-text code" />
            </td>
        </tr>
        
        <p><input type="submit" value="Continue &rarr;"></p>
    </form>





  </div>
  <footer>

  </footer>


  <!-- JavaScript at the bottom for fast page loading -->

  <!-- Grab Google CDN's jQuery, with a protocol relative URL; fall back to local if offline -->
  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
  <script>window.jQuery || document.write('<script src="js/libs/jquery-1.7.1.min.js"><\/script>')</script>

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