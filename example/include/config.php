<?php
  
  // Take care that you put your key in consumer_key and secret in consumer_secret, it is 
  // easy to accidentally switch them, and then nothing will work!
  $consumer_key    = '';
  $consumer_secret = '';
  
  // 'sandbox' or 'live', note that your secret and key are specific to either sandbox or live
  // if you create your secret/key in live, they only work in live, and if you create in sandbox
  // they only work in sandbox.
  $environment     = 'live'; 
                              
  // The callback url is the FULL url to callback.php (in this example!)
  //   if the auto code doesn't work for you, set this to the url to /example/callback.php 
  $callback_url    = 'http://'.$_SERVER['HTTP_HOST'] . preg_replace('/[^\/]+\.php.*/', '', $_SERVER['PHP_SELF']).'callback.php';
    
?>