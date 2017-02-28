<?php
  
  // Take care that you put your key in consumer_key and secret in consumer_secret, it is 
  // easy to accidentally switch them, and then nothing will work!
  $consumer_key    = NULL;
  $consumer_secret = NULL;
  
  // 'sandbox' or 'live', note that your secret and key are specific to either sandbox or live
  // if you create your secret/key in live, they only work in live, and if you create in sandbox
  // they only work in sandbox.
  $environment     = 'live'; 
  
  // Standard Authentication
  // --------------------------------------------------------------------------
  // If you are going to have "normal people" authenticate (that is, you are 
  // making some service for other people to use against their trademe accounts) 
  // then you would use an OAuth Authentication process.
  //
  // Note that it is required that you accept https to use Standard Auth
  
  // The callback url is the FULL url to callback.php (in this example!)
  //   if the auto code doesn't work for you, set this to the url to /example/callback.php 
  $callback_url    = 'https://'.$_SERVER['HTTP_HOST'] . preg_replace('/[^\/]+\.php.*/', '', $_SERVER['PHP_SELF']).'callback.php';

  // Manual Authentication
  // --------------------------------------------------------------------------
  //
  // If your code only needs to access your account (for example you are
  // writing a thing to import sales from trademe into your systems) then you 
  // can manually authenticate following this process.
  //
  // You do not need https on your website to use manual authentication
      
  // Go to https://developer.trademe.co.nz/api-overview/authentication/
  // Complete the form with the details above
  // Select the appropriate permissions you want to give (all of them is fine)
  // Click Generate Token
  // Copy the Token and Secret below  
  $manual_auth_access_token        = NULL;
  $manual_auth_access_token_secret = NULL;
  
?>