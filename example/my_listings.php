<?php

  /** This is an example of using the Access Token to get the user's watchlist, it's about
   *  the simplest example you can possibly have.
   */
        
  require_once('include/init.php');   
  include('_header.php');
  
  // We set the access token which we stored in callback.php (at some earlier time)
  $AccessToken = retrieve_token('access');
  trademe()->set_token($AccessToken);
      
  
  ?>
  <style type="text/css">
    .css-listing { display:inline-block; width:233px; margin:10px; border:1px solid black; padding:10px; border-radius:10px; }
    .css-listing .css-title { font-size:larger; color:black; text-decoration:none; display:block; }
    .css-listing .css-price { font-weight:bold;  display:block; }
  </style>
  <h1>Listing Gallery</h1>
  <p> These are the listings that you currently have on Trade Me... </p>
  
  <?php
    $PerPage      = 50;    // Trade Me may limit this further than you request
    $AllOnOnePage = false; // Will retrieve all your listings and put on one page
    
    $Page    = isset($_REQUEST['Page']) ? intval($_REQUEST['Page']) : 1;
    do
    {
      $Result = trademe()->get('MyTradeMe/SellingItems',array('rows'=>$PerPage, 'page' => $Page++, 'photo_size' => 'Gallery'));
      if(!$Result->is_ok()) 
      {
        ?>
        <div>
          <h1>Failure</h1>
          <p>Error response from Trade Me follows...</p>
          <?php echo "<pre>".htmlspecialchars($Response->error_message())."</pre>"; ?>
        </div>
        <?php
        break;
      }
      
      foreach($Result->List->Item as $Listing)
      {
        ?>
        <div class="css-listing">
          <a href="<?php echo htmlspecialchars(trademe()->get_listing_url($Listing->ListingId)) ?>"><img src="<?php echo htmlspecialchars($Listing->PictureHref) ?>" /></a>
          
          <a href="<?php echo htmlspecialchars(trademe()->get_listing_url($Listing->ListingId)) ?>" class="css-title"><?php echo htmlspecialchars($Listing->Title) ?></a>
          
          <span class="css-price"><?php echo htmlspecialchars($Listing->PriceDisplay); ?></span>
        </div>
        <?php
      }
    }
    while($AllOnOnePage && ($Result->TotalCount > $PerPage*($Page-1)));
    
    if(!$AllOnOnePage && ($Result->TotalCount > $PerPage*($Page-1)))
    {
      ?>
      <p>
        <a href="my_listings.php?Page=<?php echo htmlspecialchars($Page) ?>">Next Page</a>
      </p>
      <?php
    }
  ?>
  
  <?php
  include('_footer.php');
?>