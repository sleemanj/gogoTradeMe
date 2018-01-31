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
  
  /** This class is returned as the data for a response when the typeof response is a urlencoded query string
   *
   *  URL Encoded:  List[0][Title]=Foo
   *
   *  URL Encoded:  $Title = (string) $Response->List[0]->Title
   *
   *  URL Encoded:  foreach($Response->List      as $Job) { }
   */
   
  require_once('gogoOAuthResponseData.php');
  
  class gogoOAuthResponseDataURLEncoded extends gogoOAuthResponseData
  {
    /** Construct a urlencoded Response Data Object, from a urlencoded (query) string.
     *
     * @param string|array String containing URL Encoded query, or an array already parsed
     */
     
    public function __construct($URLEncodedQueryStringOrPreParsedArray, $Codecs)
    {
      if(is_string($URLEncodedQueryStringOrPreParsedArray))
      {
        $URLEncodedQueryStringOrPreParsedArray = $Codecs->from_query_string($URLEncodedQueryStringOrPreParsedArray);
      }
      
      return parent::__construct($URLEncodedQueryStringOrPreParsedArray, $Codecs);
    }
        
    /** We tostring return as a query string for anything but a simple scalar.
     *
     */
    
    public function __tostring()
    {
      return $this->codec()->to_query_string($this->to_array());
    }
    
  }  
?>