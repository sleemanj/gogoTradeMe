<?php
    
  /** Obtain a singleton trademe object 
   *
   */
   
  function trademe()
  {
    static $trademe;
    if(isset($trademe)) return $trademe;
      
    require_once('config.php');
    
    if(!isset($consumer_secret) || !isset($consumer_key))
    {
      throw new Exception("You must edit config.php to enter your consumer secret and key, which you obtain from Trade Me");
    }
    
    
    if($environment == 'sandbox')
    {
      require_once('../lib/gogoTradeMe/gogoTradeMeSandbox.php');
      $trademe = new gogoTradeMeSandbox
          ( 
            $consumer_key,                          // Obtain from "My TradeMe"
            $consumer_secret,                       // Obtain from "My TradeMe"
            $callback_url                           // Handles the return from TradeMe
          );
    }
    else
    {
      require_once('../lib/gogoTradeMe/gogoTradeMe.php');
      $trademe = new gogoTradeMe
          ( 
            $consumer_key,                          // Obtain from "My TradeMe"
            $consumer_secret,                       // Obtain from "My TradeMe"
            $callback_url                           // Handles the return from TradeMe
          );      
    }
    
    return $trademe;
  }
     
       
   /** Take a token (array) and store it however you want to do that.
    *
    * For this example we are storing tokens in the session, DON'T DO THAT FOR REAL!
    *
    * Token is an array with the following keys
    *    'oauth_token', 'oauth_token_secret', 'oauth_token_type', 'oauth_token_time'
    *    
    *   oauth_token_type is 'request' or 'access'
    *   oauth_token_time is seconds since epoch that the token was created  
    *
    * @param array Token
    */
      
    function store_token($Token)
    {
      @session_start();
      if(!isset($_SESSION['TradeMe'])) $_SESSION['TradeMe'] = array();
      
      $_SESSION['TradeMe'][$Token['oauth_token_type']] = $Token;
    }
    
   /** Get a token you stored previously, by it's type (request or access)
    *  
    *  @return array|NULL
    */
    
    function retrieve_token($oauth_token_type)
    {
      @session_start();
      if(!isset($_SESSION['TradeMe'])) $_SESSION['TradeMe'] = array();
      if(!isset($_SESSION['TradeMe'][$oauth_token_type]))
      {
        throw new Exception('You have not stored a '.$oauth_token_type.' token, you probably need to hit connect.php');
      }
      return @$_SESSION['TradeMe'][$oauth_token_type];
    }
?>