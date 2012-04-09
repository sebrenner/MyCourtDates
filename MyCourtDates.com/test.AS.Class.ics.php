<?php
// 
//  attorneySchedule.Class.Tester.php
//  This script runs tests of the attorneySchedule.Class.php
//
//  Created by Scott Brenner on 2012-04-07.
//  Copyright 2012 Scott Brenner. All rights reserved.
// 

// $attorneyId = "13836";   // Dan Burke no NAC
// $attorneyId = "23890";   // Tim Cutcher ~3 dozen
// $attorneyId = "24523";   // Frank Osborn ~2 dozen
// $attorneyId = "40186";   // Dan Burke no NAC
// $attorneyId = "51212";   // Tom Bruns
// $attorneyId = "73125";   // Knefflin 9 NAC   || Peak memory usage:10053744
// $attorneyId = "76537" ;
// $attorneyId = "82511";   //     || died Memory Usage:85222232
// $attorneyId = "85696";   // CUTCHER/JEFFREY/J ~2 dozen
// $attorneyId = "PP69587";  // Pridemore 92 NAC || Peak memory usage:48833464

require_once( "attorneySchedule.Class.php" );

// Get parameters
if( isset( $_GET["attorneyId"] ) ){
    $attorneyId = strtoupper( $_GET[ "attorneyId" ] );
}
else{
    $attorneyId = "73125";
}

$a =  new AttorneySchedule( $attorneyId );
$a->getICS( 0, "Spdcnlsj" );

?>