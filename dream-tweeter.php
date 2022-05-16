<?PHP
require('vendor/autoload.php');
require('lib/lib.php');

$debug = true;
$run = true;
$sleep = false;
$crawl_results;

do{
    main();

    //  sleep
    if($sleep){
        $seconds = rand(120, 14400);
        $datetime = date_create()->add(new DateInterval("PT{$seconds}S"))->format('m/d/y G:i:s');
        echo "\n\nGoing to do the next tweet at: {$datetime}\n\n";
        sleep($seconds);
    }
}
while($run);


function main(){
    global $debug;
    global $crawl_results;
    global $run;
    global $sleep;
    $sleep = false;

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
        if($debug) echo "crawling...\n\n";
        crawl($random_link);
        if($debug) echo "finished crawling...\n\n";
        if($debug) echo count($crawl_results) . "\n\n";
        //  if crawl results is empty, fuck it, start over.
        if(!count($crawl_results)){
            echo "no crawl results, trying again...\n\n";
            return;
        } 
        
        ##  pick a random page from the results
        $rand_i = array_rand($crawl_results);
        $crawl_result = $crawl_results[$rand_i];
        if($debug) echo "RANDOM PAGE: " . $crawl_result . "\n\n";
        
        ##  Get all the text on the page, break it down to sentences
        //  try until it works for as many results as we got, or start over.
        for($i = count($crawl_results) - 1; $i >= 0; $i--){
            $sentences = pick_random_sentences($crawl_result);
            if($sentences == false){
                array_splice($crawl_results, $rand_i, 1);
                if(count($crawl_results)){
                    $rand_i = array_rand($crawl_results);
                    $crawl_result = $crawl_results[$rand_i];
                }
                continue;
            } else break;
        }

        if($sentences == false){
            echo "couldn't get any text from any of the google results O_O...trying again";
            return;
        } 

        if($debug) echo "SENTENCES FROM RANDOM PAGE: " . $sentences . "\n\n";
        
        ##  Submit it to the language generator
        $ai_text = get_ai_text($sentences);
        if($debug) echo "AI TEXT RESULT: " .  $ai_text . "\n\n";
        
        ##  Pick as many sentences as will fit in the twitter character limit.
        $tweet = format_paragraph($ai_text);
        if(!$tweet){
            echo "Empty tweet, trying again...";
            return;
        }

        if($debug) echo "TWEET: {$tweet}\n\n";        
        
        ##  Send an email confirmation.
        $result = send_mail($tweet);
        if($result){
            echo "It's up to you now...\n\n";
            $sleep = true;
        } else {
            echo "Mail sending failed =/.\n\n";
            return;
        }
    } catch(Throwable $e){
        echo $e->getMessage() . "in line " . $e->getLine() . " in file " . $e->getFile() .  "\n\n";
        $e->getTrace();
        echo "There was an error, trying again...\n\n";
        return;
    }
}







?>