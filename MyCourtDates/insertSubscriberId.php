<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>
			Confirm Subscriber Bar Number
		</title>
	</head>
	<body>

<?php
include("passwords/todayspo_MyCourtDates.php");
if(isset( $_GET["id"] )){
    $a =  new AttorneySchedule( $_GET["id"] );
    $a->getICS( 0, "Spdcnlsj" );

if(isset( $_GET["id"] )){
        $a =  new AttorneySchedule( $_GET["id"] );
        $a->getICS( 0, "Spdcnlsj" );


// Submit confirmation to db  Add attorney id to db
try 
{
    $dbh = mysql_connect( $dbHost, $dbAdmin, $dbAdminPassword) or die(mysql_error());
    mysql_select_db( $db ) or die(mysql_error());    
}

catch(PDOException $e)
{
    echo $e->getMessage();
    echo "<br><br>Database $db -- NOT -- loaded successfully .. ";
    die( "<br><br>Query Closed !!! $error");
}

$query ="  INSERT INTO addOnAttorneyIDTable ( principalAttorneyId, addOnAttorneyId)
            VALUES ( '$attorneyId','$attorneyId' )";

echo $query;

$result = mysql_unbuffered_query($query) or die(mysql_error());
echo $result;
mysql_close( $dbh );

?>

	</body>
</html>
