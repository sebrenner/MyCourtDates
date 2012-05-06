<?php 
// 
//  ics.php
//  This script takes the following arguments:
//  AttorneyId - the pricipal id of the attorney whose schedule is to be built 
//  
//  The other information neede to prepare the ics file can be pulled from the db or flat file.
//  It may make sense to move it to the URI paramaters, but for now it seems smatert to keep it
//  in a db, or maybe a flat file.
//
//  Created by Scott Brenner on 2012-04-06.
//  Copyright 2012 Scott Brenner. All rights reserved.
// 
ob_start('ob_gzhandler');

require_once( "class.user.php" );
require_once( "class.ics.php" );

// This library exposes objects and methods for creating ical files.
require_once( "libs/iCalcreator-2.12/iCalcreator.class.php" );

if(isset( $_GET[ "sumstyle" ] )){
    $sumStyle = $_GET[ "sumstyle" ];
}else{
    $sumStyle = "Sc";
}

if(isset( $_GET["id"] )){
    $userObj = new user( $_GET["id"], false );
    $userName['fName'] = $userObj->getFName();
    $userName['mName'] = $userObj->getMName();
    $userName['lName'] = $userObj->getLName();
    $userId = $userObj->getUserBarNumber();
    $eventsArray = $userObj->getUserSchedule();
    $c = new ICS($eventsArray, $userId, $userName, null, false);
    $c->__get('ics');
}
else{
    // echo    "You must provide an attorneyd id in the URI,\ne.g., MyCourtDates.com/ics.php?id=69613.\n\n\tThe following is dummy data:";
    $userObj = new user( '73125', false );
    $userName['fName'] = $userObj->getFName();
    $userName['mName'] = $userObj->getMName();
    $userName['lName'] = $userObj->getLName();
    $userId = $userObj->getUserBarNumber();
    $eventsArray = $userObj->getUserSchedule();
    $c = new ICS($eventsArray, $userId, $userName, null, false);
    $c->__get('ics');
}


?>