<?PHP
require_once('simple_html_dom.php');
require_once('creds.php');
require_once('dr34m_tw33t3r-crawler.php');

use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlObservers\NetCrawlObserver;

$split_regex = '/(?<!Mr.|Mrs.|Dr.|U.S.|U.S.A.|L.A.|l.a.)(?<=[.?!;:])\s+/';

function load_vocab(){
    //  Read each of the parts of speech files, make an array of each.
    $random_lists = [];
    $random_lists['articles'] = ['the', 'a'];
    $dir = opendir('random-words');
    while(false !== $entry = readdir($dir)){
        if($entry != '.' && $entry != '..'){
            $fileName = "random-words/{$entry}";
            $file = fopen($fileName, 'r');
            switch($entry){
                case 'adjectives.txt':
                    $text = fread($file, filesize($fileName));
                    $words = explode("\r\n", $text);
                    $random_word = $words[array_rand($words)];
                    $random_lists['adjectives'] = $words;
                    fclose($file);
                    break;
                case 'adverbs.txt':
                    $text = fread($file, filesize($fileName));
                    $words = explode("\r\n", $text);
                    $random_lists['adverbs'] = $words;
                    fclose($file);
                    break;
                case 'nouns.txt':
                    $text = fread($file, filesize($fileName));
                    $words = explode("\r\n", $text);
                    $random_lists['nouns'] = $words;
                    fclose($file);
                    break;
                case 'prepositions.txt':
                    $text = fread($file, filesize($fileName));
                    $words = explode("\r\n", $text);
                    $random_lists['prepositions'] = $words;
                    fclose($file);
                    break;
                case 'verbs.txt':
                    $text = fread($file, filesize($fileName));
                    $words = explode("\r\n", $text);
                    $random_lists['verbs'] = $words;
                    fclose($file);
                    break;
            }
        }
    }
    return $random_lists;
}

$random_lists = load_vocab();

//  functions for getting random parts of speech
function art($int = 1){
    global $random_lists;
    $art_array = [];
    $arts = $random_lists['articles'];
    for($i = 0; $i < $int; $i++){
        array_push($art_array, $arts[array_rand($arts)]);
    }
    return $art_array;
}


function adj($int = 1){
    global $random_lists;
    $adj_array = [];
    $adjs = $random_lists['adjectives'];
    for($i = 0; $i < $int; $i++){
        array_push($adj_array, $adjs[array_rand($adjs)]);
    }
    return $adj_array;
}


function adv($int = 1){
    global $random_lists;
    $adv_array = [];
    $advs = $random_lists['adverbs'];
    for($i = 0; $i < $int; $i++){
        array_push($adv_array, $advs[array_rand($advs)]);
    }
    return $adv_array;
}


function noun($int = 1){
    global $random_lists;
    $noun_array = [];
    $nouns = $random_lists['nouns'];
    for($i = 0; $i < $int; $i++){
        array_push($noun_array, $nouns[array_rand($nouns)]);
    }
    return $noun_array;
}


function prep($int = 1){
    global $random_lists;
    $prep_array = [];
    $preps = $random_lists['prepositions'];
    for($i = 0; $i < $int; $i++){
        array_push($prep_array, $preps[array_rand($preps)]);
    }
    return $prep_array;
}


function verb($int = 1){
    global $random_lists;
    $verb_array = [];
    $verbs = $random_lists['verbs'];
    for($i = 0; $i < $int; $i++){
        array_push($verb_array, $verbs[array_rand($verbs)]);
    }
    return $verb_array;
}



//  build a random sentence
function build_sentence(){
    $rand_case = rand(0, 5);
    $random_sentence;
    switch($rand_case){
        case 0:
            $arts = art(2);
            $adjs = adj(2);
            $nouns = noun(2);
            $verbs = verb(1);
            $adv = adv(1);
            $random_sentence = "{$arts[0]} {$adjs[0]} {$nouns[0]} {$adv[0]} {$verbs[0]} {$arts[1]} {$adjs[1]} {$nouns[1]}";
            break;        
        case 1:
            $random_sentence = noun()[0];
            break;        
        case 2:
            $random_sentence = adj()[0] . " " . noun()[0] . " .";
            break;            
        case 3:
            $random_sentence = verb()[0] . " " . adv()[0];
            break;
        case 4:
            $random_sentence = art()[0] . " " . adj()[0] . " " . noun()[0] . " " . verb()[0];
            break;
        case 5:
            $random_sentence = adv()[0] . ' ' . verb()[0];
            break;
    }
    return $random_sentence;
}



function crawl($url){
    //  Pick a random IP address, see if we get data from it.  
    $netCrawlObserver = new NetCrawlObserver();
    
    //  Crawl the site, build a list of all internal URLs, pick a random one.
    Crawler::create()
    ->setCrawlObserver($netCrawlObserver)
    ->setCrawlProfile(new \Spatie\Crawler\CrawlProfiles\CrawlInternalUrls($url))
    ->setTotalCrawlLimit(250)
    ->startCrawling($url);
}


function get_ai_text($text){
    global $debug;
    $text = $text;
    $headers = ['api-key:4fe904a0-f080-4745-814f-5874275dc1d6'];
    $curl = curl_init("https://api.deepai.org/api/text-generator"); 
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, ['text'=>$text]);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3); 
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        $resp = curl_exec($curl);
        $respCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE); 
    curl_close($curl);

    if($respCode == 200){
        return $resp;
    } else {
        if($debug) echo "We got an error when trying to get the AI text: {$respCode}\n";
    }
}


function search($text){
    global $debug;
    global $api_key;
    global $cse_cx;
    $params = http_build_query(['key'=>$api_key, 'cx'=>$cse_cx, 'q'=>$text]);
    $uri = "https://www.googleapis.com/customsearch/v1?" . $params;
    $curl = curl_init($uri); 
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3); 
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        $resp = curl_exec($curl);
        $respCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE); 
        $error = curl_error($curl);
    curl_close($curl);
    
    if($respCode == 200){
        $json = json_decode($resp, true);
        $links = [];
        foreach($json['items'] as $link){
            array_push($links, $link['link']);
        }
        return $links;
    } else {
        if($debug) {
            "Got {$respCode} from google search:\n\n";
            echo var_dump($json);
            return false;
        } 
    }
}


function get_random_link($query){
    $search_results = search($query);
    if($search_results != false){
        $random_link = $search_results[array_rand($search_results)];
        return $random_link;    
    } else {
        return false;
    }
    
}


function pick_random_sentence($link){
    global $debug;
    global $split_regex;
    $html = file_get_html($link);

    $page = $html->find('p');

    $sentence_arr = [];
    foreach($page as $p){
        $p = $p->plaintext;
        $p = html_entity_decode($p);
        $p = preg_replace('/\s+/', ' ', $p);
        $p = trim($p);
        //  Totally had to google this, but hey now I know more about positive lookbehinds!
        //  /(?<!Mr.|Mrs.|Dr.)(?<=[.?!;:])\s+/
        $p_arr = preg_split($split_regex, $p, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        foreach($p_arr as $sentence){
            array_push($sentence_arr, $sentence);
        }
    }
    //  Pick two random sentences, remove the first one so we don't get doubles.
    $rand_indx1 = array_rand($sentence_arr);
    $sentence1 = $sentence_arr[$rand_indx1];
    array_splice($sentence_arr, $rand_indx1, 1);
    $rand_indx2 = array_rand($sentence_arr);
    $sentence2 = $sentence_arr[$rand_indx2];
    if($debug) echo "\nSentence 1: {$sentence1} \n\n";
    if($debug) echo "Sentence 2: {$sentence2} \n\n";
    $sentence = $sentence1 . ' ' . $sentence2;
    return $sentence;
}


function format_paragraph($paragraph){
    global $debug;
    global $split_regex;
    $sentences = preg_split($split_regex, $paragraph, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

    $char_count = 0;
    $tweet = '';
    foreach($sentences as $k=>$sentence){
        $len = strlen($sentence);
        if ($char_count + $len <= 280){
            $char_count .= $len;
            array_key_first($sentences) != $k ? 
                $tweet .= "  " . $sentence : 
                $tweet .= $sentence;
        } else {
            break;
        }
    }
    if($debug) echo "\n\nTweet is: {$tweet}\n\n";
    return $tweet;
}



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