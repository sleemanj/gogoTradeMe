<?php

  /** This is the callback, what we do is take 'oauth_verifier' from our $_REQUEST, and pass this back
   *  to trademe who will give us another token, an access token, which we can use to make actual useful
   *  requests in future.
   */
        
  require_once('include/init.php');   
    
  // We must use the same request token we obtained in connect.php
  $RequestToken = retrieve_token('request');
  trademe()->set_token($RequestToken);
  
  // And now we get the access token using the verifier
  $AccessToken = trademe()->get_access_token($_REQUEST['oauth_verifier']);
  
  // Which we save, because we now can use this to make any future requests 
  //  until the access token expires, which I believe for TradeMe is a year (?)
  store_token($AccessToken);
        
?>
<?php include('_header.php'); ?>

      <h1 class="css-content-legend">We are (in theory) connected To TradeMe</h1>      
         
      <p>
        You don't have to reauthorise unless you lose the Access Token or it expires. So store the Access Token in a database, config file, or whatever so your users don't have to reauthorise all the time.  
      </p>     
      
      <p>This example only stores it in the $_SESSION, that's probably not very useful to you in production but it suffices for the examples :-)</p>
      
      <p>
        Now you can <a href="index.php">try it out with some simple demos</a>.
      </p>
      
    
<?php include('_footer.php'); ?>