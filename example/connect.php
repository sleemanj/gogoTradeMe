<?php

  /** This is the connector, your user would use this page when they are connecting your application (/website)
   *  to TradeMe, once they have "connected", they don't need to do it again until that "connection" expires
   *  (which is 12 months I think?), or of course, if you forget the "Access Token"!
   */
      
  require_once('include/init.php');   
    
  
  // This is where the work really starts, if we have been asked to (re)authorise by the user, 
  if(isset($_REQUEST['Reauthorise']))
  {        
    // We get a request token, by providing oauth with OUR url which will handle the "verifier callback"
    //   NOTE: in oauth, "callback" means "TradeMe redirects the user to this url", not a server->server request
    $RequestToken = trademe()->get_request_token();
    
    // We need to get access to this later, so store it however you want to    
    store_token($RequestToken);
    
    // Now we get a URL from oauth to where we need to redirect the user
    $RedirectURL = trademe()->get_authorize_url();
    
    // And we send them there, and stop processing
    header('location: '.$RedirectURL );
    exit;
  }
    
?>
<?php include('_header.php'); ?>
      <h1 class="css-content-legend">Connect To TradeMe</h1>    
      <p>In order to connect to your TradeMe account you must complete the authorisation process, simply click on the link below and you will be redirected to TradeMe to authorise access to your account.</p>
          
      <p><a href="connect.php?Reauthorise=1">Begin Authorisation</a></p>
<?php include('_footer.php'); ?>