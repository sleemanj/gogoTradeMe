<?php

  /** This is an example of using the Access Token to get the user's watchlist, it's about
   *  the simplest example you can possibly have.
   */
        
  require_once('include/init.php');   
  
  // We set the access token which we stored in callback.php (at some earlier time)
  $AccessToken = retrieve_token('access');
  trademe()->set_token($AccessToken);
  
  // And then simply call the "endpoint" at trademe.
  $Response = trademe()->get('MyTradeMe/Watchlist/All', array('page' =>1, 'rows' => 1));
  
  // We can check the response is OK
  if($Response->is_ok())
  {  
    // Assuming the request worked, you should be able to access the data from it like this...
    //  --- NB: TotalCount will typically be a SimpleXMLElement, so need to have that (int) cast!
    $TotalItemsInWatchlist = (int) $Response->TotalCount;  
        
    // The response object __tostring() returns the body of the response, in this case, raw XML
    // we will just print it out for something to show.
    header('Content-type: text/xml');      
    echo $Response;   
    
    
    // Advanced Note (perhaps for troubleshooting!)
    // -----------------------------------------------------------------------
    // Referencing an OBJECT PROPERTY on the Response automatically performs this 
    //  behind the scenes (for example getting the TotalCount property).
    //
    //     $Response->data()->TotalCount;
    //
    // which is an automatically detected switch between either
    //
    // 1.  $Response->xml()->TotalCount;    
    // 2.  $Response->json()->TotalCount;
    //
    // depending on what type of response we got/you asked for.
    //   
    // If the auto detection isn't working (why not?) then 
    // you can directly get a SimpleXML DOM Structure with the xml() method        
    // and an object hierachy based json decode with the json() method
    // And you can of course use the auto-detecting "data()" method yourself.    
    // -----------------------------------------------------------------------
    
  }
  else
  {
    // If an error did happen (is_ok() is false) then error_message() will give
    // you the error message in the trademe response, or something like that.
    echo $Response->error_message();
  }
?>