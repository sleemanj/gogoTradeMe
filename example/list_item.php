<?php

  /** This is an example of using the Access Token to get the user's watchlist, it's about
   *  the simplest example you can possibly have.
   */
        
  require_once('include/init.php');   
  include('_header.php');
  
  // We set the access token which we stored in callback.php (at some earlier time)
  $AccessToken = retrieve_token('access');
  trademe()->set_token($AccessToken);
      
  if(isset($_REQUEST['Listing']))
  {
 
    $Response = trademe()->post('Selling::ListingRequest', $_REQUEST['Listing']);
     
    // We can check the response is OK
    if($Response->is_ok())
    {  
      echo "<h1>Success</h1>";            
      echo "<p><a href=\"".(trademe()->get_listing_url( (string) $Response->ListingId))."\">View Listing</a></p>";
      echo "<p><strong>We submitted the following command (php code).</strong></p>";
      echo "<pre> trademe()->post('Selling::ListingRequest', " . htmlspecialchars(var_export($_REQUEST['Listing'], true)) . "); </pre>";
      echo "<p><strong>TradeMe returned the following XML.</strong></p>";
      echo "<pre>".htmlspecialchars($Response->asIndentedXML())."</pre>";            
    }
    else
    {
      // If an error did happen (is_ok() is false) then error_message() will give
      // you the error message in the trademe response, or something like that.  
      echo "<h1>Failure</h1>";
      echo "<p>Error response from TradeMe follows...</p>";
      echo "<pre>".htmlspecialchars($Response->asIndentedXML())."</pre>";
    }    
  }
  
  ?>
  <h1>List An Item</h1>
  
  <form enctype="multipart/form-data" method="post">
  
    <table>
      <tr>
      
        <th>Category:</th>
        <td>0002-0357-0507- (Computers / Monitors / LCD Monitors
          <input type="hidden" name="Listing[Category]" value="0002-0357-0507-" />
          <p><small>For this example's purposes we are listing to this category.  The api call trademe()->get('Categories') will get you the XML list of categories.</small></p>
        </td>
      </tr>
        
        
        
        <tr><th>Title:</th><td><input type="text" name="Listing[Title]" value="Cool Thing For Sale" maxchars="50" /></td></tr>
        <tr><th>Subtitle:</th><td><input type="text" name="Listing[Subtitle]" value="" maxchars="50" /></td></tr>
        <tr><th>Description:</th><td><input type="text" name="Listing[Description]" value="This is a cool thing.  Buy it now." maxchars="2048" /></td></tr>
        <tr><th>Start Price:</th><td><input type="text" name="Listing[StartPrice]" value="0.50" maxchars="10" /></td></tr>
        <tr><th>Reserve Price:</th><td><input type="text" name="Listing[ReservePrice]" value="0.50" maxchars="10" /></td></tr>
        <tr><th>Buy Now Price:</th><td><input type="text" name="Listing[BuyNowPrice]" value="12" maxchars="10" /></td></tr>
        <tr><th>Duration:</th>     <td><input type="text" name="Listing[Duration]" value="Seven" maxchars="10" /></td></tr>
        <tr><th>End Date Time:</th><td><input type="text" name="Listing[EndDateTime]" value="" maxchars="10" /></td></tr>
        <tr><th>Pickup:</th><td><input type="text" name="Listing[Pickup]" value="Allow" maxchars="10" /></td></tr>
        <tr><th>Is Brand New:</th><td><input type="text" name="Listing[IsBrandNew]" value="1" maxchars="10" /></td></tr>
        <tr><th>Authenticated Members Only:</th><td><input type="text" name="Listing[AuthenticatedMembersOnly]" value="1" maxchars="10" /></td></tr>
        <tr><th>Is Classified:</th><td><input type="text" name="Listing[IsClassified]" value="0" maxchars="10" /></td></tr>
    <!--    
        <tr>
          <th>Open Homes</th>
          <td>
            <table>
              <tr><th>Start</th> <th>End</th></tr>
              <tr>
                <td><input type="text" name="Listing[OpenHomes][OpenHome][0][Start]" /></td>
                <td><input type="text" name="Listing[OpenHomes][OpenHome][0][End]" /></td>
              </tr>
            </table>
          </td>
        </tr>
      -->
      
        <tr><th>Send Payment Instructions:</th><td><input type="text" name="Listing[SendPaymentInstructions]" value="1" maxchars="10" /></td></tr>
      
       
        <tr><th>IsOrNearOffer:</th><td><input type="text" name="Listing[IsOrNearOffer]" value="0" maxchars="10" /></td></tr>
        <tr><th>IsPriceOnApplication:</th><td><input type="text" name="Listing[IsPriceOnApplication]" value="0" maxchars="10" /></td></tr>
        
        <tr><th>Is Bold:</th><td><input type="text" name="Listing[IsBold]" value="0" maxchars="10" /></td></tr>
        <tr><th>Is Featured:</th><td><input type="text" name="Listing[IsFeatured]" value="0" maxchars="10" /></td></tr>
        <tr><th>Is Homepage Featured:</th><td><input type="text" name="Listing[IsHomepageFeatured]" value="0" maxchars="10" /></td></tr>
        <tr><th>HasGallery:</th><td><input type="text" name="Listing[HasGallery]" value="0" maxchars="10" /></td></tr>
        <tr><th>HasGalleryPlus:</th><td><input type="text" name="Listing[HasGalleryPlus]" value="0" maxchars="10" /></td></tr>
        <tr><th>Quantity:</th><td><input type="text" name="Listing[Quantity]" value="" maxchars="10" /></td></tr>
        <tr><th>IsFlatShippingCharge:</th><td><input type="text" name="Listing[IsFlatShippingCharge]" value="" maxchars="10" /></td></tr>
        <tr><th>HasAgreedWithLegalNotice:</th><td><input type="text" name="Listing[HasAgreedWithLegalNotice]" value="1" maxchars="10" /></td></tr>
        
        <tr><th>HomePhoneNumber:</th><td><input type="text" name="Listing[HomePhoneNumber]" value="" maxchars="10" /></td></tr>
        <tr><th>MobilePhoneNumber:</th><td><input type="text" name="Listing[MobilePhoneNumber]" value="" maxchars="10" /></td></tr>
        <tr><th>IsHighlighted:</th><td><input type="text" name="Listing[IsHighlighted]" value="" maxchars="10" /></td></tr>
        <tr><th>HasSuperFeature:</th><td><input type="text" name="Listing[HasSuperFeature]" value="" maxchars="10" /></td></tr>
        
        <tr>
          <th>Photo Ids:</th>
          <td>
          <p>You can see the Upload Photo example to add photos which you can then select when creating a listing.</p>
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
                  <input type="checkbox" value="<?php echo (string)$Photo->Id ?>" name="Listing[PhotoIds][PhotoId][]" />
                  <img src="<?php echo htmlspecialchars($Thumb); ?>" />                  
                </li>
                <?php
              }
            ?>            
            </ul>
          </td></tr>
          
        <tr>
          <th>ShippingOptions:</th>
          <td>
            <table>
              <tr><th>Type</th><th>Price</th><th>Method</th></tr>
              <tr>
                <td><input type="text" name="Listing[ShippingOptions][ShippingOption][0][Type]" value="Custom" maxchars="10" /></td>
                <td><input type="text" name="Listing[ShippingOptions][ShippingOption][0][Price]" value="2.70" maxchars="10" /></td>
                <td><input type="text" name="Listing[ShippingOptions][ShippingOption][0][Method]" value="Standard Post" maxchars="10" /></td>
              </tr>
              <tr>
                <td><input type="text" name="Listing[ShippingOptions][ShippingOption][1][Type]" value="Custom" maxchars="10" /></td>
                <td><input type="text" name="Listing[ShippingOptions][ShippingOption][1][Price]" value="4.95" maxchars="10" /></td>
                <td><input type="text" name="Listing[ShippingOptions][ShippingOption][1][Method]" value="Courier" maxchars="10" /></td>
              </tr>
            </table>              
          </td>
        </tr>
        
        <tr>
          <th>PaymentMethods</th>
          <td>
            <label><input type="checkbox" name="Listing[PaymentMethods][PaymentMethod][]" value="BankDeposit" /> Bank Deposit</label><br/>
            <label><input type="checkbox" name="Listing[PaymentMethods][PaymentMethod][]" value="CreditCard" checked="checked" /> Credit Card</label><br/> 
            <label><input type="checkbox" name="Listing[PaymentMethods][PaymentMethod][]" value="Cash" /> Cash</label><br/>
            <label><input type="checkbox" name="Listing[PaymentMethods][PaymentMethod][]" value="SafeTrader" /> Safe Trader</label><br/>
            <label><input type="checkbox" name="Listing[PaymentMethods][PaymentMethod][]" value="Other" checked="checked" /> Other (see above option "Other Payment Method")</label><br/>
          </td>
        </tr>
        <tr><th>Other Payment Method:</th><td><input type="text" name="Listing[OtherPaymentMethod]" value="Deciduous Forests" maxchars="35" /></td></tr>
        
        <tr><th>IsClearance:</th><td><input type="text" name="Listing[IsClearance]" value="0" maxchars="10" /></td></tr>
        
        <tr>
          <th>Contacts:</th>
          <td>
            <p><small>Only used for classifieds</small></p>
            <table>
              <tr><th>FullName</th><th>PhoneNumber</th><th>AlternatePhoneNumber</th><th>EMail</th></tr>
              <tr>
                <td><input type="text" name="Listing[Contacts][Contact][0][FullName]" value="Ford Prefect" maxchars="10" /></td>
                <td><input type="text" name="Listing[Contacts][Contact][0][PhoneNumber]" value="04-242-4242" maxchars="15" /></td>
                <td><input type="text" name="Listing[Contacts][Contact][0][AlternatePhoneNumber]" value="04-242-4242" maxchars="15" /></td>
                <td><input type="text" name="Listing[Contacts][Contact][0][EMail]" value="ford@example.org" maxchars="100" /></td>                
              </tr>
            </table>              
          </td>
        </tr>
        
        
        <!-- Attributes -->
        <tr>
          <th>Attributes</th>
          <td>These are attributes specific to the category, please see the code at line <?php echo __LINE__ ?> for how this list is generated.</td>
        </tr>
        <?php
          $Attributes = trademe()->get('Categories/0002-0357-0507-/Attributes');      
          
          $x = -1;
          foreach($Attributes->Attribute as $Attribute)
          {
          
            $x++;
            ?>
            <tr>
              <th><?php echo (string) $Attribute->DisplayName ?></th>
              <td>
                <input type="hidden"   name="Listing[Attributes][Attribute][<?php echo $x ?>][Name]" value="<?php echo (string) $Attribute->Name ?>" />

                <?php
                  switch($Attribute->Type)
                  {
                    case 'Boolean':
                    {
                      ?>
                      <input type="hidden"   name="Listing[Attributes][Attribute][<?php echo $x ?>][Value]" value="0" />
                      <input type="checkbox" name="Listing[Attributes][Attribute][<?php echo $x ?>][Value]" value="1" />
                      <?php
                    }
                    break;
                    
                    case 'Decimal':
                    case 'String':                    
                    case 'Integer':
                    {
                      if(isset($Attribute->Options) && count($Attribute->Options->AttributeOption))
                      {                        
                        ?>
                        <select name="Listing[Attributes][Attribute][<?php echo $x ?>][Value]">
                          <?php
                            foreach($Attribute->Options->AttributeOption as $Option)
                            {
                              ?>
                              <option value="<?php echo htmlspecialchars((string) $Option->Value) ?>"><?php echo htmlspecialchars((string) $Option->Display) ?></option>
                              <?php
                            }
                          ?>
                        </select>
                        <?php
                      }
                      else
                      {
                        ?>
                        <input type="text"   name="Listing[Attributes][Attribute][<?php echo $x ?>][Value]" value="17" />
                        <?php
                      }
                      
                      if(isset($Attribute->Range->Lower)) echo " From " . (string) $Attribute->Range->Lower;
                      if(isset($Attribute->Range->Upper)) echo " To " . (string) $Attribute->Range->Upper;
                      if(isset($Attribute->IsRequiredForSell) && (string) $Attribute->IsRequiredForSell == 'true')
                      {
                        echo " (Required)";
                      }
                    }
                    break;
                  }
                ?>
                <input type="hidden"   name="Listing[Attributes][Attribute][<?php echo $x ?>][Type]" value="<?php echo (string) $Attribute->Type ?>" />
              </td>
            </tr>
            <?php
          }
        ?>        
        

    </table>
  
  
    <input type="submit" value="Create Listing" />
  </form>


  <?php
  include('_footer.php');
?>