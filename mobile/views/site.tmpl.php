<?php include('includes/header.php'); ?>
<body> 

<div data-role="page">

   <header data-role="header" class="MyCourtDates.com">
      <h1>MyCourtDates.com</h1>
   </header><!-- /header -->

<div data-role="content">  
   <ul data-role="listview" data-theme="c" data-dividertheme="d" data-counttheme="e">
   <?php
        foreach ($events as $event) {
         ?>
         <li>
           <h2>
               <a href="article.php?siteName=<?php echo $siteName;?>&origLink=<?php echo urlencode($item->guid->content);?>">
              <?php echo $event->properties->summary->text; ?>
              </a>
           </h2>
           <span class="ui-li-count"><?php echo $comments; ?> </span>
        </li>

   <?php  } ?>
   </ul>
</div><!-- /content -->

   <footer data-role="footer" class="MyCourtDates.com">
      <h4> www.tutsplus.com</h4>
   </footer>
</div><!-- /page -->

</body>
</html>

