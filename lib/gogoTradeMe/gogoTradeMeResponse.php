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
  
  class gogoTradeMeResponse extends gogoOAuthResponse
  {
  
    /** Trademe returns 200 for even errors :-(
     *
     * @return bool
     */
    
    public function is_ok()
    {    
      if(!parent::is_ok()) return FALSE;
      if(isset($this->Data()->ErrorDescription) || isset($this->data()->Error)) return FALSE;
      if(isset($this->Data()->Success) && (string) $this->Data()->Success == 'false') return FALSE;
      return TRUE;
    }
    
    /** We extend the standard error message to return errors from TradeMe's XML/results, and 
     *  detech some other things like 404 errors and such.
     */
     
    public function error_message()
    {
      if($this->is_ok()) return false;
      
      if($this->is('text/html'))
      {
        return "Unknown Error, Probably Incorrect TradeMe Method URL " . parent::error_message();
      }           
      
      $data = $this->data(); // Take advantage of data() to make this format agnostic.
        
      if(isset($data->Error))            return $data->Error;        
      if(isset($data->ErrorDescription)) return $data->ErrorDescription; // Consistency anybody? :-(
      if(isset($data->Description))      return $data->Description;      // Really now guys, this is stupid! :-(
      
      return 'Unknown Error';
    }
    
  }
  
?>