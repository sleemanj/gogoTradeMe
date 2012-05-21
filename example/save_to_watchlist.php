<?php

  /** This is an example of using the Access Token to save a listing to your watchlist
   */
        
  require_once('include/init.php');   
  
  // We set the access token which we stored in callback.php (at some earlier time)
  $AccessToken = retrieve_token('access');
  trademe()->set_token($AccessToken);
  include('_header.php');
  
  if(isset($_REQUEST['Delete']))
  {
    $Response = trademe()->delete('MyTradeMe/Watchlist/'.$_REQUEST['Delete']);
  }
  
  if(isset($_REQUEST['Watchlist']))
  {  
    // And then simply call the "endpoint" at trademe.
    // $Response = trademe()->SaveNote($_REQUEST['Note']['ListingID'], $_REQUEST['Note']['Note']);
    $Response = trademe()->post('MyTradeMe/Watchlist/Add::SaveToWatchlistRequest', $_REQUEST['Watchlist']);
    
    
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
  
  
  {
    
    
    ?>
    <h1>Add Listing To Watchlist</h1>    
    
    <form method="post">
      <table class="css-form-table">
        <tbody>
          <tr><th>Listing Id</th>  <td><input type="text" name="Watchlist[ListingId]" /></td></tr>
          <tr><th>Email Option</th><td>
            <select name="Watchlist[EmailOption]">
              <option>UseDefault</option>
              <option>None</option>
              <option>OneHour</option>
              <option>TwelveHours</option>
              <option>TwentyFourHours</option>              
            </select>
          </td></tr>          
        </tbody>
      </table>
      <input type="submit" value="Submit" />
    </form>
    
    <h2>Your existing watchlist...</h2>
    <ul>
    <?php
      $Watchlist = trademe()->get('MyTradeMe/Watchlist/All');
      
      foreach($Watchlist->List->WatchlistItem as $Item)
      { 
        $ListingURL = trademe()->get_listing_url($Item);
        ?>
        <li>
          <a href="<?php echo $ListingURL ?>"><?php echo $Item->Title; ?></a> 
            [<a href="save_to_watchlist.php?Delete=<?php echo $Item->ListingId ?>">Delete</a>]
        </li>
        <?php
      }
    ?>
    </ul>
    <?php
    include('_footer.php');
  }
?>
