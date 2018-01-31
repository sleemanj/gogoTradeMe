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
  
  /** This class is returned as the data for a response when the typeof response is an xml string
   *  it is just SimpleXMLElement by another name, ideally we would restrict it so that 
   *  it is read only, but I don't think it's possible.
   *
   *  XML:          <Jobs><List><Job><Title>Foo</Title></Job></List></Jobs>
   
   *  XML:          $Title = (string) $Response->List->Job[0]->Title
   *
   *  XML:          foreach($Response->List->Job as $Job) { }
   *
   */
   
  class gogoOAuthResponseDataXML extends SimpleXMLElement
  {        
    function asIndentedXML()
    { 
      if(!function_exists('dom_import_simplexml')) return preg_replace('/</', "\n<", $this->asXML());
      
      $dom = new DOMDocument('1.0');
      $dom->preserveWhiteSpace = false;
      $dom->formatOutput = true;
      $dom->loadXML($this->asXML());
      return $dom->saveXML();
    }
    
  }  
?>