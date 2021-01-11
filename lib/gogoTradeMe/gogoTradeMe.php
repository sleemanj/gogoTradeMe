<?php
  /*
    License
    ==============================================================================
    Copyright (C) 2011 by Gogo Internet Services Limited

    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is
    furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in
    all copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
    THE SOFTWARE.
  */
  
  require_once(dirname(__FILE__).'/../gogoOAuth/gogoOAuth.php');
  require_once('gogoTradeMeResponse.php');
  
  /** Extention of gogoOAuth which provides for using the TradeMe (http://www.trademe.co.nz/) API
   *
   */
  
  class gogoTradeMe extends gogoOAuth
  {
     protected $APIBaseURL            = 'https://api.trademe.co.nz/v1/';    
     protected $OAuthBaseURL          = 'https://secure.trademe.co.nz/Oauth/';
     protected $PhotosURLPrefix       = 'http://images.trademe.co.nz/photoserver/';
     protected $MainWebsiteURL        = 'http://www.trademe.co.nz/';
     
     protected $ResponseClass         = 'gogoTradeMeResponse';  
     protected $DefaultAPIEndpintExtention   = 'xml';        
     protected $DefaultNamespaceForXMLPost   = 'http://api.trademe.co.nz/v1';
    
    /**
     * Get the URL of the authorization page.
     *     
     * @return string The Authorization URL.
     */
     
    public function get_authorize_url($Scope='MyTradeMeRead,MyTradeMeWrite,BiddingAndBuying')
    {           
      return parent::get_authorize_url() . '&scope='.$Scope;
    }
    
    /** 
     * Get the URL to a stored photo.
     *
     * @param int|object Either the Id of the photo, or a response containing 'Id' element
     * @return string URL
     */
     
    public function get_photo_url($PhotoId)
    {
      if(is_object($PhotoId) && isset($PhotoId->Id)) $PhotoId = (string) $PhotoId->Id;
      preg_match('/([1-9]?[0-9])$/', (string) $PhotoId, $M);
      return $this->PhotosURLPrefix . $M[1] . '/'.$PhotoId . '_full.jpg';
    }
    
    /** 
     * Get the URL to a stored photo's thumbnail.
     *
     * @param int|object Either the Id of the photo, or a response containing 'Id' element
     * @return string URL
     */
     
    public function get_thumbnail_url($PhotoId)
    {
      if(is_object($PhotoId) && isset($PhotoId->Id)) $PhotoId = (string) $PhotoId->Id;
      preg_match('/([1-9]?[0-9])$/', (string) $PhotoId, $M);
      return $this->PhotosURLPrefix . 'thumb/'. $M[1] . '/'.$PhotoId . '.jpg';
    }
    
    
    /** 
     * Get the URL to a listing.
     *
     * @param int|object Either the ListingId of the listing, or a response containing 'ListingId' element
     * @return string URL
     */
     
    public function get_listing_url($ListingId)
    {
      if(is_object($ListingId) && isset($ListingId->ListingId)) $ListingId = (string) $ListingId->ListingId;      
      return $this->MainWebsiteURL . 'Browse/Listing.aspx?id='.$ListingId;
    }
          
    /**
     * Get an array of data which can be submitted as ->post('Selling/Edit::EditListingRequest', $Data)
     *
     * Example, setting expiry date 8 hours in future:
     *
     *   $EditingData = trademe()->get_listing_for_edit(123456789);
     *   $EditingData['Duration']    = 'EndDate';
     *   $EditingData['EndDateTime'] = date('c', time() + 60*60*8);
     *   trademe()->post('Selling/Edit::EditListingRequest', $EditingData);
     *
     * A whole load of cautions!!
     *  Due to inconsistency in the Trade Me API, we can not guarantee that the data you get,
     *  if submitted as an edit will not have unintended consequences.  Especially for "special"
     *  listing types like Vehicles.  Ensure that if your use this function, you take
     *  special care in your testing that it works as you desire it to.
     *
     *  The Selling/Edit API does not support these things at all, which may be applicable only to special types of listings...
     *    RegionId , Region, Suburb, GeographicLocation
     *    Dealership
     *    Agency
     *    ContactDetails            - this is used as "Contacts" (above), using the member's email address as well
     *
     *  Additional notes about Selling/Edit...
     *    HasBuyNow                 - This is determined by BuyNowPrice
     *    IsBuyNowOnly, HasMultiple - This is determined by there being a Quantity and a BuyNowPrice
     *    Description               - You can set $Data['Description'] to a string and it will be converted appropriatly
     *    
     *  Previous versions of the API had these additional cautions which are probably now OK...
     *    Duration                 was calculated (guessed) or may be undefined, recommend to always set this yourself
     *    SendPaymentInstructions  was always set true     
     *    IsPriceOnApplication     was undefined (false)
     *    HasGalleryPlus           was probably correct, but not certain
     *    HasAgreedWithLegalNotice was always true
     *    HomePhoneNumber          may have been correct, uncertain
     *    MobilePhoneNumber        may have been correct, uncertain
     *    HasSuperFeature          was undefined (false)
     *    PaymentMethods           was best guess 
     *    OtherPaymentMethod       was best guess if any
     *    IsClearance              was undefined (false)
     *    Contacts                 was best guess (probably only applies to Classifieds, may not be equivalent to current listing)
     * 
     */
     
    public function get_listing_for_edit($ListingId)
    {
      $FullListing = $this->get('Selling/Listings/'.(int)$ListingId);
      if(!$FullListing->is_ok())            return NULL;
      
      $Edit = array_pop($this->codec()->xml_to_array($FullListing));
            
      if(!isset($Edit['Description']) && isset($Edit['Body']))
      {
        $Edit['Description']         = $Edit['Body']; // Note that Body is a plain string
                                                      // Description needs <paragraph> child elements, we will do that 
                                                      // in validate_post_xml_selling 
      }
                                                    
      if(!isset($Edit['Duration']))
      {
        $Edit['Duration']           = round(((strtotime($Edit['EndDate']) - strtotime($Edit['StartDate'])) / (60*60*24)));                                              
      
        if(abs(((strtotime($Edit['EndDate']) - strtotime($Edit['StartDate'])) / (60*60*24)) - $Edit['Duration']) > (2/24))
        {
          // If the calculated duration is different to the end date by more than 2 hours we assume then it must be an EndDate
          //  but because that could cost money unexpectedly if you didn't change it, we will remove this all togethor
          unset($Edit['Duration']);
        }
        else
        {
          // We only handle up to 10 day
          switch($Edit['Duration'])
          {
            case 2: $Edit['Duration'] = 'Two'; break;
            case 3: $Edit['Duration'] = 'Three'; break;
            case 4: $Edit['Duration'] = 'Four'; break;
            case 5: $Edit['Duration'] = 'Five'; break;
            case 6: $Edit['Duration'] = 'Six'; break;
            case 7: $Edit['Duration'] = 'Seven'; break;
            case 8: $Edit['Duration'] = 'Eight'; break;
            case 9: $Edit['Duration'] = 'Nine'; break;
            case 10: $Edit['Duration'] = 'Ten'; break;
            default : unset($Edit['Duration']);
          }
        }
      }
      
      if(!isset($Edit['EndDateTime']) && isset($Edit['EndDate']))
      {
        $Edit['EndDateTime']        = $Edit['EndDate'];
      }
      
      if(!isset($Edit['Pickup']) && isset($Edit['AllowsPickups']))
      {
        $Edit['Pickup']             = $Edit['AllowsPickups'];
      }
      
      if(!isset($Edit['IsBrandNew']) && isset($Edit['IsNew']))
      {
        $Edit['IsBrandNew']         = $Edit['IsNew'];
      }
      
      if(!isset($Edit['IsHomepageFeatured']) && isset($Edit['HasHomePageFeature']))
      {
        $Edit['IsHomepageFeatured'] = $Edit['HasHomePageFeature'];
      }
      
      if(!isset($Edit['HasSuperFeature']) && isset($Edit['IsSuperFeatured']))
      {
        $Edit['HasSuperFeature']    = $Edit['IsSuperFeatured'];
      }
      
      if(!@$Edit['StartPrice'])     $Edit['StartPrice'] = $Edit['BuyNowPrice'];
      
      $Edit['PhotoIds'] = array();
      foreach($FullListing->Photos->Photo as $P)
      {
        $Edit['PhotoIds']['PhotoId'][] = (int) $P->PhotoId;
      }
              
      // FIXME - not available in any API :(
      {
        if(!isset($Edit['Duration']))                 $Edit['Duration']                 = @$Edit['ListingLength'];
        if(!isset($Edit['SendPaymentInstructions']))  $Edit['SendPaymentInstructions']  = 1;               
        if(!isset($Edit['IsPriceOnApplication']))     $Edit['IsPriceOnApplication']     = NULL;
        if(!isset($Edit['HasGalleryPlus']))           $Edit['HasGalleryPlus']           = isset($Edit['RemainingGalleryPlusRelists']);
        if(!isset($Edit['HasAgreedWithLegalNotice'])) $Edit['HasAgreedWithLegalNotice'] = 1;
        
        if(!isset($Edit['HomePhoneNumber']))          $Edit['HomePhoneNumber']   = @$Edit['ContactDetails']['PhoneNumber'];
        if(!isset($Edit['MobilePhoneNumber']))        $Edit['MobilePhoneNumber'] = @$Edit['ContactDetails']['MobilePhoneNumber'];
        
        // Best guess at the payment methods offered..
        if(isset($Edit['PaymentMethods']['PaymentMethodDetail']))
        {
          $PM = array();
          foreach($Edit['PaymentMethods']['PaymentMethodDetail'] as $D)
          {
            $PM[] = $D['Id'];
          }
          $Edit['PaymentMethods'] = array('PaymentMethod' => $PM);
        }
        elseif(!isset($Edit['PaymentMethods']))
        {
          $Edit['PaymentMethods'] = array('PaymentMethod' => array());
          
          foreach(explode(',', (string) $FullListing->PaymentOptions) as $Pmt)
          {
            if(!trim($Pmt)) continue;
            switch(strtolower(preg_replace('/\s+/', '', $Pmt)))
            {
              case 'nzbankdeposit': // LOWER CASE NO SPACE
                $Edit['PaymentMethods']['PaymentMethod'][] = 'BankDeposit';
                break;
                
              case 'cash': // LOWER CASE
                $Edit['PaymentMethods']['PaymentMethod'][] = 'Cash';
                break;
                
              case 'paynow':  
              case 'creditcard':
                $Edit['PaymentMethods']['PaymentMethod'][] = 'CreditCard';
                break;
                
              case 'safetrader':
                $Edit['PaymentMethods']['PaymentMethod'][] = 'SafeTrader';
                
              default:
                $Edit['PaymentMethods']['PaymentMethod'][] = 'Other';
                $Edit['OtherPaymentMethod'] = $Pmt;
            }
          }
          
          
          if(!count($Edit['PaymentMethods']['PaymentMethod'])) 
          {
            $Edit['PaymentMethods']['PaymentMethod'] = 'None';
          }
        }
        
        if(!isset($Edit['IsClearance']))              $Edit['IsClearance']       = NULL;
        
        if(!isset($Edit['Contacts']['Contact']))
        {
          if(isset($Edit['ContactDetails']['ContactName']))
          {
            $Edit['Contacts']['Contact'] = array('FullName' => $Edit['ContactDetails']['ContactName'],
                                                  'PhoneNumber' => $Edit['ContactDetails']['PhoneNumber'],
                                                  'AlternatePhoneNumber' => $Edit['ContactDetails']['MobilePhoneNumber'],
                                                  'Email'    => $Edit['Member']['Email']);
          }
        }
      }
      
      // Run through the validator to make it easier for the developer using this
      // function to see what's available.      
      $Xml = new SimpleXMLElement($this->codec()->array_to_xml(array('EditListingRequest' => $Edit)));
      $this->validate_post_xml_selling('Selling/Edit', $Xml);
      
      // Return as an array to make it easy.
      $Edit = $this->codec()->xml_to_array($Xml);
      
      // For simplicity for the user, reset the description to a plain string
      // instead of an array of <Paragraph>
      if(isset($Edit['Description']['Paragraph']))
      {
        $Body = '';
        if(is_array($Edit['Description']['Paragraph']))
        {
          foreach($Edit['Description']['Paragraph'] as $Paragraph)
          {
            $Body .= "{$Paragraph}\n\n";
          }
        }
        else
        {
          $Body = $Edit['Description']['Paragraph'];
        }
        $Edit['Description'] = trim($Body);
      }
      return $Edit['EditListingRequest'];
    }
    /** Validate and potentially modify the provided XML which is going to be posted.
     *
     *  @param string The method being called, if post_xml() was called with a ::RootElement suffix,
     *          this has already been removed and wrapped around the XML.
     *  @param object A SimpleXMLElement, if not one, it will be turned into one.  The validator
     *          may change the xml in other ways, eg adding default values.
     *  @return bool
     */
     
    protected function validate_post_xml($method, SimpleXMLElement &$xml)
    {                    
      switch(ucfirst(strtolower(preg_replace('/[.\/].*$/', '', $method))))
      {
        case 'Photos'       :  return $this->validate_post_xml_photos($method, $xml);
        case 'Mytrademe'    :  return $this->validate_post_xml_mytrademe($method, $xml);
        case 'Bidding'      :  return $this->validate_post_xml_bidding($method, $xml);
        case 'Listings'     :  return $this->validate_post_xml_listings($method, $xml);
        case 'Member'       :  return $this->validate_post_xml_member($method, $xml);
        case 'Search'       :  return $this->validate_post_xml_search($method, $xml);
        
        case 'Localities'   :
        case 'Tmareas'      :
        case 'Categories'   :  return $this->validate_post_xml_catalogue($method, $xml);
        
        case 'Favourites'   :  return $this->validate_post_xml_favourites($method, $xml);
        
        case 'Fixedpriceoffers'   :  return $this->validate_post_xml_fixedpriceoffers($method, $xml);
        case 'Selling':        return $this->validate_post_xml_selling($method, $xml);        
        default: 
         throw new Exception("Unknown Post Type " . ucfirst(strtolower(preg_replace('/[.\/].*$/', '', $method))) . ".  Maybe the API has added a new thing we don't understand yet.");
      }      
      
      parent::validate_post_xml($method, $xml);
    }
          
    /** Validate XML used in posting to the Photos methods
     *
     *  Uploading a photo is a particularly special case, the validator will handle everything for you, just
     *  be sure to provide element FileName and have it point to a file on the disk, the signature etc is 
     *  automatically done.
     *
     *  @param string
     *  @param SimpleXMLElement
     *  @throws UnexpectedValueException
     *  @throws DomainException If the XML root element/method is unrecognised.
     */
     
    private function validate_post_xml_photos($method, &$xml)
    {
      
      switch($xml->getName())
      {
        case 'PhotoUploadRequest':
        {
          if(!isset($xml->FileName))
          {
            throw new UnexpectedValueException("Required element 'FileName' not provided.");
          }
                    
          $Defaults =  array('IsWaterMarked' => false, 'IsUsernameAdded' => false);      
                    
          if(!isset($xml->PhotoData))
          {
            if(!file_exists((string)$xml->FileName))
            {
              throw new UnexpectedValueException('File \''.((string)$xml->FileName).'\' does not exist and no PhotoData provided.');
            }
            
            $Defaults['PhotoData'] = base64_encode(file_get_contents((string) $xml->FileName));
            
            $xml->FileName = basename((string)$xml->FileName);            

            // The description is returned by the Photos method (listing photos)
            // and is taken as the FileName provided, you may wish to use a 
            // different description than the filename, and can do so by providing it
            // as a parameter.
            if(isset($xml->Description))
            {
              $xml->FileName = (string)$xml->Description;
              unset($xml->Description);
            }
          }
          
          $Defaults['FileType'] =  preg_replace('/^.*\./', '', (string)$xml->FileName);
                    
          $this->set_xml_defaults($xml,$Defaults);
          
          $Sig = md5($this->ConsumerKey . (string)$xml->FileName . (string)$xml->FileType. ucwords((string)$xml->IsUsernameAdded) . ucwords((string)$xml->IsWaterMarked));
          $xml->addChild('Signature', $Sig);
          
          $this->reorder_xml_elements($xml, array('Signature','PhotoData','FileName','FileType','IsWaterMarked','IsUsernameAdded'));     
        }
        break;
          
        default:
          throw new DomainException('Unknown XML Post: '.$method.'::'.$xml->getName());
          return;
      }      
    }
    
    /** Validate XML used in posting to the MyTradeMe methods
     *
     *  @param string
     *  @param SimpleXMLElement
     *  @throws UnexpectedValueException
     *  @throws DomainException If the XML root element/method is unrecognised.
     */
     
    protected function validate_post_xml_mytrademe($method, &$xml)
    {
      switch($xml->getName())
      {
        case 'SaveNoteRequest':
        {
          // Element Order
          $Defaults           = array('Text' => NULL, 'ListingId'=> NULL, 'NoteId' => 0, 'OfferId' => 0);          
          $ElementOrder       = array_keys($Defaults);
          
          $this->set_xml_defaults($xml, $Defaults); 
          $this->reorder_xml_elements($xml, $ElementOrder);       
        }
        break;
        
        case 'DeliveryAddress':
        {
          // Element Order
          $Defaults           = array('Country' => 'New Zealand');          
          $ElementOrder       = array('Name', 'Address1', 'Address2','Suburb', 'City', 'PostCode', 'Country', 'IsMembershipAddress', 'Id');
          
          $this->set_xml_defaults($xml, $Defaults); 
          $this->reorder_xml_elements($xml, $ElementOrder);       
        }
        break;
        
        
        case 'SaveToWatchlistRequest':
        {
          // Element Order
          $Defaults           = array('ListingId' => NULL, 'EmailOption' => 'UseDefault', 'SaveEmailOption' => 'false');          
          $ElementOrder       = array_keys($Defaults);
          
          $this->set_xml_defaults($xml, $Defaults); 
          $this->reorder_xml_elements($xml, $ElementOrder);     
        }
        break;
        
        case 'FeedbackRequest':
        {
          // Element Order
          $Defaults           = array('FeedbackType' => NULL, 'Text' => NULL,  'PurchaseId' => NULL);          // 'ListingId' => NULL, 'IsForFpo' => NULL,
          $ElementOrder       = array('FeedbackType', 'Text', 'ListingId', 'IsForFpo', 'PurchaseId' );
          
          $this->set_xml_defaults($xml, $Defaults); 
          $this->reorder_xml_elements($xml, $ElementOrder);     
        }
        break;
        
        case 'FeedbackUpdateRequest':
        {
          // Element Order
          $Defaults           = array('FeedbackType' => NULL, 'Text' => NULL, 'FeedbackId' => NULL);          
          $ElementOrder       = array_keys($Defaults);
          
          $this->set_xml_defaults($xml, $Defaults); 
          $this->reorder_xml_elements($xml, $ElementOrder);     
        }
        break;
        
        case 'BlacklistRequest':
        {
          // Element Order
          $Defaults           = array('MemberId' => NULL);          
          $ElementOrder       = array_keys($Defaults);
          
          $this->set_xml_defaults($xml, $Defaults); 
          $this->reorder_xml_elements($xml, $ElementOrder);     
        }
        break;
        
        case 'EmailOptions':
        {
          // Element Order
          $Defaults           = array();          
          $ElementOrder       = array('IsAppliedToExisting', 'WatchlistReminder', 'Newsletter', 'EmailFormat', 'EmailMemberWhen', 'FixedPriceOffer', 'Relist', 'Agent', 'AgentReportFormat' );
          
          $this->set_xml_defaults($xml, $Defaults); 
          $this->reorder_xml_elements($xml, $ElementOrder);     
        }
        break;
        
        case 'PayNowRefundRequest':
        {
          // Element Order
          $Defaults           = array('PurchaseId' => NULL);          
          $ElementOrder       = array('ListingId', 'OfferId', 'RefundAmount', 'PurchaseId');
          
          $this->set_xml_defaults($xml, $Defaults); 
          $this->reorder_xml_elements($xml, $ElementOrder);     
        }
        break;
        
        case 'CourierParcelTrackingRequest':
        {
          // Element Order
          $Defaults           = array();          
          $ElementOrder       = array('Parcel');
          
          $this->set_xml_defaults($xml, $Defaults); 
          $this->reorder_xml_elements($xml, $ElementOrder);     
        }
        break;

        default: 
          throw new DomainException('Unknown XML Post: '.$method.'::'.$xml->getName());
          return;
      }
    }
    
    /** Validate XML used in posting to the MyTradeMe methods
     *
     *  @param string
     *  @param SimpleXMLElement
     *  @throws UnexpectedValueException
     *  @throws DomainException If the XML root element/method is unrecognised.
     */
     
    protected function validate_post_xml_bidding($method, &$xml)
    {
      switch($xml->getName())
      {
        case 'BidRequest':
        {
          // Element Order
          $Defaults           = array('ListingId' => NULL, 'Amount'=> NULL, 'AutoBid' => 'false', 'ShippingOption' => NULL, 'EmailOutBid' => 'false');          
          $ElementOrder       = array_keys($Defaults);
          
          $this->set_xml_defaults($xml, $Defaults); 
          $this->reorder_xml_elements($xml, $ElementOrder);       
        }
        break;
        
        case 'BuyNowRequest':
        {
          // Element Order
          $Defaults           = array('ListingId' => NULL, 'ShippingOption' => NULL, 'Quantity' => 1);              
          $ElementOrder       = array_keys($Defaults);
          
          $this->set_xml_defaults($xml, $Defaults); 
          $this->reorder_xml_elements($xml, $ElementOrder);       
        }
        break;
                
        default: 
          throw new DomainException('Unknown XML Post: '.$method.'::'.$xml->getName());
          return;
      }
    }
    
    /** Validate XML used in posting to the Listings methods
     *
     *  @param string
     *  @param SimpleXMLElement
     *  @throws UnexpectedValueException
     *  @throws DomainException If the XML root element/method is unrecognised.
     */
     
    protected function validate_post_xml_listings($method, &$xml)
    {
      switch($xml->getName())
      {
        case 'ListingAnswerQuestion':
        {
          // Element Order
          $Defaults           = array('answer' => NULL);          
          $ElementOrder       = array_keys($Defaults);
          
          $this->set_xml_defaults($xml, $Defaults); 
          $this->reorder_xml_elements($xml, $ElementOrder);       
        }
        break;
        
        case 'ListingAddComment':
        {
          // Element Order
          $Defaults           = array('comment' => NULL);          
          $ElementOrder       = array_keys($Defaults);
          
          $this->set_xml_defaults($xml, $Defaults); 
          $this->reorder_xml_elements($xml, $ElementOrder);       
        }
        break;
        
        case 'EmailRequest':
        {
          // Element Order
          $Defaults           = array('Message' => NULL, 'CopyToSelf' => 'true');          
          $ElementOrder       = array_keys($Defaults);
          
          $this->set_xml_defaults($xml, $Defaults); 
          $this->reorder_xml_elements($xml, $ElementOrder);       
        }
        break;
        
        case 'EmailFriendRequest':
        {
          // Element Order
          $Defaults           = array('Message' => NULL, 'CopyToSelf' => 'true', 'EmailTo' => NULL);          
          $ElementOrder       = array_keys($Defaults);
          
          $this->set_xml_defaults($xml, $Defaults); 
          $this->reorder_xml_elements($xml, $ElementOrder);       
        }
        break;
        
        default: 
          throw new DomainException('Unknown XML Post: '.$method.'::'.$xml->getName());
          return;
      }
    }  
    
    /** Validate XML used in posting to the Member methods
     *
     *  @param string
     *  @param SimpleXMLElement
     *  @throws UnexpectedValueException
     *  @throws DomainException If the XML root element/method is unrecognised.
     */
     
    protected function validate_post_xml_member($method, &$xml)
    {
      switch($xml->getName())
      {
                
        default: 
          throw new DomainException('Unknown XML Post: '.$method.'::'.$xml->getName());
          return;
      }
    }
    
    /** Validate XML used in posting to the Search methods
     *
     *  @param string
     *  @param SimpleXMLElement
     *  @throws UnexpectedValueException
     *  @throws DomainException If the XML root element/method is unrecognised.
     */
     
    protected function validate_post_xml_search($method, &$xml)
    {
      switch($xml->getName())
      {
                
        default: 
          throw new DomainException('Unknown XML Post: '.$method.'::'.$xml->getName());
          return;
      }
    }
    
    /** Validate XML used in posting to the Catalogue (Categories, Localities, TmAreas) methods
     *
     *  @param string
     *  @param SimpleXMLElement
     *  @throws UnexpectedValueException
     *  @throws DomainException If the XML root element/method is unrecognised.
     */
     
    protected function validate_post_xml_catalogue($method, &$xml)
    {
      switch($xml->getName())
      {
                
        default: 
          throw new DomainException('Unknown XML Post: '.$method.'::'.$xml->getName());
          return;
      }
    }
    
    /** Validate XML used in posting to the Favourites methods
     *
     *  @param string
     *  @param SimpleXMLElement
     *  @throws UnexpectedValueException
     *  @throws DomainException If the XML root element/method is unrecognised.
     */
     
    protected function validate_post_xml_favourites($method, &$xml)
    {
      switch($xml->getName())
      {
        case 'SaveSearchRequest':
        {
          // Element Order
          $Defaults           = array('Email' => 'Daily', 'SearchString' => NULL, 'Type' => 'General');          
          $ElementOrder       = array_keys($Defaults);
          
          $this->set_xml_defaults($xml, $Defaults); 
          $this->reorder_xml_elements($xml, $ElementOrder);       
        }
        break;
        
        case 'SaveCategoryRequest':
        {
          // Element Order
          $Defaults           = array('Email' => 'Daily', 'CategoryId' => NULL);          
          $ElementOrder       = array_keys($Defaults);
          
          $this->set_xml_defaults($xml, $Defaults); 
          $this->reorder_xml_elements($xml, $ElementOrder);       
        }
        break;
        
        case 'SaveSellerRequest':
        {
          // Element Order
          $Defaults           = array('Email' => 'Daily', 'SellerId' => NULL);          
          $ElementOrder       = array_keys($Defaults);
          
          $this->set_xml_defaults($xml, $Defaults); 
          $this->reorder_xml_elements($xml, $ElementOrder);       
        }
        break;
        
        default: 
          throw new DomainException('Unknown XML Post: '.$method.'::'.$xml->getName());
          return;
      }
    }
    
    /** Validate XML used in posting to the FixedPriceOffers methods
     *
     *  @param string
     *  @param SimpleXMLElement
     *  @throws UnexpectedValueException
     *  @throws DomainException If the XML root element/method is unrecognised.
     */
     
    protected function validate_post_xml_fixedpriceoffers($method, &$xml)
    {
      switch($xml->getName())
      {
        case 'FixedPriceOfferRequest':
        {
          // Element Order
          $Defaults           = array('ListingId' => NULL, 'Accept' => NULL, 'ShippingOption' => NULL, 'Quantity' => 1);          
          $ElementOrder       = array_keys($Defaults);
          
          $this->set_xml_defaults($xml, $Defaults); 
          $this->reorder_xml_elements($xml, $ElementOrder);       
        }
        break;
        
        case 'FixedPriceOfferToMembersRequest':
        {
          // Element Order
          $Defaults           = array('ListingId' => NULL, 'Price' => NULL, 'Duration' => 'One', 'Quantity' => 1, 'MemberIds' => NULL);          
          $ElementOrder       = array_keys($Defaults);
          
          $this->set_xml_defaults($xml, $Defaults); 
          $this->reorder_xml_elements($xml, $ElementOrder);       
        }
        break;
        
        case 'FixedPriceOfferWithdrawalRequest':
        {
          // Element Order
          $Defaults           = array('ListingId' => NULL);          
          $ElementOrder       = array_keys($Defaults);
          
          $this->set_xml_defaults($xml, $Defaults); 
          $this->reorder_xml_elements($xml, $ElementOrder);       
        }
        break;
        
        default: 
          throw new DomainException('Unknown XML Post: '.$method.'::'.$xml->getName());
          return;
      }
    }
   
    /** Validate XML used in posting to the Selling methods
     *
     *  @param string
     *  @param SimpleXMLElement
     *  @throws UnexpectedValueException
     *  @throws DomainException If the XML root element/method is unrecognised.
     */
     
    protected function validate_post_xml_selling($method, &$xml)
    {
   
      switch($xml->getName())
      {
        case 'ListingRequest':
        {
          // Element Order
          $Defaults           = array(
            'Category' => NULL,
            'Title' => NULL,
          //  'Subtitle' => NULL,
            'Description' => NULL,
          //  'StartPrice' => NULL,
          //  'ReservePrice' => NULL,
                        
            'Duration' => NULL,
            
            'ShippingOptions' => NULL,
            'PaymentMethods' => NULL,
          );     
          
          $ElementOrder       = array(
            'Category',
            'Title',
            'Subtitle',
            'Description',
            'StartPrice',
            'ReservePrice',
            'BuyNowPrice',
            'Duration',
            'EndDateTime',
            'Pickup',
            'IsBrandNew',
            'AuthenticatedMembersOnly',
            'IsClassified',
            'OpenHomes',
            'SendPaymentInstructions',
            'OtherPaymentMethod',
            'IsOrNearOffer',
            'IsPriceOnApplication',
            'IsBold',
            'IsFeatured',
            'IsHomepageFeatured',
            'HasGallery',
            'HasGalleryPlus',
            'Quantity',
            'IsFlatShippingCharge',
            'HasAgreedWithLegalNotice',
            'AutoRelistLimit',
            'HomePhoneNumber',
            'MobilePhoneNumber',
            'IsHighlighted',
            'HasSuperFeature',
            'PhotoIds',
            'ShippingOptions',
            'PaymentMethods',
            'Attributes',
            'IsClearance',
            'ExternalReferenceId',
            'Contacts', // Property Agents
            'ReturnListingDetails',
            'DonationRecipient',
            'CatalogueId',            // DVD Blu-Ray
            'PromotionId',
            'ExcludeFromShippingPromotion',
            'SKU',
            'GeographicLocation',
            'WasPrice',
            'EmbeddedContent',
            'IsBranded',
            'ShortDescription',
            'ShippingCalculatorInputs',
            'AdditionalData',
            'VariantDefinition',
            'SecondCategory',            
            'PremiumPackageCode',
            'ProductSpecification',
            'ListingExtras',
            'HasGoodFor2Relists',            
            'ListingId',
          );
          
          
          $this->set_xml_defaults($xml, $Defaults);        

          if(!isset($xml->Description->Paragraph))
          {
            $Description = (string)$xml->Description;
            $xml->Description = '';
            
            $Description = preg_split('/\r?\n/', $Description);                     
            foreach($Description as $Para)            
            {
              $xml->Description->addChild('Paragraph', $Para);              
            }            
          }
                   
          $this->reorder_xml_elements($xml, $ElementOrder, $ElementOrder);           
        }
        break;
        
        case 'RelistWithEditsRequest':
        case 'EditListingRequest':
        {
          // Element Order
          $Defaults           = array(
            'Category' => NULL,
            'Title' => NULL,
           // 'Subtitle' => NULL,
            'Description' => NULL,
           // 'StartPrice' => NULL,
           // 'ReservePrice' => NULL,
                        
            'Duration' => NULL,
            
            'ShippingOptions' => NULL,
            'PaymentMethods' => NULL,
            
            'ListingId'      => NULL
          );     
          
          $ElementOrder       = array(
            'Category',
            'Title',
            'Subtitle',
            'Description',
            'StartPrice',
            'ReservePrice',
            'BuyNowPrice',
            'Duration',
            'EndDateTime',
            'Pickup',
            'IsBrandNew',
            'AuthenticatedMembersOnly',
            'IsClassified',
            'OpenHomes',
            'SendPaymentInstructions',
            'OtherPaymentMethod',
            'IsOrNearOffer',
            'IsPriceOnApplication',
            'IsBold',
            'IsFeatured',
            'IsHomepageFeatured',
            'HasGallery',
            'HasGalleryPlus',
            'Quantity',
            'IsFlatShippingCharge',
            'HasAgreedWithLegalNotice',
            'AutoRelistLimit',
            'HomePhoneNumber',
            'MobilePhoneNumber',
            'IsHighlighted',
            'HasSuperFeature',
            'PhotoIds',
            'ShippingOptions',
            'PaymentMethods',
            'Attributes',
            'IsClearance',
            'ExternalReferenceId',
            'Contacts', // Property Agents
            'ReturnListingDetails',
            'DonationRecipient',
            'CatalogueId',            // DVD Blu-Ray
            'PromotionId',
            'ExcludeFromShippingPromotion',
            'SKU',
            'GeographicLocation',
            'WasPrice',
            'EmbeddedContent',
            'IsBranded',
            'ShortDescription',
            'ShippingCalculatorInputs',
            'AdditionalData',
            'VariantDefinition',
            'SecondCategory',            
            'PremiumPackageCode',
            'ProductSpecification',
            'ListingExtras',
            'HasGoodFor2Relists',
            'ListingId',
          );
          
          $this->set_xml_defaults($xml, $Defaults);           
          
          if(!isset($xml->Description->Paragraph))
          {
            $Description = (string)$xml->Description;
            $xml->Description = '';
            
            $Description = preg_split('/\r?\n/', $Description);                     
            foreach($Description as $Para)            
            {
              $xml->Description->addChild('Paragraph', $Para);              
            }            
          }
          
          $this->reorder_xml_elements($xml, $ElementOrder, $ElementOrder);       
        }
        break;
        
        case 'WithdrawRequest':
        {
          // Element Order
          $Defaults           = array('ListingId' => NULL, 'Type' => NULL);          
          $ElementOrder       = array('ListingId', 'Type', 'Reason', 'SalePrice');
          
          $this->set_xml_defaults($xml, $Defaults); 
          $this->reorder_xml_elements($xml, $ElementOrder);       
        }
        break;
        
        case 'RelistListingRequest':
        {
          // Element Order
          $Defaults           = array('ListingId' => NULL);          
          $ElementOrder       = array_keys($Defaults);
          
          $this->set_xml_defaults($xml, $Defaults); 
          $this->reorder_xml_elements($xml, $ElementOrder);       
        }
        break;
        
        case 'SellSimilarListingRequest':
        {
          // Element Order
          $Defaults           = array('ListingId' => NULL);          
          $ElementOrder       = array_keys($Defaults);
          
          $this->set_xml_defaults($xml, $Defaults); 
          $this->reorder_xml_elements($xml, $ElementOrder);       
        }
        break;
        
        default: 
          throw new DomainException('Unknown XML Post: '.$method.'::'.$xml->getName());
          return;
      }
    }
    
    /** Set defaults for elements in an xml (direct children only) and throws exception on a missing required element.
     *
     * @param SimpleXMLElement
     * @param Array array of key => value, if value === NULL, the key is required to be set in the XML
     * @throw UnexpectedValueException For any element where default is NULL and is not provided - this means "REQURIED".
     */
     
    protected function set_xml_defaults(&$xml, $defaults = array(), $ExceptionMessage = 'Required Element Not Provided: %s')
    {
      foreach($defaults as $k => $v)
      {
        if(!isset($xml->{$k}))
        {        
          if(!isset($v) && !isset($xml->{$k}))
          {
            throw new UnexpectedValueException(sprintf($ExceptionMessage, $k));
          }
          
          if(is_scalar($v))
          {
            if(is_bool($v)) $v = $v ? 'true': 'false';
            $xml->addChild($k, $v);
            continue;
          }
          elseif(is_array($v))
          {
            $xml->addChild($k);
            $this->set_xml_defaults($xml->{$k}, $v);
            continue;
          }
        }
      }      
    }
    
    /** The TradeMe API requires it's elements in an exact order.  Sigh.
     * 
     * @param SimpleXMLElement
     * @param array Element names in the order required.
     */
     
    protected function reorder_xml_elements(&$xml, $order, $skip_if_blank = array())
    {    
      $out = "<".$xml->getName();
      foreach($xml->attributes() as $a => $v) $out .= " {$a}=\"".htmlspecialchars($v)."\"";
      $out .= ">";
      
      foreach($order as $element)
      {      
        if(isset($xml->{$element}))
        {
          if(in_array($element, $skip_if_blank))
          {
            if(!count($xml->{$element}->children()) && !strlen((string) $xml->{$element}))
            {             
              continue;
            }            
          }
          
          $out.= $xml->{$element}->asXML();
        }
      }
      
      $out .= "</".$xml->getName().">";
      
      $xml = new SimpleXMLElement($out);
    }
    
    /** Get a gogoOAuthCodecs object to be used for encoding/decoding strings. */
    public function codec()
    {
      if(isset($this->Codecs)) return $this->Codecs;
      require_once(dirname(__FILE__).'/../gogoOAuth/gogoOAuthCodecs.php');
      $this->Codecs = new gogoOAuthCodecs(array('Boolean' => array('false', 'true')));
      return $this->Codecs;
    }

    /** For a given scalar or XML element, determine if it is "true".
     *
     *   1 is true
     *   true is true
     *   '1' is true     
     *   'true' is true
     *  
     *  Anything else is false.
     *
     *  This is necessary because the booleans in XML are passed as strings 'true' and 'false'
     *   but there is the ability to pass in boolean php types and they are converted, but may 
     *   not be converted in the other direction. So use this method to be safe.
     */
     
   public function is_true($Value)
   {
     if($Value instanceof SimpleXMLElement)
     {
      $Value = (string)$Value;
     }
     
     if($Value === true)   return true;
     if($Value === 'true') return true;
     if($Value === 1)      return true;
     if($Value === '1')    return true;
     
     return false;
   }
  }  
?>