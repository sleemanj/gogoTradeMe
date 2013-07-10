<?php include('_header.php'); ?>
<?php
  include('include/config.php');
  ?>

    
  <?php
  if(!isset($consumer_key) || !isset($consumer_secret))
  {
    ?>
    <p>Before continuing, you must edit example/include/config.php to set your consumer key/secret, do that now.</p>
    <p>PS: You get a consumer key/secret by going to <a href="http://www.trademe.co.nz/MyTradeMe/Api/DeveloperOptions.aspx">My Trade Me &gt; My Applications &gt; Developer Options</a> and registering a (your) new application, where it asks for a Callback URL, just enter "oob", we will override it anyway.</p>
    <?php
  }
  else
  {
    include('include/init.php');
    try
    {
      $AccessToken = retrieve_token('access');
      ?>
      <p>We seem to be "authorized" now, so let's do some stuff!</p>
      <p><strong>PS: Best you go add a listing or two to your watchlist, since we will be demoing stuff mainly about that for simplicity.</strong></p>
      <ul>
        <li><a href="connect.php">Authorize again, just for fun.</a> - you don't need to unless your AccessKey expires or gets lost.</li>        
        <li><a href="watchlist.php">Dump your Watchlist XML</a> - a simple GET.</li>
        <li><a href="save_to_watchlist.php">Add a listing to your watchlist</a> - a simple "XML POST".</li>
        <li><a href="save_note.php">Add a note to a listing</a> - a simple "XML POST".</li>
        <li><a href="add_delivery_address.php">Add a delivery address</a> - a simple "XML POST".</li>
        <li><a href="upload_photo.php">Upload a photo to your My TradeMe</a> - a complicated "XML POST" which has been made very simple by gogoTradeMe.</li>
        <li><a href="list_item.php">List an item</a> - specifically in the Computers/Monitors/LCD Category, demonstrates listing a general item with attributes (monitor size).</li>        
      </ul>
      <?php
    }
    catch(Exception $E)
    {
      ?>
      <p>To get started with the API, you (and eventually your users) must "authorize" your application, I like to call this "Connecting to Trade Me", easy for the punters to understand.</p>
      <p><a href="connect.php">Connect To Trade Me</a></p>
      <?php
    }
  }
?>
<?php include('_footer.php'); ?>
