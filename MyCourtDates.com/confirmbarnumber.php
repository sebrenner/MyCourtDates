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

      require_once("attorneySchedule.Class.php");

      // Get parameters
      if( isset( $_GET["barNumber"] ) ){
          $attorneyId = strtoupper( $_GET[ "barNumber" ] );
      }
      else{
          $attorneyId = null;
      }
      if( isset( $_GET["attnyType"] ) ){
          $attnyType = $_GET["attnyType"];
      }
      else{
          $attnyType = null;
      }
      // Query and parse clerk's site

      function permuteProsIds( $attorneyId ){
          // asumes that prosecutor id is in one of three forms: P00000, PP00000, or 00000.
          $prosecutorIds = array();
          if ( substr($attorneyId, 0, 2) == "PP" ) {
              $prosecutorIds[] = $attorneyId;             // PP form
              $prosecutorIds[] = substr($attorneyId, 1);  // P form
              $prosecutorIds[] = substr($attorneyId, 1);  // natural form
              return $prosecutorIds;
          }

          if ( substr($attorneyId, 0, 1) == "P" ) {
              $prosecutorIds[] = $attorneyId;             // P form
              $prosecutorIds[] = "P" . $attorneyId;       // PP form
              $prosecutorIds[] = substr($attorneyId, 1);  // natural form
              return $prosecutorIds;
          }

          $prosecutorIds[] = "PP" . $attorneyId;      // PP form
          $prosecutorIds[] = "P" . $attorneyId;       // P form
          $prosecutorIds[] =  $attorneyId;            // natural form
          return $prosecutorIds;
      }


      switch ( $attnyType ) {
          case 'prosc':
              //  indicate querying all three combinations.
              //  get each type of prosecutor id: p, pp, natural.
              $prosecutorIds = permuteProsIds( $attorneyId );
              $i = 0;
              foreach ( $prosecutorIds as $prosecutorId ) {
                  $i++;
                  $a =  new AttorneySchedule( $attorneyId );
                  echo "<input type=\"checkbox\" name=\"attorney$i\" value=\"$prosecutorId\">";
                  echo $a->getAttorneyFName() . " " . $a->getAttorneyMName() . " ";
                  echo $a->getAttorneyLName() . " (". $prosecutorId . ")<br>";
                  echo "<input type=\"hidden\" name=\"idCount\" value=\"$i\" >";
              }
              break;

          case 'pubDef':
              # code...
              break;

          default:
              $a =  new AttorneySchedule( $attorneyId );

              // Output attorney name and next three events.
              echo "<h2>The Clerk of Court's website indicates that attorneyId: $attorneyId belongs to<br />" .  $a->getAttorneyFName() . " " . $a->getAttorneyMName() . " " . $a->getAttorneyLName() . "</h2>";

              // There should probably some code warning the user if this id is already in the db and if it already has addOn ids.

              echo "<h3>" . $a->getAttorneyFName() . " " . $a->getAttorneyMName() . " " . $a->getAttorneyLName() . " is the attoney of record on  " . $a->getActiveCaseCount() . " active cases.</h3>";

              echo "<h4>The Hamilton County Clerk of Court's lists " . $a->getNACCount() . " scheduled court appearances.  " .  $a->getActiveNACCount() . " are active.</h4>";

              echo "<h4>The next <strong>three</strong> shedules appearance:</h4>";

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
              foreach ($a->getNACs() as $NAC ) {
                  // skip past NACs
                  if ( date( "Y m d",  $NAC[ "timeDate" ]) < date( "Y m d" ) ) {continue;}

                  if ( $NAC["active"]) {
                      echo "<tr>";
                  }else{
                      echo "<TR BGCOLOR=\"#99CCFF\">";
                  };
                  echo "
                      <td>" . date( "F j, Y, g:i a",  $NAC[ "timeDate" ] ) . "</td>
                      <td><a href=\"" . $a->getHistURI( $NAC["caseNum"] ) . "\">" .  $NAC["caseNum"] . "</a></td>
                      <td>" . $NAC[ "plaintiffs" ] . " v. " . $NAC[ "defendants" ] ."</td>
                      <td>" . $NAC[ "setting" ] ."</td>
                      <td>" . $NAC[ "location" ] ."</td>
                      <td>" . "<a href=\"" .
                              "http://www.courtclerk.org/attorney_schedule_list_print.asp?court_party_id=" . 
                              $NAC[ "attorneyId" ] . 
                              "&date_range=prior6\">" .
                              $NAC[ "attorneyId" ] .
                              "</a></td></tr>";
                  $counter ++;
                  // only show the next 3 NACs 
                  if ( $counter > 2 ) {  break; }
              }
              echo "</table>";
              break;
      }

      // Output confirmation form inputs
      ?>
                  <p>
                      <input type="hidden" name="attorneyId" value="<?php echo $attorneyId; ?>">
                      <input type="submit">
                  </p>
              </form>
              <p>
                  If this attorney schedule is not the correct one you can search again:
              </p>
              <form action="confirmSubscriberId.php" method="get" accept-charset="utf-8">
                  <p>
                      <input type="text" name="attorneyId" value="0000000" id="attorneyId"><br>
                      <input type="radio" name="attnyType" value="private" checked="checked">Private Counsel (including appointed counsel) <input type="radio" name="attnyType" value="prosc">Prosecutor <input type="radio" name="attnyType" value="pubDef">Public Defender<br>
                      <input type="submit" name="Find" value="Find" id="submit">
                  </p>
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