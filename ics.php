<?php 
// 
//  ics.php
//  This script takes the following arguments:
//
//  id - the CourtClerk.org attorney id whose schedule is to be built 
//  sumstyle - this string is the style of the event summary, i.e., title.  
//      See class.ics for deatils.
//  reminders - this determines whether or not the subscriber want reminder.
//      If an integer is passed it will treat it as minuutes.
//      It will alse take a variety of time format, e.g., 2 hours, 1 day, 1 week.
//
//  Created by Scott Brenner on 2012-04-06.
//  Copyright 2012 Scott Brenner. All rights reserved.
// 
ob_start('ob_gzhandler');

require_once( "class.user.php" );
require_once( "libs/UnitedPrototype/autoload.php" );
require_once( "class.ics.php" );
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
    if (is_int($_GET['reminders'])) {
        # if reminders is an integer, treat it a minutes
        $reminderInterval = $_GET['reminders'];
        $reminderInterval .= " minutes";
    }
    else{
        $reminderInterval = $_GET['reminders'];
    }
}

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

if(isset( $_GET["id"] )){
    $userObj = new user( $_GET["id"], false );
    $userName['fName'] = $userObj->getFName();
    $userName['mName'] = $userObj->getMName();
    $userName['lName'] = $userObj->getLName();
    $userId = $userObj->getUserBarNumber();
    $c = new ICS($userObj->getUserSchedule(), $userObj->getUserBarNumber(), $userName, $sumStyle, $reminderInterval, false);

    // Assemble Page information
    $pageName=$userName['lName'] . "-" . $userId;
    $page = new GoogleAnalytics\Page("/ics/$pageName");
    $page->setTitle( $pageName );

    // Track page view
    $tracker->trackPageview($page, $session, $visitor);

    $c->__get('ics');
}
else{

    // Assemble Page information
    $page = new GoogleAnalytics\Page("/ics/NoUserIDSupplied");
    $page->setTitle( $pageName );

    // Track page view
    $tracker->trackPageview($page, $session, $visitor);
    echo "No Attorney Id Was Provided";
}

// Assemble Page information
$pageName = $userName['lName'] . "-" . $userId;
$page = new GoogleAnalytics\Page("/ics/$pageName");
$page->setTitle( $pageName );


?>
