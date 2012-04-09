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

// This class exposes objects and methods for converting courtClerk.org pages to stand data formats.
require_once( "attorneySchedule.Class.php" );

if(isset( $_GET["id"] )){
    $a =  new AttorneySchedule( $_GET["id"] );
    $a->getICS( 0, "Spdcnlsj" );
}
else{
    echo    "You must provide an attorneyd id in the URI,\n
            e.g., MyCourtDates.com/ics.php?id=69613.";
}

?>