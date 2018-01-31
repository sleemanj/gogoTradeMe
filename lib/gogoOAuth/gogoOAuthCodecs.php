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
  
  /** A small collection of codec type methods used by gogoOAuth.
   *
   * @package gogoOAuth
   * @author James Sleeman <james@gogo.co.nz>
   * @licence BSD
   */
  
  class gogoOAuthCodecs
  {
    protected $QueryStringOpts;
    protected $JsonOpts;
    protected $XMLOpts;
    
    function __construct( 
        $QueryStringOpts = array('Boolean' => array(0 , 1)), 
        $JsonOpts = array(), 
        $XMLOpts = array('Boolean' => array('false', 'true')) 
    )
    {
      $this->QueryStringOpts = $QueryStringOpts;
      $this->JsonOpts = $JsonOpts;
      $this->XMLOpts = $XMLOpts;
    }
    
    /**
     * rawurlencode
     *  just like the built in PHP one, and uses it, with the exception that the PHP one
     *  did not always leave ~ unencoded as required by current RFC.  This one always leaves
     *  it unencoded.
     *
     * This one can also encode a single dimension array of parameters for convenience.
     *
     * @param array|string $input A parameter or set of parameters to encode.
     * @return array|string
     */

    public function rawurlencode($input)
    {
      if(is_scalar($input)) 
      {
        if (version_compare(PHP_VERSION, '5.3.0') >= 0) 
        {
          return rawurlencode($input);
        }
        else
        {
          return str_replace('%7E', '~', rawurlencode($input));          
        }
      }
      elseif(is_array($input)) 
      {
        return array_map(array($this, 'rawurlencode'), $input);
      }        
      else 
      {
        return '';
      }
    }
    
    /** Take a query string and return an array.
     *  
     *  Essentially this is PHP's built in parse_str() with the exception that it 
     *  always checks that magic_quotes_gpc is switched off before it parses
     *
     * @param  String
     * @return Array
     */
    
    public function from_query_string($string)
    {
      $RestoreGPC = false;
      
      if(function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc())
      { 
        // Well, I certainly don't want MQ GPC, but we will turn it back on when we are
        // done parsing the string just incase your main application is braindead
        $RestoreGPC = true;
        ini_set('magic_quotes_gpc', false);
      }
      parse_str($string, $data);
      
      if($RestoreGPC) ini_set('magic_quotes_gpc', true);
      
      return $data;
    }
    
   /** Do the reverse of the php builtin parse_str - convert an array
    * into a query string.  Handles both scalars and arrays (associative and otherwise)
    * in the way that you would expect.
    *
    * @note http_build_query() is a PHP built in which does basically the same thing
    *    trouble is, it sounds to have some bugs and we can't control the true/false
    *    values, which might be important to us.
    *
    * @param Array     $vars         associative array of variables
    * @param String    $true         String to use for bool(true)
    * @param String    $false        String to use for bool(false)
    * @param String    $separator    Character/string to use as the separator, typically '&'
    * @param Bool      $skip_numeric Skip numerically indexed entries in the array(s)
    * @param Array     $arr_pfx      prefix (private, internal use)
    *
    * @return string Query string (not including leading "?")
    */

    public function to_query_string($vars, $true = NULL, $false = NULL, $separator = '&', $skip_numeric = FALSE, $arr_pfx = NULL )
    {
      $out = array();
      $snd = false;
      
      if(!isset($false)) $false = $this->QueryStringOpts['Boolean'][0];
      if(!isset($true))  $true  = $this->QueryStringOpts['Boolean'][1];
      
      foreach ( $vars as $key=>$val )
      {
        if ( !is_array($val) )
        {
          if(is_numeric($key) && ($skip_numeric || !$arr_pfx)) continue;
          
          if(is_bool($val))  
          { 
            $val = $val ? $true : $false; 
          }
          
          if($arr_pfx)
          {
            $key = "$arr_pfx[$key]";
          }
          
          $out[] = $this->rawurlencode($key) . "=" . $this->rawurlencode($val);
        } 
        else 
        { // Recurse into arrays
          if(is_numeric($key) && ($skip_numeric || !$arr_pfx)) continue;
          
          if($arr_pfx)
          {
            $key = "$arr_pfx[$key]";
          }
          
          $v = $this->to_query_string($val,$true,$false,$separator,$skip_numeric,$key);
          if($v) $out[] = $v;
        }
      }

      return implode($separator, $out);
    }
      
    /** Decode a string from json     
     *  Always produces a nested associated array or a scalar, does NOT produce an object (at any level).
     *
     *  @param string
     *  @return array|scalar     
     */
      
    public function json_decode($string)
    {    
      if(!function_exists('json_decode'))
      {
        throw new Exception('Your PHP does not have json_decode(), this means you either are using an old PHP version, or you have not enabled the json extention.  You need to either upgrade PHP, add the json extention, or implement your own json_decode() function (see comments in the json_decode page on php.net for ideas).');
      }
      else
      {
        return json_decode($string, true);
      }
    }
    
    /** Encode a string to json     
     *
     *  @param  array|scalar 
     *  @return string
     */
      
    public function json_encode($structure)
    {    
      if(!function_exists('json_encode'))
      {
        throw new Exception('Your PHP does not have json_decode(), this means you either are using an old PHP version, or you have not enabled the json extention.  You need to either upgrade PHP, add the json extention, or implement your own json_encode().');
      }
      else
      {
        return json_encode($structure);
      }
    }
    
    
    /** Convert a nested array structure into an XML string.
     *
     *  @param array
     *  @return string
     */
    
    public function array_to_xml($array)
    {
      if(is_scalar($array))
      {
        $v = $array;
        switch(gettype($v))
        {
          case 'boolean': $v = $v ? $this->XMLOpts['Boolean'][1] : $this->XMLOpts['Boolean'][0]; break;          
        }
        return htmlspecialchars( $v );
      }
      
      $xml = '';
      foreach($array as $k => $v)
      {
        if(!isset($v)) continue;
        
        if(is_array($v))
        {
          $keys = array_keys($v);
          if(is_numeric(array_pop($keys)))
          { // A numerically indexed array is multiple copies of the element
            foreach($v as $vv)
            {
              $xml .= "<{$k}>".$this->array_to_xml($vv)."</{$k}>";              
            }
            continue;
          }
        }
        
        $xml .= "<{$k}>".$this->array_to_xml($v)."</{$k}>";
      }
      
      return $xml;
    }
    
    /** Convert a SimpleXML structure, or a string of XML into a nested array.
     */
    public function xml_to_array($xml)
    {
      
      $k = $xml->getName();
      
      if($xml->count())
      {
        $v      = array();        
        $multi = array();
        foreach($xml->children() as $child)
        {
          if(isset($v[$child->getName()]))
          {
            if(!$multi[$child->getName()]) 
            {
              $v[$child->getName()] = array($v[$child->getName()]);
              $multi[$child->getName()] = true;
            }
            $v[$child->getName()][] = array_pop($this->xml_to_array($child));
          }
          else
          {
            $v[$child->getName()] = array_pop($this->xml_to_array($child));
            $multi[$child->getName()] = false;
          }
        }        
      }
      else
      {
        $v = (string) $xml;
      }
      
      return array($k => $v);      
    }
    
  }
?>