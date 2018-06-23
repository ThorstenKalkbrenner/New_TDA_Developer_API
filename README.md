# TDA_Developer_API
new TDA developer API tools

stream stock quotes and futures with nodejs

nodejs quoteStream.js

saves quotes, asks and bids on filesystem

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

...

see file.

IMPORTANT: make sure to run this in a protected / single user / vm (or docker) enviroment.
