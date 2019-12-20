gogoTradeMe
==============================================================================

:boom: Looking for expert help with the Trade Me API and PHP?  See me in my day job, I am an [expert in Trade Me Website Integration Development](https://code.gogo.co.nz/trade-me-ecommerce-integration-service.html) - sorry for the ad, got to pay the bills you know.

---

NOTICE: Trademe have for some reason removed the developer forum which had much good information, in the mean time you should read the thread at the Wayback Machine

https://web.archive.org/web/20150114234323/http://developer.trademe.co.nz/forum/?mingleforumaction=viewtopic&t=118.0


gogoTradeMe provides a SIMPLE implementation of OAuth in PHP, utilising CURL, 
and specifically intended and configured for accessing the TradeMe API.

This software is not produced, endorsed or supported by Trade Me Limited, it 
is an unofficial open source package providing a convenient interface to 
connect PHP applications to the published Trade Me API.

It ONLY handles the "guts" of authorising with OAuth and issuing requests.  

Caching, token storage etc is all left up to you to do however you want.

The best way to learn how to use gogoTradeMe is to grab the source, 
put it somwhere you can access it and hit the exampe/index.php

    First, Edit:  example/include/config.php
    Then,   Hit:  example/index.php

    Now,   Read:  example/connect.php
           Then:  example/callback.php
            And:  example/watchlist.php
            And:  example/save_note.php

gogoTradeMe was written by James Sleeman, Gogo Internet Services Limited 
- http://www.gogo.co.nz/

And once more to be abundantly clear, this software is not produced, endorsed 
or supported by Trade Me Limited, it is an unofficial open source package 
providing a convenient interface to connect PHP applications to the published 
TradeMe API.

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
