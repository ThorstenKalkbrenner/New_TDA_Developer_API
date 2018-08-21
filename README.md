# TDA_Developer_API
TDA developer API tools based on https://developer.tdameritrade.com/apis

stream stock quotes and futures with nodejs and saves quotes, asks and bids on filesystem:

nodejs quoteStream.js


to use:
1.) save initial access_token and refresh_token response in a file tdoa.json as outlined in
https://developer.tdameritrade.com/content/simple-auth-local-apps

then:
2.) php tdAuth.php subscription => get subscription credentials for node streamer above

more functions:
- movers
- hours
- quotes
- order (regular and orderext for after hour orders)
- ordercancel
- orderstatus
- transactions
- history

examples:

# falling / rising stocks:
php tdAuth.php movers COMPX up
php tdAuth.php movers SPX.X down

# market hours
php tdAuth.php hours

# buy in extented hours 300 XLI limit 74.82
php tdAuth.php orderext buy 300 XLI 74.82

# Quote
php tdAuth.php quotes AAPL

...

see file.

IMPORTANT: make sure to run this in a protected / single user / vm (or docker) enviroment.
