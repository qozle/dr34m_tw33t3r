<?PHP


//  Generate unique nonce for each request
function generate_nonce(){
    //  generate 32 bits of random data 
    $random_bytes = random_bytes(32);
    $random_base64 = base64_encode($random_bytes);
    $random_string = preg_replace('/\W/', '', $random_base64);
    return $random_string;
}


//  Generatore Oauth header string
function generate_oAuth($status, $method = 'POST', $url = 'https://api.twitter.com/1.1/statuses/update.json'){
    global $consumer_key;
    global $consumer_secret;
    global $token;
    global $token_secret;
    
    $signing_key = rawurlencode($consumer_secret) . '&' . rawurlencode($token_secret);

    $nonce = generate_nonce();
    $sig_method = "HMAC-SHA1";
    $timestamp = time();
    $version = "1.0";
    
    $base_signature = '';
    
    $oAuth_header_val = 'OAuth ';

    $params = ['oauth_consumer_key'=>$consumer_key, 'oauth_nonce'=>$nonce, 'oauth_signature_method'=>$sig_method, 'oauth_timestamp'=>$timestamp, 'oauth_token'=>$token, 'oauth_version'=>$version, 'status'=>$status];
    $encoded_params = [];
    
    //  % encode each parameter
    foreach($params as $key=>$param){
        $encoded_params[rawurlencode($key)] = rawurlencode($param);
    }
    
    //  sort alphabetically by encoded key
    if(!ksort($encoded_params)){
        return false;
    }

    //  create encoded parameter string
    $encoded_string = '';
    foreach($encoded_params as $key=>$param){
        if($key != array_key_last($encoded_params)){
            $encoded_string .= $key . '=' . $param . "&";
        } else {
            $encoded_string .= $key . '=' . $param;
        }
    }

    //  Create the signature base string
    $base_signature .= strtoupper($method) . '&';
    $base_signature .= rawurlencode($url) . '&';
    $base_signature .= rawurlencode($encoded_string);

    //  Create the signature value
    $calc_signature = hash_hmac('sha1', $base_signature, $signing_key, true);
    $oAuth_signature = base64_encode($calc_signature);
    
    //  remove status from the encoded_params array so we can reuse the array 
    unset($encoded_params['status']);
    //  add the signature to the encoded params array
    $encoded_params['oauth_signature'] =  rawurlencode($oAuth_signature);

    //  Sort it again, becasue something isn't working...
    if(!ksort($encoded_params)){
        return false;
    }

    //  build header string
    foreach($encoded_params as $key=>$param){
        if($key != array_key_last($encoded_params)){
            $oAuth_header_val .= "{$key}=\"{$param}\", ";
        } else {
            $oAuth_header_val .= "{$key}=\"{$param}\"";
        }
    }

    return $oAuth_header_val;
}


//  Send a request
function twitter_request($status, $method = null, $url = null){
    global $debug;
    if(is_null($url)){
        if(is_null($method)){
            $auth = generate_oAuth($status);
        } else {
            $auth = generate_oAuth($status, $method);
        }
    } else {
        if(is_null($method)){
            $auth = generate_oAuth($status, 'GET', $url);
        } else {
            $auth = generate_oAuth($status, $method, $url);
        }
    }

    $status = rawurlencode($status);

    $api_url = is_null($url) ? "https://api.twitter.com/1.1/statuses/update.json?status={$status}" : $url;

    $api_method = is_null($method) ? 'POST' : $method;

    if($debug){
        echo 'Status: ' . $status . "\n\n";
        echo 'Method: ' . $api_method . "\n\n";
        echo 'Url: ' . $api_url . "\n\n";
        echo 'Auth: ' . $auth . "\n\n";
    }
    
    
    $curl = curl_init($api_url);  
    $header = ["Authorization: {$auth}"];
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10); 
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $api_method);
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
    // if($api_method == 'POST'){
    //     curl_setopt($curl, CURLOPT_POSTFIELDS, ['status'=>$status]);
    // }
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    $resp = curl_exec($curl);
    $respCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $error = curl_error($curl); 
    curl_close($curl);
    
    $json = json_decode($resp, true);

    return ['code'=>$respCode, 'json'=>$json, 'resp'=>$resp, 'error'=>$error];       
}



?>