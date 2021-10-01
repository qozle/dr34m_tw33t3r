<?PHP
require('vendor/autoload.php');
require('lib/lib.php');

$debug = true;
$run = true;

do{
    main();

    //  sleep
    $seconds = rand(120, 14400);
    $datetime = date_create()->add(new DateInterval("PT{$seconds}S"))->format('m/d/y G:i:s');
    echo "\n\nGoing to do the next tweet at: {$datetime}\n\n";
    sleep($seconds);
}
while($run);


function main(){
    global $debug;
    global $crawl_results;
    global $run;

    try{
        ##  Build a random sentence from vocab
        $sentence = build_sentence();
        if($debug) echo "SENTENCE: " . $sentence . "\n\n";
        
        ##  Do a google search, pick a random result, try ten times
        //  If we fail ten times, there's probably something wrong with the API.
        for($i = 0; $i < 10; $i++){
            $random_link = get_random_link($sentence);
            if(!$random_link) continue;
            else break;
        }
        if($random_link == false){
            echo "Failed {$i} times to get a random google result.  Time to die.";
            $run = false;
            return;
        } 
        if($debug) echo "RANDOM LINK: " . $random_link . "\n\n";
        
        ##  Crawl the site (results go in $crawl_results)
        crawl($random_link);
        //  if crawl results is empty, fuck it, start over.
        if(!count($crawl_results)) echo "no crawl results, trying again..."; return;
        
        ##  pick a random page from the results
        $rand_i = array_rand($crawl_results);
        $crawl_result = $crawl_results[$rand_i];
        if($debug) echo "RANDOM PAGE: " . $crawl_result . "\n\n";
        
        ##  Get all the text on the page, break it down to sentences
        //  try until it works for as many results as we got, or start over.
        for($i = count($crawl_results) - 1; $i >= 0; $i--){
            if($sentences = pick_random_sentences($crawl_result) == false){
                array_splice($crawl_results, $rand_i, 1);
                $crawl_result = $crawl_results[array_rand($crawl_results)];
                continue;
            } else {
                break;
            }
        }
        if($sentences == false) echo "couldn't get any text from any of the google results O_O...trying again"; return;

        if($debug) echo "SENTENCES FROM RANDOM PAGE: " . $sentences . "\n\n";
        
        ##  Submit it to the language generator
        $ai_text = get_ai_text($sentences);
        if($debug) echo "AI TEXT RESULT: " .  $ai_text . "\n\n";
        
        ##  Pick as many sentences as will fit in the twitter character limit.
        $tweet = format_paragraph($ai_text);
        if(!$tweet) echo "Empty tweet, trying again..."; return;
        if($debug) echo "TWEET: {$tweet}\n\n";
        
        
        ##  Send an email confirmation.
        $result = send_mail($tweet);
        if($result){
            echo "It's up to you now...\n\n";
        } else {
            echo "Mail sending failed =/.\n\n";
            return;
        }
    } catch(exception $e){
        echo $e->getMessage();
        echo "\n\n";
        return;
    }
}







?>