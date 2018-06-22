<?php


$path   = "logs/";
$userid = ''; // tda userid

$tokenfile = $path . "tdoa.json";
$userid = 
$expiresecs = 1800 - 3;

$d = json_decode(file_get_contents($tokenfile));

$out    = "/tmp/tdout.".getmypid(); // CHANGE this to a protected directory if you are running script in a multi user enviroment
$outerr = "/tmp/tdouterr.".getmypid();

if ($argc == 1) { $argv[1] = 'positions'; }

function refreshAuth() {
    global $d, $tokenfile, $path;
    $cmdr = 'curl -s -X POST --header "Content-Type: application/x-www-form-urlencoded" -d "grant_type=refresh_token&refresh_token='.urlencode($d->refresh_token).'&access_type=offline&code=&client_id=QBQB%40AMER.OAUTHAP&redirect_uri=" "https://api.tdameritrade.com/v1/oauth2/token" >'.$tokenfile.'.tmp 2>'.$path.'tdoa.err';
    system($cmdr);
    $tmp = file_get_contents($tokenfile.'.tmp');
    if (stristr($tmp, 'error') || !stristr($tmp, 'access_token') || !stristr($tmp, 'refresh_token')) {
        echo "refreshAuth ERROR:\n".$tmp."\n";
        exit;
    }
    else {
        file_put_contents($tokenfile, $tmp);
        unlink($tokenfile.'.tmp');
        $d = json_decode(file_get_contents($tokenfile));
    }
}

for ($i = 0; $i < 2; $i++) {
#    echo "round $i\n";
    
if ($argv[1] == 'refresh') {
    refreshAuth();
    exit;
}

clearstatcache();
$age = time() - filemtime($tokenfile);
if ($age > $expiresecs && $i == 0) {
    echo "$tokenfile too old at $age secs... refreshing... \n";
    refreshAuth();
    continue;
}

else if ($argv[1] == 'history') {
    $cmd = 'curl -s -X GET --header "Authorization: Bearer '.$d->access_token.'" "https://api.tdameritrade.com/v1/marketdata/AMZN/pricehistory?periodType=day&period=10&frequencyType=minute&frequency=1"';
}
    
else if ($argv[1] == 'historytoday') {
    $cmd = 'curl -s -X GET --header "Authorization: Bearer '.$d->access_token.'" "https://api.tdameritrade.com/v1/marketdata/AMZN/pricehistory?\
periodType=day&period=5&frequencyType=minute&frequency=1&endDate='.(time()*1000).'"';
}

else if ($argv[1] == 'transactions') {
    $cmd = 'curl -s -X GET --header "Authorization: Bearer '.$d->access_token.'" ""https://api.tdameritrade.com/v1/accounts/'.$userid.'/transactions?symbol="'.$argv[2].'&startDate=2018-01-01"';
}

else if ($argv[1] == 'hours') {
    $cmd = 'curl -s -X GET --header "Authorization: Bearer '.$d->access_token.'" "https://api.tdameritrade.com/v1/marketdata/EQUITY/hours"';
}

else if ($argv[1] == 'movers') {
    $cmd = 'curl -s -X GET --header "Authorization: Bearer '.$d->access_token.'" "https://api.tdameritrade.com/v1/marketdata/'.urlencode('$'.$argv[2]).'/movers?direction='.$argv[3].'&change=percent"';
}

else if ($argv[1] == 'orderstatus') {
    $cmd = 'curl -s -X GET --header "Authorization: Bearer '.$d->access_token.'" "https://api.tdameritrade.com/v1/accounts/'.$userid.'/orders/'.$argv[2].'"';
}

else if ($argv[1] == 'ordercancel') {
    $cmd = 'curl -s -X DELETE --header "Authorization: Bearer '.$d->access_token.'" "https://api.tdameritrade.com/v1/accounts/'.$userid.'/orders/'.$argv[2].'"';
}

else if ($argv[1] == 'quotes') {
    $cmd = 'curl -s -X GET --header "Authorization: Bearer '.$d->access_token.'" "https://api.tdameritrade.com/v1/marketdata/quotes?symbol='.$argv[2].'"';
}

else if ($argv[1] == 'subscription') {
    $cmd = 'curl -s -X GET --header "Authorization: Bearer '.$d->access_token.'" "https://api.tdameritrade.com/v1/userprincipals?fields=streamerSubscriptionKeys%2CstreamerConnectionInfo"';
}

else if ($argv[1] == 'order') {
    if ($argc != 6) {
        echo "args: order Buy|Sell quantity stock limit_price\n";
        exit;
    }
    $action   = $argv[2];
    $quantity = $argv[3];
    $stock    = $argv[4];
    $price    = $argv[5];
$cmd = 'curl -s -X POST --header "Authorization: Bearer '.$d->access_token.'" --header "Content-Type: application/json" -d "{
  '.($price > 0 ? '\"orderType\": \"LIMIT\", \"price\" : '.$price.',' : '\"orderType\": \"MARKET\",').'
  \"session\": \"NORMAL\",
  \"duration\": \"DAY\",
  \"orderStrategyType\": \"SINGLE\",
  \"orderLegCollection\": [
    {
      \"instruction\": \"'.$action.'\",
      \"quantity\": '.$quantity.',
      \"instrument\": {
        \"symbol\": \"'.$stock.'\",
        \"assetType\": \"EQUITY\"
      }
    }
  ]
}" "https://api.tdameritrade.com/v1/accounts/'.$userid.'/orders"';
}
else if ($argv[1] == 'orderext') {
    if ($argc != 6) {
        echo "args: orderext Buy|Sell quantity stock limit_price\n";
        exit;
    }
    $action   = $argv[2];
    $quantity = $argv[3];
    $stock    = $argv[4];
    $price    = $argv[5];
$cmd = 'curl -s -X POST --header "Authorization: Bearer '.$d->access_token.'" --header "Content-Type: application/json" -d "{
  '.($price > 0 ? '\"orderType\": \"LIMIT\", \"price\" : '.$price.',' : '\"orderType\": \"MARKET\",').'
  \"session\": \"SEAMLESS\",
  \"duration\": \"GOOD_TILL_CANCEL\",
  \"orderStrategyType\": \"SINGLE\",
  \"orderLegCollection\": [
    {
      \"instruction\": \"'.$action.'\",
      \"quantity\": '.$quantity.',
      \"instrument\": {
        \"symbol\": \"'.$stock.'\",
        \"assetType\": \"EQUITY\"
      }
    }
  ]
}" "https://api.tdameritrade.com/v1/accounts/'.$userid.'/orders"';
}
else {
    $cmd = 'curl -s -X GET --header "Authorization: Bearer '.$d->access_token.'" "https://api.tdameritrade.com/v1/accounts/'.$userid.'?fields=positions"';
}

system($cmd . ' >'.$out.' 2>'.$outerr);

$errorstr = @file_get_contents($out);
if (stristr($errorstr, '"error":') && (stristr($errorstr, 'Invalid') || stristr($errorstr, 'expired') || stristr($errorstr, 'The access token being passed has expired or is invalid'))) {
    echo "Refreshing Access Token...\n";
    if ($i > 0) {
        echo "Sleeping...\n";
        sleep(3);
    }
    refreshAuth();
    continue;
}
break;
}

if ($argv[1] != 'refresh') {
    if ($argv[1] == 'hours') {
        if (json_decode(file_get_contents($out))->equity->EQ->isOpen > 0) {
            echo json_decode(file_get_contents($out))->equity->EQ->sessionHours->regularMarket[0]->end . "\n";
        }
        else {
            echo "Market is CLOSED\n";
        }
    }
    else if ($argv[1] == 'movers') {
        foreach (json_decode(file_get_contents($out)) as $m) {
            echo $m->symbol . "\t" . round($m->change * 100, 2) . "%\t" . round($m->last,2) . "\t" . $m->description . "\n";
        }
    }
    else {
        print_r(json_decode(file_get_contents($out)));
        if ($argv[1] == 'subscription') {
            file_put_contents($path . 'tdoaprincipals.json',
            file_get_contents($out));
        }
    }
}

$errorstr = @file_get_contents($outerr);
if ($errorstr != null && strlen($errorstr) > 0) {
    echo "ERROR: $errorstr \n";
}

?>
