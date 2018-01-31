gogoOAuth - PHP OAuth Made Easy
==============================================================================

OAuth, especially 3-legged OAuth, is traditionally a mind numbing experience for developers new to the process, there are a number of libraries about for doing the hard work, but they are indeed quite hard work themselves.

gogoOAuth however, quite simply, makes it easy.  The package aims to automated and generalize as much as possible about using an OAuth based API as possible, often when using gogoOAuth you won't even need to know if you are talking xml, json or urlencoded parameters, "it just works".

gogoOAuth was written by James Sleeman, Gogo Internet Services - http://www.gogo.co.nz/, it is licenced under the MIT License and may be freely redistributed, modified and used in both private and commercial projects - "Do with it what you will."


The authorisation process.
------------------------------------------------------------------------------

First, we must create ourselves a gogoOAuth object, we will provide the key and secret which you obtained from the providor of the API you are connecting to, the address of a callback (we will cover that in a minute), the base url for general api calls, and the base url for oauth api calls, consult your providors documentation to work this out.

    $OAuth = new gogoOAuth
      ( 
        $consumer_key, 
        $consumer_secret, 
        'http://yoursite.com/callback.php',
        'https://www.server.com/api/', 
        'https://www.server.com/oauth/' 
      );

Now we need to see if we already have an access token, an access token is a long-lived token which we can use many times, so once you get it, store it somewhere.  The `get_the_access_token_from_somewhere()` method is not defined by gogoOAuth, store and retrieve access tokens however you so wish.  A token in gogoOAuth as an associative array (single dimension).

    $accessToken = get_the_access_token_from_somewhere(); // If it exists

### When you do not have an access token yet.  ###

If you do not have an access token, you need to first get a request token from OAuth and save that somewhere (in the session is fine, it does not live long), then get an `authorize_url`, and finally redirect the user to that url.  That url will be at your API provider website and when the user is finished there, they will be redirected to your callback url which you specified in the creation of gogoOAuth. 

    if(!isset($accessToken)) // We do not have an access token, we need to get one
    {
        $Token = $OAuth->get_request_token();
        save_the_request_token_to_somewhere($Token);
        
        $AuthURL = $OAuth->get_authorize_url();
        header('location: {$AuthURL}');
    }
      
When the user is redirected to your callback, this is what it needs to do...

### callback.php  ###

    $OAuth->set_token(get_the_request_token_from_somewhere());
    $accessToken = $OAuth->get_access_token($_GET['oauth_verifier']);
        
    save_the_access_token_to_somewhere($accessToken);      

### When you have an access token. ###

Once you have retrieved an access token (which you stored in a database for future reference!) you simply provide it to OAuth.

    // We have an access token, so we can use it
    $OAuth->set_token($accessToken);
      
And then you can perform GET, POST and DELETE requests.

Day to day operations
------------------------------------------------------------------------------

For day to day operations, you simply create your $OAuth object and provide it with the accesstoken which you obtained on a previous occasion, then you can perform GET/POST/DELETE requests using the OAuth object.

  $OAuth = new gogoOAuth
  ( 
    $consumer_key, 
    $consumer_secret, 
    'http://yoursite.com/callback.php',
    'https://www.server.com/api/', 
    'https://www.server.com/oauth/' 
  );
  $accessToken =  get_the_access_token_from_somewhere();
  $OAuth->set_token($accessToken);

  echo $OAuth->get('/path/to/endpoint.xml');
        
### GET Requests  ###

GET requests are performed using the `get()` method of the OAuth, simply provide it with the "endpoint" and an array of parameters.  The "endpoint" is essentially the URL found in the documentation of the API provider, less the API base url provided when you create the OAuth object.

The response from a `get()` is a fairly smart object which will decode responses in XML, JSON and URLENCODED formats and allow you to reference data directly on the response object.  It also includes standard methods for checking the status, and getting error messages.

The detection of the response type is based largely on the conten type of the response from the server.  See gogoOAuthResponse.php

    $Response = $OAuth->get('Path/To/Method.xml', array('param1' => 'value1')); // Requests ...../Method.xml?param1=value1

    // Check for errors
    echo $Response->is_ok() ? "Everything is OK!" : $Response->error_message();
          
    echo $Response; // Full XML of the request
    echo $Response->SomeElement; // For XML requests, Response behaves just like an SimpleXMLElement

    // Json responses usually work in the same way
    $Response = $OAuth->get('Path/To/Method.json', array('param1' => 'value1')); // Requests ...../Method.xml?param1=value1
    echo $Response; // Json encoded response we got
    echo $Response->SomeProperty; // As for XML, we can reference right into the response.
    echo $Response->SomeProperty->SomeSubProperty;  // And deeper.
    foreach($Response->SomeProperty as $v) echo $v; // Arrays are handled as you would expect, but this may differ from XML output.  Beware.

    // Urlencoded response (param1=val1&param2=val2....) are as for json
    $Response = $OAuth->get('Path/To/Method.php', array('param1' => 'value1')); // Requests ...../Method.xml?param1=value1
    echo $Response; // Urlencoded encoded response we got
    echo $Response->SomeProperty; // As for XML, we can reference right into the response.
    echo $Response->SomeProperty->SomeSubProperty;  // And deeper.
    foreach($Response->SomeProperty as $v) echo $v; // Arrays are handled as you would expect, but this may differ from XML output.  Beware.
      
### POST Requests ### 

POST requests are performed using the `post()` method of OAuth, provide it with the endpoint as you did for `get()` requests, and an array of parameters.  This time the paramaters will be encoded according to the requests's content type (which will be automatically detected if not provided).

      
    //  We can autodetect the format required in POST from the extention.
    $Response = $OAuth->post('Path/To/Method.xml', array('param1' => 'value1')); // Will post XML <param1>value1</param1>
    $Response = $OAuth->post('Path/To/Method.json', array('param1' => 'value1'));// Will post JSON { "param1": "value1" }

    //  We fall back to urlencoded if the extention is unknown (or not useful)
    $Response = $OAuth->post('Path/To/Method.php', array('param1' => 'value1')); // Will post param1=value1

    // Detection can be overridden by content type
    $Response = $OAuth->post('Path/To/Method.xml', array('param1' => 'value1'), 'application/x-www-form-urlencoded'); // Will post param1=value1
    $Response = $OAuth->post('Path/To/Method.xml', array('param1' => 'value1'), 'application/json'); // Will post { "param1": "value1" }
    $Response = $OAuth->post('Path/To/Method.xml', array('param1' => 'value1'), 'text/xml'); // Will post <param1>value1</param1>

    // In all cases, you can supply the parameters as a string in the approprate format if you prefer
    $Response = $OAuth->post('Path/To/Method.xml','<param1>value1</param1>');
    $Response = $OAuth->post('Path/To/Method.json','{ "param1": "value1" }');
    $Response = $OAuth->post('Path/To/Method.json','param1=value1');

    // For XML posts you can supply the parameters as a SimpleXMLElement
    $Response = $OAuth->post('Path/To/Method.xml',
        
There is one more little time saver with `post()`, you can suffix the endpoint with a double colon followed by the name of a root element which will enclose the supplied parameters, that is, you can write

    $OAuth->post('Path/To/Method.xml::ElementName', array('param1'=> 'value1'));
    
this will cause the POST contents to be `<ElementName><param1>value1</param1></ElementName>`.

        
### DELETE Requests ### 
    // Work exactly the same as GET requests just with a different name
    $Response =  $OAuth->delete('Path/To/Method.xml', array('param1' => 'value1'));


Licence
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
      