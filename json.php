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

// This class exposes objects and methods for converting courtClerk.org pages to associative arrays.
require_once( "class.user.php" );
require_once( "libs/UnitedPrototype/autoload.php" );
ob_start('ob_gzhandler');

$firsttDate = null;
$lastDate = null;

if(isset( $_GET[ "firstdate" ] ))
{
    $fistDate = $_GET[ "firstdate" ];
}

if(isset( $_GET[ "lastdate" ] ))
{
    $lastDate = $_GET[ "lastdate" ];
}

if(isset( $_GET["id"] )){
    $userObj = new user( $_GET["id"], false );
    $userName = $userObj->getFullName();
    echo json_encode( $userObj->getUserSchedule() );
}
else{
    $dummyId ="69613";
    // $dummyId ="73125";
    echo "You must provide an attorneyd id in the URI,\ne.g., MyCourtDates.com/ics.php?id=$dummyId.\n\nThe following is dummy data:\n";
    $userObj = new user( $dummyId, false );
    $userName = $userObj->getFullName();
    echo json_encode( $userObj->getUserSchedule() );
    
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

// Assemble Page information
$pageName=$userName['lName'] . "-" . $userId;
$page = new GoogleAnalytics\Page("/ics/$pageName");
$page->setTitle( $pageName );

// Track page view
$tracker->trackPageview($page, $session, $visitor);

?>