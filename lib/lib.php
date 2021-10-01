<?PHP
require_once('simple_html_dom.php');
require_once('creds.php');
require_once('dr34m_tw33t3r-crawler.php');

use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlObservers\NetCrawlObserver;

//  "constants"
define("SENTENCE_REGEX",  '/(?<!Mr.|Mrs.|Dr.|U.S.|U.S.A.|L.A.|l.a.)(?<=[.?!;:])\s{1,2}/');
define("SPACE",           " ");
define("POS_ARR",         ['art', 'adj', 'adv', 'noun', 'prep', 'verb']);
$crawl_results;


##  Load vocab files
$dir = opendir('random-words');
while(false !== $entry = readdir($dir)){
    if($entry != '.' && $entry != '..'){
        $fileName = "random-words/{$entry}";
        $file = fopen($fileName, 'r');
        switch($entry){
            case 'adjectives.txt':
                $text = fread($file, filesize($fileName));
                $words = explode("\r\n", $text);
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
            case 'articles.txt':
                $text = fread($file, filesize($fileName));
                $words = explode("\r\n", $text);
                $random_lists['articles'] = $words;
                fclose($file);
                break;
            case 'questions.txt':
                $text = fread($file, filesize($fileName));
                $words = explode("\r\n", $text);
                $random_lists['question'] = $words;
                fclose($file);
        }
    }
}


//  utility functions for getting random parts of speech
function art(){
    global $random_lists;
    $arts =  $random_lists['articles'];
    return $arts[array_rand($arts)];
}


function adj(){
    global $random_lists;
    $adjs =  $random_lists['adjectives'];
    return $adjs[array_rand($adjs)];
}


function adv(){
    global $random_lists;
    $advs =  $random_lists['adverbs'];
    return $advs[array_rand($advs)];
}


function noun(){
    global $random_lists;
    $nouns =  $random_lists['nouns'];
    return $nouns[array_rand($nouns)];
}


function prep(){
    global $random_lists;
    $preps =  $random_lists['prepositions'];
    return  $preps[array_rand($preps)];
}


function verb(){
    global $random_lists;
    $verbs =  $random_lists['verbs'];
    return $verbs[array_rand($verbs)];
}

function question(){
    global $random_lists;
    $questions =  $random_lists['questions'];
    return $questions[array_rand($questions)];
}



//  Build a random sentence
function build_sentence($seed = null, $question = false){
    static $random_sentence;
    static $num_words;
    static $word_count;

    //  if it's the first time through, pick a seed.
    if(is_null($seed)){
        if($question){
            $random_sentence .= question() . SPACE;
        }
        $num_words = rand(1, 13);
        $word_count = 1;
        $random_sentence = '';
        //  generate first word, do-while to make sure we don't get a single article
        do {
            $primer = POS_ARR[array_rand(POS_ARR)];
        } while ($num_words == 1 && $primer == 'art');

        build_sentence($primer);
    } else { 
        //  if it's not, then we build a sentence and return it
        if($word_count == $num_words){
            if($seed == 'noun'){
                $random_sentence .= SPACE . $seed();
                return $random_sentence;
            } else {
                $num_words++;
            }
        }
        $word_count++;
        switch($seed){
            case 'art':
                $choice = ['noun', 'adj'][rand(0, 1)];
                $random_sentence .= SPACE . $seed();
                build_sentence($choice);
                break;
            case 'adj':
                $random_sentence .= SPACE . $seed();
                build_sentence('noun');
                break;
            case 'adv':
                $random_sentence .= SPACE . $seed();
                build_sentence('verb');
                break;
            case 'noun':
                $choice = ['adv', 'verb'][rand(0,1)];
                $random_sentence .= SPACE . $seed();
                build_sentence($choice);
                break;
            case 'verb':
                $choice = ['prep', 'art'][rand(0,1)];
                $random_sentence .= SPACE . $seed();
                build_sentence($choice);
                break;
            case 'prep':
                $random_sentence .= SPACE . $seed();
                build_sentence('art');
                break;
        }

    }

    return $random_sentence;   
}


//  Do a google search for some text, return an array of links of the results
//  or false is we didn't get a good resp code.
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
        if($debug) echo "Got {$respCode} from google search =/.\n\n"; 
        return false;
    }
}


//  Build a sentence, do a search, pick a result, try up to 10 times recursively.
function get_random_link($sentence){
    $search_results = search($sentence);
    if($search_results){
        for($i = count($search_results) - 1; $i >=0; $i--){
            if(preg_match('/\.(pdf|jpg|jpeg|gif|doc)$/', $search_results)){
                array_splice($search_results, array_search($random_link), 1);
            }
        }
        //  if we still have results left, pick a random one.
        if(count($search_results)) return $search_results[array_rand($search_results)];
        else return false;
    } else return false;   
}


function crawl($url){
    $netCrawlObserver = new NetCrawlObserver();    
    //  Crawl the site, build a list of all URLs.
    return Crawler::create()
    ->setCrawlObserver($netCrawlObserver)
    ->setTotalCrawlLimit(100)
    ->startCrawling($url);
}


//  Pick two random sentences from a URL
function pick_random_sentences($link){
    global $debug;

    //  It's more reliable to get the html ourselves.
    $curl = curl_init($link);  
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10); 
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);

        $resp = curl_exec($curl);
        $respCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl); 
    curl_close($curl);

    if($respCode == 200){
        $html = str_get_html($resp);
        if(!$html){
            $html_bool = $html ? 'true' : 'false';
            echo "\$html evaluated to a bool ({$html_bool})\n";
            return false;
        } 
    } else {
        echo "Bad response ({$respCode}) from random link ({$link}).";
        return false;
    }
    
    //  we have some html, now get some sentences.
    $page = $html->find('p');

    $sentence_arr = [];
    foreach($page as $p){
        //  format each p element.
        $p = $p->plaintext;
        $p = html_entity_decode($p);
        $p = trim($p);
        $p = preg_replace('/\s+/', ' ', $p);
        $p = preg_replace('/(\n|\r|\r\n)/', '  ', $p);
        //  Totally had to google this, but hey now I know more about positive lookbehinds!
        $p_arr = preg_split(SENTENCE_REGEX, $p, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        foreach($p_arr as $sentence){
            //  can't hurt to double check each sentence I guess whatever.
            $sentence = trim($sentence);
            $sentence = preg_replace('/\s+/', ' ', $sentence);
            $sentence = preg_replace('/(\n|\r|\r\n)/', '  ', $sentence);
            array_push($sentence_arr, $sentence);
        }
    }
    if(count($sentence_arr)){
        //  Pick two random sentences, remove the first one so we don't get doubles.
        $rand_indx1 = array_rand($sentence_arr);
        $sentence1 = $sentence_arr[$rand_indx1];
        array_splice($sentence_arr, $rand_indx1, 1);
        $rand_indx2 = array_rand($sentence_arr);
        $sentence2 = $sentence_arr[$rand_indx2];
        if($debug) echo "\nSentence 1: {$sentence1} \n";
        if($debug) echo "\nSentence 2: {$sentence2} \n\n";
        $sentence = $sentence1 . ' ' . $sentence2;
        return $sentence;
    } else {
        return false;
    }
}



function get_ai_text($text){
    global $debug;
    global $ai_api;
    $headers = [$ai_api];
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
        $json = json_decode($resp, true);
        $ai_text = str_replace($text, '', $json['output']);
        return $ai_text;
    } else {
        if($debug) echo "\nGot a bad response from the DeepAI API: {$respCode}\n\n";
        return false;
    }
}


function format_paragraph($paragraph){
    global $debug;
    $sentences = preg_split(SENTENCE_REGEX, $paragraph, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    $char_count = 0;
    $tweet = '';

    //  loop backwards first and remove everything that's too big to use anyway.
    for($i = count($sentences) -1; $i >= 0; $i--){
        $len = strlen($sentence[$i]);
        if($len > 280){
            array_splice($sentences, $i, 1);
            continue;
        }
    }

    //  loop forwards now because we want to make sense, sentences need
    //  to come in chronological order.
    foreach($sentences as $sentence){
        if($char_count + $len <= 280){
            $sentence = trim($sentence);
            $char_count === 0 ? $tweet .= $sentence : $tweet .= "  " . $sentence;
            $char_count += $len;
        } else break;
    }

    //  replace any new lines with two spaces
    $tweet = preg_replace('/(\n|\r|\r\n)/', '  ', $tweet);
    if(preg_replace('/\s+/', '', $tweet) == '') return false;
    else return $tweet;
}


function send_mail($tweet){
    global $smtpUser;
    global $smtpPassword;
    global $secure_password;
    global $email;
    $sendmail_path = ini_get('sendmail_path');

    // Create the Transport
    $transport = (new Swift_SmtpTransport('smtp.gmail.com', 465, 'ssl'))
    ->setUsername($smtpUser)
    ->setPassword($smtpPassword);

    // Create the Mailer using your created Transport
    $mailer = new Swift_Mailer($transport);

    $params = http_build_query(['tweet'=>$tweet, 'sec'=>password_hash($secure_password, PASSWORD_DEFAULT)]);

    $body = "
    {$tweet}

    <a href='https://dev.01014.org/dr34m_tw33t3r/send_tweet.php?{$params}'>Approve</a>
    ";

    // Create a message
    $message = (new Swift_Message('New tweet'))
    ->setFrom(['server@01014.org' => 'Dream Tweeter'])
    ->setTo([$email])
    ->setBody($body, 'text/html');

    // Send the message
    $result = $mailer->send($message);
    return $result;
}

?>