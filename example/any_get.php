<?php

  /** This is an example of using the Access Token to do an arbitrary request
   */
        
  require_once('include/init.php');   
  
  // We set the access token which we stored in callback.php (at some earlier time)
  $AccessToken = retrieve_token('access');
  trademe()->set_token($AccessToken);
  
  
  
  if(isset($_REQUEST['Endpoint']))
  {  
    // And then simply call the "endpoint" at trademe.
    // $Response = trademe()->SaveNote($_REQUEST['Note']['ListingID'], $_REQUEST['Note']['Note']);
    $Params = array();
    if(trim(@$_REQUEST['Params']))
    {
      foreach(preg_split('/\r?\n/', trim($_REQUEST['Params'])) as $P)
      {
        list($k, $v) = explode('=', $P, 2);
        $Params[$k] = $v;
      }
    }
    
    $Response = trademe()->get($_REQUEST['Endpoint'], $Params);
    
    if(!@$_REQUEST['Raw'])
    { 
      include_once('_header.php');
      // We can check the response is OK
      if($Response->is_ok())
      {            
        echo "<h1>Success!</h1>";      
        echo "<pre>".htmlspecialchars($Response->asIndentedXML())."</pre>";      
      }
      else
      {
        echo "<h1>Failure</h1>";
        echo "<p>Error response from TradeMe follows...</p>";
        echo "<pre>".htmlspecialchars($Response->error_message())."</pre>";
      }
    }
    else
    {
      header('Content-type: text/xml');
      echo $Response;
      exit;
    }
  }
  
  include_once('_header.php');
  {
    
    
    ?>
    <h1>Execute Any GET</h1>    
        
    <form method="post">
      <h2>Endpoint</h2>
      <p>https://api.trademe.co.nz/{version}/<input type="text" name="Endpoint" value="<?php echo isset($_REQUEST['Endpoint']) ? $_REQUEST['Endpoint'] : 'Search/General' ?>" size="40"/>.xml</p>
      
      <h2>Query Parameters</h2>
      <p>Name=Value, one per line, do not url encode (script will do it for you).</p>
      <textarea name="Params" cols="40" rows="5"><?php echo isset($_REQUEST['Params']) ? $_REQUEST['Params'] : "search_string=microlight aircraft\nsort_order=TitleAsc" ?></textarea>
      <p>
      <label>Give the raw XML: <input type="checkbox" name="Raw" value="1" /></label>
      </p>
      <input type="submit" value="Go" />
    </form>    
    <?php
    include('_footer.php');
  }
?>
