<?php

  /** This is an example of using the Access Token to add a delivery address
   */
        
  require_once('include/init.php');   
  
  // We set the access token which we stored in callback.php (at some earlier time)
  $AccessToken = retrieve_token('access');
  trademe()->set_token($AccessToken);
  
  include('_header.php');
  
  if(isset($_REQUEST['Delete']))
  {
    $Response = trademe()->delete('MyTradeMe/DeliveryAddresses/'.$_REQUEST['Delete']);
  }
  
  if(isset($_REQUEST['Address']))
  {  
    // And then simply call the "endpoint" at trademe.
    // $Response = trademe()->SaveNote($_REQUEST['Note']['ListingID'], $_REQUEST['Note']['Note']);
    $Response = trademe()->post('MyTradeMe/DeliveryAddresses::DeliveryAddress', $_REQUEST['Address']);
    
    
    // We can check the response is OK
    if($Response->is_ok())
    {  
      echo "<h1>Success</h1>";
      echo "<p>TradeMe returned the following XML.</p>";
      echo "<pre>".htmlspecialchars($Response->asIndentedXML())."</pre>";      
      echo "<p>You will see it includes a &quot;DeliveryAddressId&quot;, perhaps you want to use that your application, this is how you would get it.</p>";
      echo "<pre>".htmlspecialchars('$TheDeliveryAddressId = (int) $Response->DeliveryAddressId;')."</pre>";
      echo "<p>The cast is because we are talking to an SimpleXMLElement there, to be safe, always cast to what you want (or at least expect).</p>";
    }
    else
    {
      // If an error did happen (is_ok() is false) then error_message() will give
      // you the error message in the trademe response, or something like that.  
      echo "<h1>Failure</h1>";
      echo "<p>Error response from TradeMe follows...</p>";
      echo "<pre>".htmlspecialchars($Response->error_message())."</pre>";
      // echo $Response->error_message();
    }    
  }
  
  
  
  {
    $Addresses = trademe()->get('MyTradeMe/DeliveryAddresses');
    
    ?>
    <h1>Add A Delivery Address</h1>    
    <p>The XML result from the API will be displayed, click back a couple of times to get o the index page when you've satisfied your curiosity.</p>
    <form method="post">
      <table class="css-form-table">
        <tbody>
          <tr><th>Name</th><td><input type="text" name="Address[Name]" /></td></tr>
          <tr><th>Address 1</th><td><input type="text" name="Address[Address1]" /></td></tr>
          <tr><th>Address 2</th><td><input type="text" name="Address[Address2]" /></td></tr>
          <tr><th>Suburb</th><td><input type="text" name="Address[Suburb]" /></td></tr>
          <tr><th>City</th><td><input type="text" name="Address[City]" /></td></tr>
          <tr><th>Post Code</th><td><input type="text" name="Address[Postcode]" /></td></tr>
          <tr><th>Country</th><td><input type="text" name="Address[Country]" /></td></tr>
        </tbody>
      </table>
      <input type="submit" value="Submit" />
    </form>
    
    <h2>Your existing addresses...</h2>
    <p><strong>Attention:</strong> The Default address <strong>has no id</strong>, as a result you can not modify or delete it in the API.</p>
    <?php      
      foreach($Addresses->Address as $Address)
      { 
        ?>
        <fieldset>
          <?php echo htmlspecialchars($Address->Name) ?><br/>
          <?php echo htmlspecialchars($Address->Address1) ?><br/>
          <?php echo htmlspecialchars($Address->Address2) ?><br/>
          <?php echo htmlspecialchars($Address->Suburb) ?><br/>
          <?php echo htmlspecialchars($Address->City) ?><br/>
          <?php echo htmlspecialchars($Address->Postcode) ?><br/>
          <?php echo htmlspecialchars($Address->Country) ?><br/>
          <?php
            if(isset($Address->Id))
            {
              ?>
              [<a href="add_delivery_address.php?Delete=<?php echo $Address->Id ?>">Delete</a>]
              <?php
            }
          ?>
        </fieldset>
        <?php
      }
    ?>
    <?php
  }
  
  include('_footer.php');
?>
