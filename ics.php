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
require_once( "libs/UnitedPrototype/autoload.php" );
require_once( "class.ics.php" );

// This library exposes objects and methods for creating ical files.
require_once( "libs/iCalcreator-2.12/iCalcreator.class.php" );
// This code declares the time zone
ini_set('date.timezone', 'America/New_York');
date_default_timezone_set ('America/New_York');

if(isset( $_GET[ "sumstyle" ] )){
    $sumStyle = $_GET[ "sumstyle" ];
}else{
    $sumStyle = "ac";
}

if( isset( $_GET['reminders'] ) ){
    $reminders = $_GET['reminders'];
    if( isset( $_GET['reminderInterval'] ) ){
        $reminderInterval = $_GET['reminderInterval'];
    }
    else{
      // echo "error no reminderInterval was provided.  Using default - 15 minutes.";
      $reminderInterval = '15 minutes';
    }
}
else{
  // echo "No reminders requested.";
  $reminderInterval = '15 minutes';
}
if(isset( $_GET["id"] )){
    $userObj = new user( $_GET["id"], false );
}
else{
    // echo    "You must provide an attorneyd id in the URI,\ne.g., MyCourtDates.com/ics.php?id=69613.\n\n\tThe following is dummy data:";
    $userObj = new user('PP68519', true );
}
$userName['fName'] = $userObj->getFName();
$userName['mName'] = $userObj->getMName();
$userName['lName'] = $userObj->getLName();
$userId = $userObj->getUserBarNumber();
$eventsArray = $userObj->getUserSchedule();

use UnitedPrototype\GoogleAnalytics;

// Initilize GA Tracker
$tracker = new GoogleAnalytics\Tracker('UA-124185-7', 'mycourtdates.com');

// Assemble Visitor information
// (could also get unserialized from database)
$visitor = new GoogleAnalytics\Visitor();
$visitor->setIpAddress($_SERVER['REMOTE_ADDR']);
$visitor->setUserAgent($_SERVER['HTTP_USER_AGENT']);
$visitor->setScreenResolution('1024x768');

// Assemble Session information
// (could also get unserialized from PHP session)
$session = new GoogleAnalytics\Session();

// Assemble Page information
$pageName=$userName['lName'] . "-" . $userId;
$page = new GoogleAnalytics\Page("/ics/$pageName");
$page->setTitle( $pageName );

// Track page view
$tracker->trackPageview($page, $session, $visitor);

$c = new ICS($eventsArray, $userId, $userName, $sumStyle, $reminderInterval, false);
$c->__get('ics');


?>