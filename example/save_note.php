<?php

  /** This is an example of using the Access Token to save a note to the user's watchlist.
   */
        
  require_once('include/init.php');   
  
  // We set the access token which we stored in callback.php (at some earlier time)
  $AccessToken = retrieve_token('access');
  trademe()->set_token($AccessToken);
  
  include('_header.php');
  if(isset($_REQUEST['Delete']))
  {
    $Response = trademe()->delete('MyTradeMe/Notes/'.$_REQUEST['Delete']['ListingId'].'/'.$_REQUEST['Delete']['NoteId'].'/0');
  }
  
  if(isset($_REQUEST['Note']))
  {  
    // And then simply call the "endpoint" at trademe.
    // $Response = trademe()->SaveNote($_REQUEST['Note']['ListingID'], $_REQUEST['Note']['Note']);
    $Response = trademe()->post('MyTradeMe/Notes::SaveNoteRequest', array('Text' => $_REQUEST['Note']['Note'], 'ListingId' => $_REQUEST['Note']['ListingID']));
        
    // We can check the response is OK
    if($Response->is_ok())
    {  
      echo "<h1>Success</h1>";
      echo "<p>TradeMe returned the following XML.</p>";
      echo "<pre>".htmlspecialchars($Response->asIndentedXML())."</pre>";      
      echo "<p>You will see it includes a &quot;NoteId&quot;, perhaps you want to use that your application, this is how you would get it.</p>";
      echo "<pre>".htmlspecialchars('$TheNoteId = (int) $Response->NoteId;')."</pre>";
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
    $Watchlist = trademe()->get('MyTradeMe/Watchlist/All.xml');
    
    ?>
    <h1>Add A Note</h1>
    <p>We will add a note to one of your Watchlist items (overwrites any existing note you have on that listing).</p>
    <p>If you don't have any Watchlist items, <a href="save_to_watchlist.php">go add one</a>.</p>

    <form method="post">
      <p><strong>Watchlist Item:</strong></p>      
      <select name="Note[ListingID]">
        <?php
          foreach($Watchlist->List->WatchlistItem as $Listing)
          {
            ?>
            <option value="<?php echo (int) $Listing->ListingId ?>"><?php echo htmlspecialchars((string) $Listing->Title) ?></option>
            <?php
          }
        ?>
      </select>
      
      <p><strong>Note:</strong></p>
      <textarea name="Note[Note]"></textarea>
      <input type="submit" value="Submit" />
    </form>
    <h1>Your Existing Watchlist Notes</h1>
    <dl>
    <?php
      $Watchlist = trademe()->get('MyTradeMe/Watchlist');
      foreach($Watchlist->List->WatchlistItem as $Item)
      {
        if(!isset($Item->Note) || !strlen((string)$Item->Note)) continue;
        
        ?>
        <dt><?php echo htmlspecialchars($Item->Title) ?></dt>
        <dd>
          <p><?php echo htmlspecialchars($Item->Note) ?></p>
          [<a href="save_note.php?Delete[NoteId]=<?php echo $Item->NoteId ?>&amp;Delete[ListingId]=<?php echo $Item->ListingId ?>">Delete</a>]
        </dd>
        <?php
      }
    ?>
    </dl>
    <?php
  }
  
  include('_footer.php');
?>