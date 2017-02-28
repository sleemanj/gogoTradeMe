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
      
      <?php
        if($_SERVER['HTTP_PORT'] != 443)
        {
          ?>
          <p>To authenticate to the TradeMe API via OAuth your site (<?php $_SERVER['HTTP_HOST'] ?>) must be accessed over HTTPS, not HTTP</p>
          
          <p>If you are going to be having "normal people" authenticate, this is not avoidable, please configure your system so you can access these files over https, and do so.</p>                    
          <?php
        }
        else
        {
          ?>
          <p>In order to connect to your TradeMe account you must authorise your code to talk to the TradeMe account.</p>
           
          <h2>OAuth Authentication</h2>
          <p>If you are going to have "normal people" authenticate (that is, you are making some service for other people to use against their TradeMe accounts) then you would use an OAuth Authentication process.</p>
                        
          <p><a href="connect.php?Reauthorise=1">Begin Authorisation</a></p>
          <?php
        }
      ?>
      <h2>Manual Authentication</h2>
      <p>If your code only needs to access your account (for example you are writing a thing to import sales from TradeMe into your systems) then you can manually authenticate following this process.</p>
      <ol>
        <?php include('include/config.php'); ?>
        <li>Go to <a href="https://developer.trademe.co.nz/api-overview/authentication/">https://developer.trademe.co.nz/api-overview/authentication/</a></li>
        <li>Environment: <?php echo $environment == 'live' ? 'Production' : 'Sandbox' ?></li>
        <li>Consumer Key: <tt><?php echo $consumer_key; ?></tt></li>
        <li>Consumer Secret: <tt><?php echo $consumer_secret; ?></tt></li>
        <li>Permissions: Select All Options Applicable</li>
        <li>Click Generate Token<li>
        <li>Copy the Generated Token and Token Secret and enter these into the Manual Authentication section of include/config.php</li>
      </ol>
      <p>Note that these tokens don't typically expire, so hard coding is just fine.</p>
<?php include('_footer.php'); ?>