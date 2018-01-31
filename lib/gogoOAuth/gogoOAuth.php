<?php
 /*
      Licence
      -------------------------------------------
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
  
  require_once('gogoOAuthResponse.php');

  /** 
  * A simple OAuth system for accessing (mainly 3-legged, does it work with 2?) remote APIs utilising OAuth.
  *
  * @package gogoOAuth
  * @author James Sleeman <james@gogo.co.nz>
  * @license MIT
  *
  */

  class gogoOAuth
  {
    protected $APIBaseURL           = NULL;    
    protected $OAuthBaseURL         = NULL;

    protected $OAuthMethods         = array('RequestToken' => 'RequestToken',
                                            'AccessToken'  => 'AccessToken',
                                            'Authorize'     => 'Authorize');

    protected $ResponseClass        = 'gogoOAuthResponse';

    protected $ConsumerKey          = false;
    protected $ConsumerSecret       = false;

    protected $DefaultCallback      = 'oob';

    protected $DefaultNamespaceForXMLPost = NULL;

    private $_token        = NULL;
    
    protected $Codecs                     = NULL; // Method codec() will establish this as a gogoOAuthCodecs object.
    protected $TrimXMLDeclarationFromPost = TRUE; // If set to TRUE, the XML declaratin < ?xml ..... will be removed when POSTing
    protected $DefaultAPIEndpintExtention = NULL;
    
    protected $CacheCallbacks = NULL; // Use set_cache_callbacks() to set a store and retrieve callback

    protected $DefaultCurlOpts = array(); // Use set_default_curl_opts() to set this (or pass to the constructor)
    
    /** 
     * Create an oauth object.
     *
     * @param string Obtain the consumer key and secret from your api provider using whatever tools they provide to do that.
     * @param string Obtain the consumer key and secret from your api provider using whatever tools they provide to do that.
     * @param string Where the api provider will send your users to with a "verifier" which you use to obtain your "access key" 
     *                    which is then used for future requests.
     *
     * @param string The API base is the base location of the API, to which your "methods" will be added,
     *  Examples: TradeMe https://api.trademe.co.nz/v1/   (example method = MyTradeMe/Watchlist.xml)
     *            Twitter http://api.twitter.com/1/       (example method = favorites.json)
     *
     * @param string The oauth base is the base location where the oauth methods will be added
     *  Examples: TradeMe https://secure.trademe.co.nz/Oauth/  (example method = RequestToken)
     *            Twitter http://api.twitter.com/oauth/        (example method = request_token)
     *
     * @param string The default namespace (if any) is the namespace which will be set on the root element of any XML post.
     *
     * @param string The default extention to add to API methods, if they do not have an extention.  Eg "xml" means you can
     *                use get('Endpoint') and it will be turned into get('Endpoint.xml'),
     *                but you can still also use get('Endpoint.json') specifically without any problem.
     *  
     * @param array A mapping of the 3 oauth requests we need ("RequestToken", "AccessToken" and "Authorize")
     *   to the corresponding oauth methods as defined by your api provider
     *   Examples: TradeMe array('RequestToken' => 'RequestToken', 'AccessToken'  => 'AccessToken', 'Authorize'     => 'Authorize')
     *             Twitter array('RequestToken' => 'request_token', 'AccessToken'  => 'access_token', 'Authorize'     => 'authorize')
     *
     * @param array An array of curl options (as would be passed to curl_setopt_array) which are necessary (over and above some built in defaults), a common one to need is array( CURLOPT_SSL_VERIFYPEER => false ) to prevent SSL issues while still using SSL.
     */
     
    public function __construct($consumer_key, $consumer_secret, $default_callback = NULL, $api_base = NULL, $oauth_base = NULL, $default_namespace_for_xml_post = NULL, $defalt_api_endpoint_extention = NULL, $oauth_methods = NULL, $default_curl_opts = array() )
    {
        $this->ConsumerKey = $consumer_key;
        $this->ConsumerSecret = $consumer_secret;
        
        if(isset($api_base)) $this->APIBaseURL = $api_base;
        if(isset($oauth_base)) $this->OAuthBaseURL = $oauth_base;
        if(isset($oauth_methods)) $this->OAuthMethods = $oauth_methods;
        if(isset($default_callback)) $this->DefaultCallback = $default_callback;  
        if(isset($default_namespace_for_xml_post)) $this->DefaultNamespaceForXMLPost = $default_namespace_for_xml_post;
        if(isset($defalt_api_endpoint_extention)) $this->DefaultAPIEndpintExtention = $defalt_api_endpoint_extention;
        if(isset($default_curl_opts)) $this->DefaultCurlOpts = $default_curl_opts;
    }

    /**
     * Step 1: Get a request token.
     *
     * You must store the request token you get somewhere.
     *
     * This is step 1 in the inital authentication process (the end goal to get an access token).
     *
     * @param string|NULL callback_url, if not supplied, use the default supplied at instantiation
     * @return Array Token.
     */

    public function get_request_token($callback_url = NULL)
    {   
      if(!isset($callback_url)) $callback_url = $this->DefaultCallback;
      
      $request_token = $this->_request($this->get_oauth_url('RequestToken'), array('oauth_callback' => $callback_url))->to_array();            
      $request_token['oauth_token_type'] = 'request';
      $request_token['oauth_token_time'] = time();
      
      $this->set_token($request_token);
      
      return $request_token;
    }

    /**
     * Step 2: Get the URL of the authorization page.
     *
     * When you have the URL, you need to redirect/send the user there.
     *
     * This is step 2 in the inital authentication process (the end goal to get an access token).
     *
     * @return string The Authorization URL.
     */

    public function get_authorize_url()
    {        
      $token = $this->get_token();
      if(!isset($token) || $token['oauth_token_type'] == 'access') 
      {
        throw new Exception('Invalid OAuth Token, you must use get_request_token() before get_authorize_url()');
      }
      
      return $this->get_oauth_url('Authorize') . "?oauth_token={$token['oauth_token']}";
    }

    /**     
     * Step 3/Step A: Set the OAuth token which you have previously stored.
     *
     * This is Step 3: in the inital authentication process (the end goal to get an access token).
     *  your callback handler must supply the request token you saved in Step 2
     *
     * This is Step A: in normal operations where you already have an access token, you will supply
     *  said access token before you can make any get/post requests.
     * 
     * @note get_request_token() and get_access_token() will do a set_token() themselves, so you 
     *  only need to use set_token() when you are instantiating the gogoOAuth object
     *  (using the appropriate token which you previously stored).
     *
     * @param array $token The OAuth token (see get_token(), get_request_token(), get_access_token())         
     */

    public function set_token($token)
    {
      $this->_token = $token;      
    }

    /**
     * Step 4: Get an access token. 
     *
     * Provide the "oauth_verifier" parameter which your callback receieved (in $_REQUEST).
     * Make sure first to call set_token() with the request token!
     *
     * When you have your shiny new access token, save it somewhere safe, you to use set_token() 
     * with it as the first step in normal operations.
     *
     * This is Step 4: in the inital authentication process (the end goal to get an access token).
     *
     * @param string $verifier The OAuth verifier returned from the authorization page or the user.
     */

    public function get_access_token($verifier)
    {
        $access_token = $this->_request($this->get_oauth_url('AccessToken'), array('oauth_verifier' => $verifier))->to_array();                
        $access_token['oauth_token_type'] = 'access';
        $access_token['oauth_token_time'] = time();
        
        $this->set_token($access_token);
        
        return $access_token;
    }

    /** Step B(1): Issue a GET request to the API.
     *
     *  You must already have set_token() with your Access Token which you stored at some point previously seconds, weeks, 
     *    months, years ago.  Well not years, probably it expires before then.
     *
     *  @param string $method The endpoint of the method to call, without the API Base (eg MyTradeMe/Watchlist/All.xml )
     *  @param array  ParamName => ParamValue
     *  @return gogoOAuthResponseDataJSON|gogoOAuthResponseDataXML|gogoOAuthResponseDataURLEncoded
     */

    public function get($method, $params = array())
    {
      return $this->_request($this->get_api_url($method), $params, 'GET');
    }

    /** Step B(2): Issue a POST request to the API.
     *
     *  You must already have set_token() with your Access Token which you stored at some point previously seconds, weeks, 
     *    months, years ago.  Well not years, probably it expires before then.
     *
     *  @param string $method The endpoint of the method to call, without the API Base (eg MyTradeMe/Watchlist/All.xml )  
     *                        This gets passed through get_api_url()
     *                        You can append a suffix as "::RootElement" where RootElement will be 
     *                           XML:         Wrapped around the XML   <RootElement>....</RootElement>
     *                           JSON:        Wrapped around the JSON  { "RootElement": .... }
     *                           Urlencoded:  Prefixed to all QS, eg RootElement[Param1]=Value1&RootElement[Param2]=Value2
     *
     *  
     *  @param mixed $params Most commonly, an array of element (xml)/param (urlencode)/property (json) => value
     *                       such an array, multi or single dimension will be converted as required by the end point
     *                       based on the content type if specified, or the extention of the endpoint if specified.
     *                       
     *                       Alternatively, a string already in the appropriate format for the request (without RootElement,
     *                       it will still be added.
     *
     *                       Alternatively for XML posts, SimpleXMLElement
     *
     *                       In any of the cases, the data, in it's structural format (not string) will be passed to
     *                       validate_post_json, validate_post_urlencoded or validate_post_xml
     *                       that is, json and urlencoded versions will validate a (nested) associated array
     *                       and XML will validate a SimpleXMLElement structure.
     *
     *  @param string $ContentType Mime content type to set on the POST, if NULL a best-guess is made 
     *          (XML is text/xml, JSON is application/json, and urlencoded is application/x-www-form-urlencoded)
     *
     *  @param string $namespace Namespace to set for the root element in XML, if any.
     *
     *  @return gogoOAuthResponseDataJSON|gogoOAuthResponseDataXML|gogoOAuthResponseDataURLEncoded
     */

    public function post($method, $params = '', $ContentType = NULL, $namespace = NULL)
    {
      // Suffix method with ::RootElement for XML type submits
      if(preg_match('/::(.*)$/i', $method, $M))
      {
        $root = strlen($M[1]) ? $M[1] : NULL;
        $method = preg_replace('/::.*$/', '', $method);
      }
      
      $URL = $this->get_api_url($method);
      
      
      if(!isset($ContentType))
      {
        if($params instanceof SimpleXMLElement)
        {
          $ContentType = 'text/xml';
        }
        elseif(preg_match('/\.xml$/', $URL))
        {
          $ContentType = 'text/xml';
        }
        elseif(preg_match('/\.json$/', $URL))
        {
          $ContentType = 'application/json';
        }
        elseif(is_string($params))
        {
          if(preg_match('/^((<\?xml)|(<[a-z]+))/i', trim($params)))
          {
            $ContentType = 'text/xml';
          }
          elseif(preg_match('/^\{/', $params))
          {
            $ContentType = 'application/json'; 
          }
          else
          {
            $ContentType = 'text/plain';
          }
        }
        else
        {
          $ContentType = 'application/x-www-form-urlencoded';
        }
      }
      

      // If this is text/plain, we are done
      if($ContentType != 'text/plain')
      {                       
        // Validate the params, wrap in root if necessary
        switch($ContentType)
        {
          case 'text/xml':
          {              
            if($params instanceof SimpleXMLElement) 
            {
              $params = $params->asXML();
            }
            
            // Params is a string, or an array
            if(is_string($params))
            {
              if(isset($root))
              {
                // Pull off the XML declaration
                preg_match('/^(<\?xml[^>]*>)/', trim($params), $M);
                if($M[1]) $params = preg_replace('/^(<\?xml[^>]*>)/', '', trim($xml));
                
                // Wrap in the root
                $xml = "<{$root}>".$xml."</{$root}>";
                
                // Reattach the xml declaration
                $xml = $M[1].$xml;  
              }
            }
            else
            {
              if(isset($root))
              {
                $params = $this->codec()->array_to_xml(array($root => $params));
              }
              else
              {
                $params = $this->codec()->array_to_xml($params);
              }
            }
                        
            // Params is now a string make it a SimpleXMLElement for validation.
            if(strlen($params))
            {
              $params = new SimpleXMLElement($params);        
                      
              $this->validate_post_xml($method, $params);      
              
              if(!isset($namespace)) $namespace = $this->DefaultNamespaceForXMLPost;
              if($namespace)
              {
                $params['xmlns'] = $namespace;
              }
              
              // And finally one more convert to a string
              $params = $params->asXML();    
              
              if($this->TrimXMLDeclarationFromPost)
              {
                $params = trim(preg_replace('/(<\?xml[^>]*>)/', '', trim($params)));
              }
            }
    
          }
          break;
          
          case 'application/json':
          {
            if(is_string($params)) $params = $this->codec()->json_decode($string);
            if(isset($root))
            { 
              $params = array($root => $params);
            }            
            $this->validate_post_json($method, $params);
            
            $params = $this->codec()->json_encode($params);
          }
          break;
          
          case 'application/x-www-form-urlencoded':
          {
            if(is_string($params)) $params = $this->codec()->from_query_string($params);
            if(isset($root))
            {
              $params = array($root => $params);
            }
            $this->validate_post_urlencoded($method, $params);
            
            $params = $this->codec()->to_query_string($params);            
          }
          break;
        }     
        
        // At this point, the params are the raw post string
      }
      
      return $this->_request($URL, $params, 'POST', $ContentType);
    }    
    
    /** Step B(3): Issue a DELETE request to the API.
     *
     *  You must already have set_token() with your Access Token which you stored at some point previously seconds, weeks, 
     *    months, years ago.  Well not years, probably it expires before then.
     *
     *  @param string $method The endpoint of the method to call, without the API Base (eg MyTradeMe/Watchlist/All.xml )
     *  @param array  ParamName => ParamValue
     *  @return gogoOAuthResponseDataJSON|gogoOAuthResponseDataXML|gogoOAuthResponseDataURLEncoded
     */

    public function delete($method, $params = array())
    {
      return $this->_request($this->get_api_url($method), $params, 'DELETE');
    }

    /** Validate and potentially modify the provided XML which is going to be posted.
     *
     *  @note, the default versions of validate_post_xml and validate_post_json use this 
     *          method by converting their structures into a similar XML structure as you'd expect
     *          running through this, and then converting back.  THIS MAY NOT WORK FOR YOU, 
     *          if not, you can easily override validate_post_json and validate_post_urlencoded
     *          to work with the data structures themselves.
     *
     *  @param string The method being called, if post() was called with a ::RootElement suffix,
     *          this has already been removed and wrapped around the XML.
     *
     *  @param SimpleXMLElement The validator may change the xml, eg adding default values.
     *
     *  @throw UnexpectedValueException Code of the exception should be the name of the element.     
     */
     
    protected function validate_post_xml($method, SimpleXMLElement &$xml)
    {
      // Nothing to validate by default
    }
    
    /** Validate and potentially modified the data structure which is going to be json encoded and POSTed.
     *
     *  The default version of this converts the array structure to SimpleXMLElement, validates with
     *   validate_post_xml, and then converts back to an array structure.  This way you only need to implement
     *   validate_post_xml and json/urlencoded will work with that.  Override if you need to do it differently!
     *
     *  @param string The method being called, if post() was called with a ::RootElement suffix,
     *          this has already been removed and the structure is array('root' => structure given to post())
     *.
     *  @param array|scalar Scalar seems unlikely, most likely an associative array structure.  It is
     *          exactly what will be run through json_encode and posted.  It may be modified by the method.
     *
     *  @throw UnexpectedValueException Code of the exception should be the name of the element.   
     */
     
    protected function validate_post_json($method, &$structure)
    {    
      $xml = new SimpleXMLElement($this->codec()->array_to_xml($structure));      
      $this->validate_post_xml($method, $xml);      
      $structure = $this->codec()->xml_to_array($xml);      
    }
    
    /** Validate and potentially modified the data structure which is going to be url encoded and POSTed.
     *
     *  The default version of this converts the array structure to SimpleXMLElement, validates with
     *   validate_post_xml, and then converts back to an array structure.  This way you only need to implement
     *   validate_post_xml and json/urlencoded will work with that.  Override if you need to do it differently!
     *
     *  @param string The method being called, if post() was called with a ::RootElement suffix,
     *          this has already been removed and the structure is array('root' => structure given to post())
     *.
     *  @param array|scalar Scalar seems unlikely, most likely an associative array structure.  It is
     *          exactly what will be run through to_query_string and posted.  It may be modified by the method.
     *
     *  @throw UnexpectedValueException Code of the exception should be the name of the element.   
     */
     
    protected function validate_post_urlencoded($method, &$structure)
    {
      $xml = new SimpleXMLElement($this->codec()->array_to_xml($structure));
      $this->validate_post_xml($method, $xml);
      $structure = $this->codec()->xml_to_array($xml);
    }

    /** Return the URL to OAuth for the given method (see OAuthMethods).
     * @param  string (eg 'RequestToken', 'AccessToken' or 'Authorize')
     * @return string
     */

    protected function get_oauth_url($Method)
    {
      return $this->OAuthBaseURL . $this->OAuthMethods[$Method];
    }

    /** Return the URL to the API for the given method.
    *
    *   If the endpoint has no extention, the default will be added (if any).
    *
    * @param string (eg 'Path/To/Some/Endpoint.xml')
    * @return string (Method prefixed with Base URL from instantiation)
    */

    protected function get_api_url($Method)
    {    
      $Url = $this->APIBaseURL . $Method;
      if($this->DefaultAPIEndpintExtention)
      {
        if(!preg_match('/\.[a-z]{3,4}$/', $Url)) $Url .= '.'.$this->DefaultAPIEndpintExtention;      
      }
      
      return $Url;
    }   

    /**
     * Send a request to a server, signed by oauth.
     *
     * @param string  $url   The URL to call.
     *
     * @param array|string   $call_params The parameters to pass on the url, if a GET then this is an array(k=>v), if it's
     *                       a POST then this is a string of raw post data 
     *                          (if you want to send x-www-form-urlencoded, encode it first)
     *                          NB: post() does that encoding for you for normal ops.
     *
     * @param string  $request_method The HTTP request method to use.          
     *
     * @return gogoOAuthResponse
     */

    private function _request($url, $call_params = NULL, $request_method = 'GET', $ContentTypeIfAny = NULL)
    {
      // Prepare oauth arguments, we need these separate because they are sent in a header, not on the url
      {
        $oauth_params = array(
            'oauth_consumer_key' => $this->ConsumerKey,
            'oauth_version' => '1.0',
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_nonce' => md5(uniqid(microtime()))
        );

        if($token = $this->get_token()) 
        {
          $oauth_params['oauth_token'] = $token['oauth_token'];
        }

        // We might have oauth parameters in the call_params, move them out
        if(is_array($call_params))
        {
          foreach(array_keys($call_params) as $k) 
          {
            if(preg_match('/^oauth_/', $k)) 
            {
              $oauth_params[$k] = $call_params[$k];
              unset($call_params[$k]);
            }
          }
        }
      }
        
      // Now we can sign our request, note that any post body is not signed (params are tho?).
      {
        $signing_parameters = array_merge($oauth_params, is_array($call_params) ? $call_params:array());
        
        // The parameters must be sorted alphabetically ignoring case
        uksort($signing_parameters, array($this, 'strcmp_nocase'));                   

        // This is not the same as to_query_string() because the parts have no parameter names        
        $base = implode('&', $this->codec()->rawurlencode(array(
            strtoupper($request_method),
            $url,
            $this->codec()->to_query_string($signing_parameters)
        )));

        // This is not the same as to_query_string() because the parts have no parameter names  
        $key = implode('&', $this->codec()->rawurlencode(array(
            $this->ConsumerSecret,
            isset($token['oauth_token_secret']) ? $token['oauth_token_secret'] : ''
        )));

        // Hash the base with the key to produce signature
        $oauth_params['oauth_signature'] = base64_encode(hash_hmac('sha1', $base, $key, true));              
      }
            
      // We can now create the OAuth authorization header from the parameters and signature
      $Headers = array(
        'Authorization: OAuth realm="",' . $this->codec()->to_query_string($oauth_params, 1, 0, ',')
      );
             
      $FormPostData = NULL;
                  
      // Assemble the URL, Headers and any Form Data      
      {
        switch(strtoupper($request_method))
        {
          case 'POST':
            if(isset($call_params) && is_array($call_params)) 
            {
              $FormPostData = $this->codec()->to_query_string($call_params);
              if(!isset($ContentTypeIfAny)) $ContentTypeIfAny = 'application/x-www-form-urlencoded';
            }
            else
            {
              $FormPostData = $call_params;
            }
            break;
            
          case 'GET':
            if(is_array($call_params) && count($call_params)) 
            {
              $url .= '?' . $this->codec()->to_query_string($call_params);
              $FormPostData = NULL;
            }
            
            // If we have a cache handler, since this is a GET request we can
            // cache this, the cache key needs to be unique 
            // to the URL
            // any non-oauth headers
            // own consumer key
            // and the authenticated user's token
            if(isset($this->CacheCallbacks))
            {
              $CacheKey 
                =      $url . ':' . 
                       implode(array_slice($Headers, 1), 1) . ':' .
                       $this->ConsumerKey . ':' .
                       $token['oauth_token']
                  ;
            }
          break;

          case 'DELETE':
            if(is_array($call_params) && count($call_params)) 
            {
              $url .= '?' . $this->codec()->to_query_string($call_params);              
              $FormPostData = NULL;
            }
          break;
        }

        if(isset($ContentTypeIfAny))
        {
          $Headers[] = 'Content-type: ' . $ContentTypeIfAny;
        }
      } 
      
      // If we are caching the request, check if we have one that isn't expired
      if(isset($CacheKey))
      {
        if($CachedResult = call_user_func_array($this->CacheCallbacks['retrieve'], array($CacheKey)))
        {
          list($response, $info) = $CachedResult;                   
          $ResponseClass = $this->ResponseClass;
          return new $ResponseClass($response, $info, $this->codec());
        }
      }

      // Now we do the http request, which is done with curl in this class
      list($response,$info) = $this->perform_http_request($url, $request_method, $Headers, $FormPostData);

      // Curl has done it's bit, so wrap the response we got into the appropriate response class and return
      $ResponseClass = $this->ResponseClass;
      $ResponseObject = new $ResponseClass($response, $info, $this->codec());
      
      // If we are caching the request, do so provided it was OK, using the expires the response parsed out of the headers
      if(isset($CacheKey) && $ResponseObject->is_ok())
      {
        call_user_func_array($this->CacheCallbacks['store'], array(array($response, $info), $CacheKey, $ResponseObject->expires()));
      }
      
      return $ResponseObject;
    }
    
    /** Set a pair of callbacks for storing and retrieving arbitrary data in a cache by a string key.
     *
     *  @param Array|NULL Either an array with callable elements "store" and "retrieve" or NULL
     *
     *      Example using Lambda functions...
     *        array('store' => function($Data, $Key, $Expires){ ...... }, 'retrieve' => function($Key){  ....... } )
     *
     *      Example using methods of some object
     *        array('store' => array($YourObject, 'your_store_method'), 'retrieve' => array($YourObject, 'your_retrieve_method'),  )
     *
     *     where $Data is arbitrary data, $Key is a string and $Expires is a unix timestamp (seconds since epoch)     
     *     store should return true/false, and retrieve should return the data, or NULL if nothing was found to match the key 
     *     data should not be returned once it has expired
     *     storing should overwrite previously stored data with the same key
     *     
     */
     
    public function set_cache_callbacks($CacheCallbacks = NULL)
    {
      if($CacheCallbacks)
      {
        if(!isset($CacheCallbacks['store'])
        || !isset($CacheCallbacks['retrieve'])
        || !is_callable($CacheCallbacks['store'])
        || !is_callable($CacheCallbacks['retrieve'])
        )
        {
          throw new Exception("Invalid callbacks provided for caching, both store and retrieve must be provided and be callable.");
        }
      }
      
      $this->CacheCallbacks = $CacheCallbacks;
    }

    /** Set/change the default curl options (in addition to some hard-coded defaults unless you overwrite them).
     *
     *  @param array Curl options array as would be passed to curl_setopt_array
     *
     */
    
    public function set_default_curl_opts($Options)
    {
      $this->DefaultCurlOpts = $Options;
    }
    
    /** Perform an arbitrary HTTP request and return a tuple of the full result including headers, and an 
     *  information array, with as per http://nz.php.net/manual/en/function.curl-getinfo.php
     *
     *  NB: If you re-implement to not use curl, at least include http_code and content_type.
     *
     * @param string The URL to get/post INCLUDING query parameters (for GET)
     * @param string GET|POST
     * @param array  Array of headers to set (not associative!) array('Content-type: text/plain', ...)
     * @param string Only valid for POST, the raw data to include in the post
     */

    protected function perform_http_request($URL, $Method = 'GET', $Headers = NULL,  $FormPostData = NULL)
    {  
      $curl = curl_init($URL);            
      
      curl_setopt_array($curl, array_replace(array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_USERAGENT      => 'gogoOAuth (' . $_SERVER['HTTP_HOST'] . ')',
        CURLOPT_HEADER         => TRUE,
        CURLOPT_HTTPHEADER     => $Headers
      ), $this->DefaultCurlOpts));
      
      if(defined('CURLINFO_HEADER_OUT'))
      {
        curl_setopt($curl, CURLINFO_HEADER_OUT, TRUE);
      }
      
      // Do anything for the specific method
      switch(strtoupper($Method))
      {
        case 'GET':
        {        
          
        }
        break;
        
        case 'POST':
        {  
          curl_setopt_array($curl, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $FormPostData
          ));
        }
        break;
        
        case 'DELETE':
        {
         curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }         
        break;
      }
            
      // Do the request and grab the response and some info about it which may be useful
      $response = curl_exec($curl);
      $info     = curl_getinfo($curl);
      if(!defined('CURLINFO_HEADER_OUT'))
      {
        $info['request_header'] = implode("\r\n", $Headers);
      }
      $info['errno'] = curl_errno($curl);
      $info['errmsg'] = curl_error($curl);

      // Ditch curl and return the response and info
      curl_close($curl);      
      return array($response, $info);
    }
                          
    /**
     * Get the current auth token (from set_token() or get_request_token() or get_access_token(), whichever was last).     
     *
     * You should not need this publically, instead be sure that you save the results of get_request_token() and get_access_token() 
     * as soon as you generate them.
     *
     * @return Array Token.
     */

    protected function get_token()
    {
      if(!isset($this->_token)) return NULL;
      return $this->_token;
    }
    
    /**
     * Compare two strings ignoring case, this is just needed for the OAuth signing.
     *
     * @param string
     * @param string
     * @return -1|0|1
     */
     
    public function strcmp_nocase($a, $b)
    {
      return strcmp(strtolower($a), strtolower($b));
    }
    
    /** Get a gogoOAuthCodecs object to be used for encoding/decoding strings.
     *  @return gogoOAuthCodecs
     */
     
    protected function codec()
    {
      if(isset($this->Codecs)) return $this->Codecs;
      require_once('gogoOAuthCodecs.php');
      $this->Codecs = new gogoOAuthCodecs();
      return $this->Codecs;
    }
  }
?>
