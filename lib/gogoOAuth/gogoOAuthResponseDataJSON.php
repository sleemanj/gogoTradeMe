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
  
  require_once('gogoOAuthResponseData.php');
  
  /** This class is returned as the data for a response when the typeof response is a json string
   *
   *  JSON:         {"List": [ {Title: 'Foo' } ]}   
   *   
   *  JSON:         $Title = (string) $Response->List[0]->Title
   *
   *  JSON:         foreach($Response->List      as $Job) { }
   */
   
  class gogoOAuthResponseDataJSON extends gogoOAuthResponseData
  {
    private $_IsSimpleScalar = FALSE;
  
    /** Construct a JSON Response Data Object, from a JSON string.
     *
     * @param string|array String containing JSON, or an array already parsed from JSON
     */
     
    public function __construct($JSONOrPreParsedArray, $Codecs)
    {
      if(is_string($JSONOrPreParsedArray))
      {
        $JSONOrPreParsedArray = $Codecs->json_decode($JSONOrPreParsedArray);
        if(!is_array($JSONOrPreParsedArray))
        { // The JSON was obviously just a scalar of some sort, we will
          // call it an array of 1 element, and our __tostring() will take 
          // care of the rest.
          $this->_IsSimpleScalar = TRUE;
          $JSONOrPreParsedArray = array($JSONOrPreParsedArray);
        }
      }
      
      return parent::__construct($JSONOrPreParsedArray, $Codecs);
    }
        
    /** For simple scalar vales, we return the scalar value, for more complicated things
     *  we return encoded json of that thing.
     *  
     */
    
    public function __tostring()
    {
      if($this->_IsSimpleScalar) 
      {
        return (string) $this[0];    
      }
      return $this->codec()->json_encode($this->to_array());
    }
               
  }  
?>