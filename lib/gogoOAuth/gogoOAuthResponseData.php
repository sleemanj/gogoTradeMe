<?php
   /*
      Licence
      -------------------------------------------
      Copyright (c) 2011, Gogo Internet Services Limited - http://www.gogo.co.nz/
      All rights reserved.

      Redistribution and use in source and binary forms, with or without
      modification, are permitted provided that the following conditions are met:
          * Redistributions of source code must retain the above copyright
            notice, this list of conditions and the following disclaimer.
          * Redistributions in binary form must reproduce the above copyright
            notice, this list of conditions and the following disclaimer in the
            documentation and/or other materials provided with the distribution.
          * Neither the name of Gogo Internet Services Limited nor the
            names of its contributors may be used to endorse or promote products
            derived from this software without specific prior written permission.

      THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
      ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
      WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
      DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
      DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
      (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
      LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
      ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
      (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
      SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
      
  */
  
  /** This class is used as the base class for some (not all) Response Data types, 
   *  namely at this point, json and urlencoded query string type responses.
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
   */
   
  class gogoOAuthResponseData extends ArrayObject
  {
    private $_Data;
    private $_Codecs;
    
    /** Create a response data object from an array 
     *
     *  @param array
     */
    
    public function __construct($Data, $Codecs)
    {
      $this->_Data = $Data;
      return parent::__construct($Data, ArrayObject::ARRAY_AS_PROPS);
    }
        
    /** Getting a property will return either a scalar, or another gogoOAuthUrlDecodedObject
     *
     *  This means that you can access in pretty similar ways for typical representations...
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
     */
     
    public function __get($k)
    {
      $v = parent::__get($k);
      
      if(is_array($v))
      {
        // We want any child arrays to be objects too, that is so we can do...
        //   "SomeObjectName[SomeProperty]=Foo"
        //   $MyResponse->SomeObjectName->SomeProperty               

        // this makes it the same as JSON responses...
        // {SomeObjectName: { SomeProperty: 'Foo' }}
        // $MyResponse->SomeObjectName->SomeProperty
        
        // and at least identical to XML in the *basic* case
        // <Response><SomeObjectName><SomeProperty>...</SomeObjectName></SomeProperty></Response>
        // $MyResponse->SomeObjectName->SomeProperty
                
        $v = new gogoOAuthUrlDecodedObject($v);
        parent::__set($k, $v);        
      }
      
      return $v;
    }
    
    /** Getting an index will return either a scalar, or another gogoOAuthUrlDecodedObject 
     *
     */
     
    public function offsetGet($k)
    {
      $v = parent::offsetGet($k);
      
      if(is_array($v))
      {
        // We want this object to recurse over any child arrays, that is so we can do
        // $MyResponse->SomeObjectParam->SomeSubParam
        // instead of needing to do $MyResponse->SubObjectParam['SomeSubParam']
        
        $v = new gogoOAuthUrlDecodedObject($v);
        parent::offsetSet($k, $v);        
      }
      
      return $v;
    }
    
    /** 
     * Return the data as an array (nested structure).
     *
     * @return array
     */
     
    public function to_array()
    {
      return $this->_Data;
    }
    
    
    /** It is not permitted to modify the response, __set() is disabled. */
    
    public function __set($k, $v)
    {
      throw new Exception("Responses are read only.");
    }
    
    /** It is not permitted to modify the response, __unset() is disabled. */
    
    public function __unset($k)
    {
      throw new Exception("Responses are read only.");
    }
    
    /** It is not permitted to modify the response, offsetUnset() is disabled. */
    
    public function offsetUnset($k)
    {
      throw new Exception("Responses are read only.");
    }
    
    /** It is not permitted to modify the response, offsetSet() is disabled. */
    
    public function offsetSet($k, $v)
    {
      throw new Exception("Responses are read only.");  
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