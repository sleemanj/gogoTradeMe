<?php
  
  $consumer_secret = NULL;
  $consumer_key    = NULL;
  
  // The callback url is the FULL url to callback.php (in this example!)
  //   if the auto code doesn't work for you, set this to the url to /example/callback.php 
  $callback_url    = 'http://'.$_SERVER['HTTP_HOST'] . preg_replace('/[^\/]+\.php.*/', '', $_SERVER['PHP_SELF']).'callback.php';
    
?>