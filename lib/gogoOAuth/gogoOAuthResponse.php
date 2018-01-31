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
  
  /** You get one of these objects as the return from get() or post().
   *
   *  It provides a generalised way to access the status and data of the response
   *  
   *  
      Potential Example with an XML response.
      --------------------------------------------------------------------------------
      $Response = $myOauth->get('message-of-the-day.xml');
      if($Response->is_ok())
      {
        echo "The message today is:" . $Response->Message;    
        foreach($Response->Author as $Author) echo "; Written By {$Author->FirstName} {$Author->LastName}";    
      }
      
      Potential Example if it was JSON
      --------------------------------------------------------------------------------
      $Response = $myOauth->get('message-of-the-day.json');
      if($Response->is_ok())
      {
        echo "The message today is:" . $Response->Message;    
        foreach($Response->Authors as $Author) echo "; Written By {$Author->FirstName} {$Author->LastName}";    
      }
      
      Potential Example if it was urlencoded parameters
      --------------------------------------------------------------------------------
      $Response = $myOauth->get('message-of-the-day.txt');
      if($Response->is_ok())
      {
        echo "The message today is:" . $Response->Message;    
        foreach($Response->Authors as $Author) echo "; Written By {$Author->FirstName} {$Author->LastName}";    
      }
      
   */

  class gogoOAuthResponse implements ArrayAccess 
  {
    protected $_Headers;
    protected $_Body;
    protected $_Status;
    protected $_StatusMessage;
    
    protected $_Full;
    protected $_CurlInfo;
    private   $_Codecs;
    
    /** A response is typically constructed by gogoOAuth and returned as a result 
     *  of a get() or post() on your gogoOAuth object.
     */
     
    function __construct($FullResponseWithHeadersFromCurl, $CurlInfo, $Codecs)
    {
      $FullResponseWithHeadersFromCurl = ltrim(preg_replace('/^HTTP\/[0-9]+\.[0-9] 100.*$/m', '', $FullResponseWithHeadersFromCurl));        
      list($response_headers,$response_body) = explode("\r\n\r\n",$FullResponseWithHeadersFromCurl,2); 
      
      $response_header_lines = explode("\r\n",$response_headers);         
      if (preg_match('@^HTTP/[0-9]\.[0-9] ([0-9]{3})(.*)$@', $response_header_lines[0], $matches)) 
      { 
        $http_response_line = array_shift($response_header_lines); 
        $response_code = $matches[1]; 
        $response_message = $matches[2];
      } 

      // put the rest of the headers in an array 
      $response_header_array = array(); 
      foreach ($response_header_lines as $header_line) { 
        if(preg_match('/[a-z0-9]+:/', $header_line))
        {
          list($header,$value) = explode(': ',$header_line,2); 
          $lastheader = trim(strtolower($header));
          $response_header_array[$lastheader] = $value;          
          
        }
        elseif(isset($lastheader))
        {
          $response_header_array[$lastheader] .= $header_line;
        }
      } 
      
      $this->_Status = (int) $response_code;
      $this->_StatusMessage = $response_message;      
      $this->_Headers = $response_header_array;
      $this->_Body = $response_body;
      
      $this->_Full = $FullResponseWithHeadersFromCurl;     
      $this->_CurlInfo = $CurlInfo;
      $this->_Codecs = $Codecs;
    }
    
    /** Return the HTTP Response Status Code that accompanied the response. 
     *
     * @return int
     */
    
    public function status()
    {
      return $this->_Status;
    }
    
    /** Responses converted to a string gets you the response body. 
     *
     *  @return string
     */
    
    public function __tostring()
    {
      return $this->_Body;
    }
    


    /** In the default response class, a 200 response is OK, anything else is an error.
     *
     * @return bool
     */
    
    public function is_ok()
    {    
      if($this->_Status !== 200) return FALSE;
      return TRUE;
    }
    
    /** Determine if this response is an error. The inverse of is_ok().
     *  In the default response class, a 200 response is OK, anything else is an error.
     *
     * @return bool
     */
     
    public final function is_error()
    {
      return !$this->is_ok();
    }
    
    
    /** Return any error message, if possible. 
     *  In the default response class any message accompanying the status is the error message
     *   IF is_error()
     *   but if is_ok() no message is returned.
     *
     * @return FALSE|string
     */
     
    public function error_message()
    {
      if($this->is_ok()) return FALSE;
      return $this->_StatusMessage;
    }
    
    /** Return a time-since-epoch that this response should be cached until, this is based directly
     *  from the Expires header sent by the server, if any.  
     *
     *  If there is no Expires header, returns 0, if Expires is in the past, returns 0.
     *  
     *  @NOTE No caching is performed by gogoOAuth.  If you want to cache results, it is up to you
     *        to do that in whatever way is most appropriate for your framework/system, not for me
     *        to decide for you how to do that.
     *
     *  @return timestamp Time after which this response is deemed to have expired.
     */
     
    public function expires()
    {
      if(isset($this->_Headers['expires']))
      {
        $time = strtotime($this->_Headers['expires']);
        if(!$time)         return 0;
        if($time < time()) return 0;
      }
      
      return 0;
    }                
    
    /** We can get a property of the resultant data simply by referencing the property as part of the response.
     *
     *  XML:          <Jobs><List><Job><Title>Foo</Title></Job></List></Jobs>
     *  JSON:         {"List": [ {Title: 'Foo' } ]}
     *  URL Encoded:  List[0][Title]=Foo
     *
     *  XML:          $Title = (string) $Response->List->Job[0]->Title
     *  JSON:         $Title = (string) $Response->List[0]->Title
     *  URL Encoded:  $Title = (string) $Response->List[0]->Title
     *
     *  XML:          foreach($Response->List->Job as $Job) { }
     *  JSON:         foreach($Response->List      as $Job) { }
     *  URL Encoded:  foreach($Response->List      as $Job) { }
     *
     * @return gogoOAuthResponseDataJSON|gogoOAuthResponseDataURLEncoded|gogoOAuthResponseDataXML
     *    for XML responses, returns SimpleXMLElement (extended as gogoOAuthResponseDataXML)
     *    for JSON responses, returns gogoOAuthResponseDataJSON
     *    for URL encoded responses, returns gogoOAuthResponseDataURLEncoded
     *        
     */
     
    public function __get($Key)
    {
     return $this->data()->{$Key}; 
    }
    
    /** We can also call methods of the resultant data - this is only really useful for XML responses, the others
     *  are unlikely to have any methods to call!
     *
     */
     
    public function __call($Fn, $Args)
    {
      return call_user_func_array(array($this->data(), $Fn), $Args);
    }
    
    /** Work out what type of data is contained in this response (xml, json... others?) and try to decode it into an object. 
     *
     *  Most of the time, you won't use this, just access a propery on this object itself and it will be 
     *  handled through data() as you would expect (eg instead of $Response->data()->Foo, just do $Response->Foo )
     *
     *  Note that normally you would not need to call this directly see __get()          
     */
     
    public function data()
    {
      if($this->is_urlencoded()) return $this->urldecode();
      if($this->is_xml())        return $this->xml();
      if($this->is_json())       return $this->json();
      throw new Exception('Unknown data format: '. $this->_Headers['content-type']);
    }
        
    /** Determine if the data is of the given content type.  This should be expanded so it's more
     *  relaxed about what servers say their content is, presently it is strict appart from case.
     *
     *  Note that normally you would not need to call this directly anyway, see __get()
     */
     
    public function is($ContentType)
    {
      $Type = strtolower(trim(preg_replace('/;.*$/', '', $this->_Headers['content-type'])));
      if(trim(strtolower($ContentType))=== $Type) return TRUE;
      return FALSE;
    }       
    
    /** Determine if the data will be likely a form-urlencoded string (somewhat relaxed check).
     *  I think the correct content-type we should expect is application/x-www-form-urlencoded
     *
     *  But we can't rely on that, it could easily be text/plain, text/xml or even text/html
     *  if the developer of the server didn't think about it.
     *
     *  Note that normally you would not call this directly, see __get()
     */
     
    public function is_urlencoded()
    {
      if(preg_match('/application\/x-www-form-urlencoded/', $this->_Headers['content-type']))
      {
        return true;
      }
      elseif(preg_match('/text\/plain|text\/html/', $this->_Headers['content-type']))
      {
        if(preg_match('/^(&?([a-zA-Z0-9._+%~[\]-]+=[a-zA-Z0-9._+%~[\]-]*)?)*$/', trim($this->_Body)))
        {
          return true;
        }
      }
      
      return false;
    }
    
    /** 
     * Determine if the data will be likely XML, we just check Content-Type currently so trust it's ok 
     *  Note that normally you would not call this directly, see __get()
     */    
     
    public function is_xml()
    {
      return preg_match('/xml/i', $this->_Headers['content-type']);
    }
    
    /** 
     * Determine if the data will likely be JSON, we just check Content-Type currently 
     * (for something containing "javascript" or "json" 
     *  Note that normally you would not call this directly, see __get()
     */
     
    public function is_json()
    {
      return preg_match('/(json|javascript)/i', $this->_Headers['content-type']);
    }
    
    /** 
     * Attempt to create an Object from the query string response. 
     * Note that normally you would not call this directly, see __get()
     */
     
    public function urldecode()
    {
      if(!isset($this->_URLObj))
      {
        require_once('gogoOAuthResponseDataURLEncoded.php');
        $this->_URLObj = new gogoOAuthResponseDataURLEncoded(trim($this->_Body), $this->codec());
      }
      
      return $this->_URLObj;
    }
    
    /** Attempt to create a SimpleXMLElement from the response. 
     *  Note that normally you would not call this directly, see __get() 
     */    
     
    public function xml()
    {
      if(!isset($this->_XMLDom)) 
      {
        require_once('gogoOAuthResponseDataXML.php');
        $this->_XMLDom = new gogoOAuthResponseDataXML(trim($this->_Body));        
      }
      
      return $this->_XMLDom;
    }
    
    /** Attempt to convert the response from json into a PHP structure.
     *  Note that normally you would not call this directly, see __get()     
     */
     
    public function json()
    {
      if(!isset($this->_JSONObj)) 
      {
        require_once('gogoOAuthResponseDataJSON.php');
        $this->_JSONObj = new gogoOAuthResponseDataJSON(trim($this->_Body), $this->codec());        
      }
      
      return $this->_JSONObj;      
    }
    
        
    /** Responses can be accessed like an array to get the headers.
     *  eg: $Response['Content-Type'] === 'text/html'
     */
     
    public function offsetExists($offset)
    {
      return isset($this->_Headers[strtolower(trim($offset))]);
    }
    
    /** Responses can be accessed like an array to get the headers.
     *  eg: $Response['Content-Type'] === 'text/html'
     */
    
    public function offsetGet($offset)
    {
      return $this->_Headers[strtolower(trim($offset))];
    }
    
    /** It is not permitted to modify the headers of a response.
     */
    
    public function offsetSet($offset,$value)
    {
      throw new Exception("You can not set a header in a response.");
    }
    
    /** It is not permitted to modify the headers of a response.
     */
     
    public function offsetUnset($offset)
    {
      throw new Exception("You can not unset a header in a response.");
    }
    
    /** Return the object's codec object (passed at creation). 
     *
     * @return gogoOAuthCodec
     */
     
    protected function codec()
    {
      return $this->_Codecs;
    }
  }

?>