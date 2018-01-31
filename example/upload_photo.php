<?php

  /** This is an example of using the Access Token to get the user's watchlist, it's about
   *  the simplest example you can possibly have.
   */
        
  require_once('include/init.php');   
  include('_header.php');
  
  // We set the access token which we stored in callback.php (at some earlier time)
  $AccessToken = retrieve_token('access');
  trademe()->set_token($AccessToken);
  
  if(isset($_REQUEST['Delete']))
  {
    trademe()->delete('Photos/'.intval($_REQUEST['Delete']).'/Remove');
  }
    
  if(isset($_FILES['File']))
  {
    // We need to pass the API a filename with an extention, tmp_name doesn't have an extention
    // so we just rename it first.
    $TmpFile = sys_get_temp_dir() . '/' . preg_replace('/[^a-z0-9_.-]/i', '', $_FILES['File']['name']);
    move_uploaded_file($_FILES['File']['tmp_name'], $TmpFile);
       
    // Now call post with the request, that is the end point and a double colon, followed by the 
    //  root element "PhotoUploadRequest" - see the Trademe docs
    // We provide here just the FileName as an argument, the gogoTradeMe connector will do the rest. 
    $Response = trademe()->post('Photos::PhotoUploadRequest', array('FileName' => $TmpFile));

    // Note that the uploaded photo will have a Description (name) at trademe as the basename 
    // of the file (eg example.jpg) if you want to give it a different name, you can pass in an
    // explicit Description parameter instead...
    //
    // $Response = trademe()->post('Photos::PhotoUploadRequest', array('FileName' => $TmpFile, 'Description' => 'Something Else'));
    
    // We can check the response is OK
    if($Response->is_ok())
    {  
      echo "<h1>Success</h1>";
      echo "<p>TradeMe returned the following XML.</p>";
      echo "<pre>".htmlspecialchars($Response->asIndentedXML())."</pre>";      
      echo "<p>You will see it includes a &quot;PhotoId&quot;, perhaps you want to use that your application, this is how you would get it.</p>";
      echo "<pre>".htmlspecialchars('$ThePhotoId = (int) $Response->PhotoId;')."</pre>";
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
  
  
  ?>
  <h1>Upload a Photo</h1>
  <p>This form will upload a photo to your My Photos at TradeMe.</p>
  <form enctype="multipart/form-data" method="post">
    <input type="file" name="File" /><input type="submit" value="Upload" />
  </form>
  <h1>Your Photos</h1>
  <ul>
  <?php
    $Photos = trademe()->get('Photos');
    
    foreach($Photos->List->MemberPhoto as $Photo)
    {
      preg_match('/([1-9]?[0-9])$/', (string) $Photo, $M);
      $Thumb = trademe()->get_thumbnail_url($Photo);
      $Full  = trademe()->get_photo_url($Photo);
      ?>
      <li>
        <img src="<?php echo htmlspecialchars($Thumb); ?>" />
        [<a href="upload_photo.php?Delete=<?php echo $Photo->Id ?>">Delete</a>]
      </li>
      <?php
    }
  ?>
  
  </ul>
  
  <?php
  include('_footer.php');
?>