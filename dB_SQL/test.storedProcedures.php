<?php
/**
 * This class provides a test suite of data and functions for testing  
 * MySQL queries in the MyCourtDates database.
 *
 * @package myCourtDates
 * @author Scott Brenner
 **/
class dbTest
{
    protected $dbh;
    public $user01 = array();
    public $NAC01 = array();
    public $case01 = array();
    public $addOneBarNumber01 = array();
    public $barNumberCaseNumber01 = array();

    /**
     * The constructor connects to the database and initializes the
     * db connection object.
     *
     * @return void
     * @author Scott Brenner
     **/
	function __construct() {
	    self::mockData();
	    echo "Test object constructed.";
	}
    /**
     * setDbUser insert/updates a user into the db.
     * Takes an associate array of user data
     * Returns true if the data is successfully inserted/updated
     *  
     * @return bool
     * @param array
     * @author Scott Brenner
     **/
    function setDbUser ( &$user ){
        // PDO
        // print_r( $user );
        include("../MyCourtDates.com/passwords/todayspo_MyCourtDates.php");
        $pdo = new PDO( "mysql:dbname=$db; host=$dbHost", $dbReader, $dbReaderPassword );
        $query = "CALL setUser_tbl(" .
            "\"" . $user[ "userBarNumber" ] . "\" ," .
            "\"" . $user[ "fName"  ] . "\" ," . 
            "\"" . $user[ "mName"  ] . "\" ," . 
            "\"" . $user[ "lName"  ] . "\" ," . 
            "\"" . $user[ "email"  ] . "\" ," . 
            "\"" . $user[ "phone"  ] . "\" ," . 
            "\"" . $user[ "mobile"  ] . "\" ," . 
            "\"" . $user[ "street"  ] . "\" ," . 
            "\"" . $user[ "street2" ] . "\" ," . 
            "\"" . $user[ "city" ] . "\" ," . 
            "\"" . $user[ "state" ] . "\" ," . 
            "\"" . $user[ "zip" ] . "\" ," . 
            "\"" . $user[ "subStart" ] . "\" ," . 
            "\"" . $user[ "subExpire" ] . "\" )";
        echo "\n" . $query;
        
        $pdo->exec( $query );
        $pdo->commit();
        
        // $stmt = $pdo->prepare( $query );
        // $stmt->execute();
    }
    /**
     * takes a bar number as a string
     * returns the date of expiration as a string
     *
     * @param   userBarNumber
     * @return  string
     * @author  Scott Brenner
     **/
    function getUserExpirationNotStoredPro( $userBarNumber ){
        // PDO
        include("../MyCourtDates.com/passwords/todayspo_MyCourtDates.php");
        $pdo = new PDO( "mysql:dbname=$db; host=$dbHost", $dbReader, $dbReaderPassword );
        foreach( $pdo->query( "SELECT subExpire FROM user_tbl WHERE userBarNumber = \"$userBarNumber\";" ) as $row )
        {
            $expDate = $row[ "subExpire"];
        }
        return $expDate;
    }
    function getUserExpirationStoredPro( $userBarNumber ){
        // PDO
        include("../MyCourtDates.com/passwords/todayspo_MyCourtDates.php");
        $pdo = new PDO( "mysql:dbname=$db; host=$dbHost", $dbReader, $dbReaderPassword );

        $pdo->query("CAST getUserExpiration( $userBarNumbe, @EXPDATE)"); 
        $result = $dbh->query("SELECT @EXPDATE");
        print_r( $result );
    }
    
    /**
     * initialize mock data
     *
     * @return void
     * @author Scott Brenner
     **/
    function mockData (){
        $this->user01 = array(
            "userBarNumber" => "user1_BarNum",
            "fName"         => "user1_fName",
            "lName"         => "user1_lName",
            "mName"         => "user1_mName",
            "email"         => "user1_email",
            "phone"         => "user1_phone",
            "mobile"        => "user1_mobile",
            "street"        => "user1_strt",
            "street2"       => "user1_strt1",
            "city"          => "user1_city",
            "state"         => "S1",
            "zip"           => "user1_zip",
            "subStart"      => "2011-10-19",
            "subExpire"     => "2014-10-19"
        );
        $this->NAC01 = array(
            "id"            => "NAC01",
            "active"        => "1",
            "caseNumber"    => "case01_caseNum",
            "timeDate"      => "2014-10-19 09:00:00",
            "setting"       => "Jury Trial",
            "location"      => "H.C. COURT HOUSE ROOM 121"
        );
        $this->case01 = array(
            "caseNumber"    => "case01_caseNum",
            "plaintiffs"    => "case01_plantiff",
            "defendants"    => "case01_defendant",
            "judge_id"      => 125,         // Use real judge ids
            "filedDate"     => "2012-04-01",
            "totDeposit"    => 22.22,        // all test ammounts will repaeat the sime digit
            "totCosts"      => 11.11,
            "race"          => "case01_race",
            "dob"           => "1973-09-10", // all test dob are 1973-09-10
            "sex"           => "M",
        );
        $this->addOneBarNumber01 = array(
            "userBarNumber" => "user1_BarNum",
            "addOnBarNumber"=> "user1_BarNum"
        );
        $this->barNumberCaseNumber01 = array(
            "barNumber"     => "user1_BarNum",
            "caseNumber"    => "case01_caseNum",
            "type"          => "ofRecord"
        );       
    }
} // END class 


?>
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
<h1>This page displays the results of several test calls to stored procedures in the My CourtDate.com database.</h1>


  </header>
  <div role="main">
  <?php
    $t =  new dbTest( );
    // $t::getUserExpirationPDO( "73125" );
    // $t::getUserExpirationNotStoredPro( "73125" );
    $t::setDbUser( $t->user01 );
  ?>


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