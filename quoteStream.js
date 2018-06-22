var WebSocket = require('ws')
var fs = require('fs')

function jsonToQueryString(json) {
    return Object.keys(json).map(function(key) {
	return encodeURIComponent(key) + '=' +
	    encodeURIComponent(json[key]);
    }).join('&');
}

var userPrincipalsResponse;
var stockslist = fs.readFileSync("stocks.txt", "utf8").trim(); // comma delimited list of stocks

fs.readFile('tdoaprincipals.json', 'utf8', function (err,data) { // Get User Principals Response at https://developer.tdameritrade.com/user-principal/apis/get/userprincipals-0
    if (err) { return console.log(err); }
    
    userPrincipalsResponse = JSON.parse(data);

    var tokenTimeStampAsDateObj = new Date(userPrincipalsResponse.streamerInfo.tokenTimestamp);
    var tokenTimeStampAsMs = tokenTimeStampAsDateObj.getTime();

    var credentials = {
	"userid": userPrincipalsResponse.accounts[0].accountId,
	"token": userPrincipalsResponse.streamerInfo.token,
	"company": userPrincipalsResponse.accounts[0].company,
	"segment": userPrincipalsResponse.accounts[0].segment,
	"cddomain": userPrincipalsResponse.accounts[0].accountCdDomainId,
	"usergroup": userPrincipalsResponse.streamerInfo.userGroup,
	"accesslevel": userPrincipalsResponse.streamerInfo.accessLevel,
	"authorized": "Y",
	"timestamp": tokenTimeStampAsMs,
	"appid": userPrincipalsResponse.streamerInfo.appId,
	"acl": userPrincipalsResponse.streamerInfo.acl
    }
    
    var request = {
	"requests": [
	    {
		"service": "ADMIN",
		"command": "LOGIN",
		"requestid": 0,
		"account": userPrincipalsResponse.accounts[0].accountId,
		"source": userPrincipalsResponse.streamerInfo.appId,
		"parameters": {
		    "credential": jsonToQueryString(credentials),
		    "token": userPrincipalsResponse.streamerInfo.token,
		    "version": "1.0"
		}
	    }
	]
    }
    
    var quoterequest = {
	"requests": [
	    {
		"service": "QUOTE",
		"requestid": 1,
		"command": "SUBS",
		"account": userPrincipalsResponse.accounts[0].accountId,
		"source": userPrincipalsResponse.streamerInfo.appId,
		"parameters": {
		    "keys": stockslist,
		    "fields": "0,1,2,3"
		}
	    }
	]
    }

    var chartequitiesrequest = {
	"requests": [
	    {
		"service": "CHART_EQUITY",
		"requestid": 2,
		"command": "SUBS",
		"account": userPrincipalsResponse.accounts[0].accountId,
		"source": userPrincipalsResponse.streamerInfo.appId,
		"parameters": {
		    "keys": "AAPL",
		    "fields": "0,1,2,3,4,5,6,7"
		}
	    }
	]
    }

    var chartfuturesrequest = {
	"requests": [
	    {
		"service": "CHART_FUTURES",
		"requestid": 3,
		"command": "SUBS",
		"account": userPrincipalsResponse.accounts[0].accountId,
		"source": userPrincipalsResponse.streamerInfo.appId,
		"parameters": {
		    "keys": "/VX,/ES,/CL",
		    "fields": "0,1,2,3,4,5,6"
		}
	    }
	]
    }

    var mySock = new WebSocket("wss://" + userPrincipalsResponse.streamerInfo.streamerSocketUrl + "/ws"); 
    var responded = false;
    
    mySock.onmessage = function(evt) { 

	var j = JSON.parse(evt.data);
	var d = new Date(); var now = d.toLocaleTimeString().substr(0, 8);
	if (j.hasOwnProperty('response')) {
	    if (!responded) {
		responded = true;
		if (evt.data.toLowerCase().includes("error")) {
		    console.log("Login ERROR: " + evt.data);
		}
		console.log("Login OK - Adding Stocks: " + stockslist);
		mySock.send(JSON.stringify(quoterequest));
//		mySock.send(JSON.stringify(chartequitiesrequest));
		mySock.send(JSON.stringify(chartfuturesrequest));
	    }
	}
	else if (j.hasOwnProperty('notify')) { }
	else {
	    if (j.hasOwnProperty('data')) {
		for (var k = 0; k < j.data.length; k++) {
		    if (j.data[k].service == 'QUOTE') {		
			var arr = j.data[k].content;
			for (var i = 0; i < arr.length; i++){
			    var obj = arr[i];
			    if (typeof obj[3]  !== "undefined") {
				console.log(now + " " + obj.key + "     " + obj[3]);
				fs.writeFile("quote_oa_" + obj.key, obj[3], function(err) { if (err) { return console.log(err); }; });
			    }
			    if (typeof obj[1]  !== "undefined") {
				fs.writeFile("bid_oa_" + obj.key, obj[1], function(err) { if (err) { return console.log(err); }; });
			    }
			    if (typeof obj[2]  !== "undefined") {
				fs.writeFile("ask_oa_" + obj.key, obj[2], function(err) { if (err) { return console.log(err); }; });
			    }
			}
		    }
		    else if (j.data[k].service == 'CHART_FUTURES') {		
			var arr = j.data[k].content;
			for (var i = 0; i < arr.length; i++){
			    var obj = arr[i];
			    if (typeof obj[5]  !== "undefined") {
				console.log(now + " FUTURES " + obj.key + " " + obj[5]);
			    }
			}
		    }
		    else {
			console.log(evt.data);
		    }
		}
	    }
	    else {
		console.log(evt.data);
	    }
	}
    }; 

    mySock.onclose = function() { console.log("CLOSED Connection."); };

    mySock.onopen = function() { 
	console.log("OPENED Connection to " + userPrincipalsResponse.streamerInfo.streamerSocketUrl);
	mySock.send(JSON.stringify(request)); 
    };
    
});
