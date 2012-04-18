<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
        <title>
            Confirm Subscriber Attorney Id Number
        </title>
    </head>
    <body>
        <h2>The Clerk's site list these Attorney Ids</h2>
        <h3>Check each of the Attorney Ids you would like to include in your schedule:</h3>
        <form action="insertSubscriberId.php" method="get" accept-charset="utf-8">

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
if( isset( $_GET["attorneyId"] ) ){
    $attorneyId = strtoupper( $_GET[ "attorneyId" ] );
}
else{
    $attorneyId = "73125";
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
    </body>
</html>
