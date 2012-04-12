<?php
   $siteName = 'nettuts';
   // YQL query (SELECT * from feed ... ) // Split for readability
   $path = "http://query.yahooapis.com/v1/public/yql?q=";
   $path .= urlencode("SELECT * FROM feed WHERE url='http://feeds.feedburner.com/$siteName'");
   $path .= "&format=json";
 
   // Call YQL, and if the query didn't fail, cache the returned data
   $feed = file_get_contents($path, true);
    echo $feed;
    print_r($feed);

// Decode that shizzle
$feed = json_decode($feed);
?>