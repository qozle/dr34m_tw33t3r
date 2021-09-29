<?PHP
require('vendor/autoload.php');
require('lib/lib.php');

$debug = true;


function main(bool $sleep = false){
    global $debug;
    global $crawl_results;

    if($sleep){
        //  Random time between 2m and 4h
        $seconds = rand(120, 14400);
        //  Let's figure out when that will be.
        $datetime = date_create()->add(new DateInterval("PT{$seconds}S"))->format('m/d/y G:i:s');
        echo "\n\nGoing to do the next tweet at: {$datetime}\n\n";
        sleep($seconds);
    } 
    try{
        ##  Build a random sentence from vocab
        $sentence = build_sentence();
        if($debug) echo "SENTENCE: " . $sentence . "\n\n";
        
        ##  Do a google search, pick a random result
        $random_link = get_random_link($sentence);
        if($random_link == false) $random_link = 'false';
        if($debug) echo "RANDOM LINK: " . $random_link . "\n\n";
        
        ##  Crawl the site
        crawl($random_link);
        
        ##  pick a random page from the results
        while(empty($crawl_results) || count($crawl_results) == 1){
            echo "Not enough internal links from crawling {$random_link}\n";
            do{
                $sentence = build_sentence();
                $random_link = get_random_link($sentence);
            } while (empty($random_link) || $random_link == false);
            echo "Crawling a new random link ({$random_link})\n\n";
            crawl($random_link);
        }
        $crawl_result = $crawl_results[array_rand($crawl_results)];
        if($debug) echo "RANDOM PAGE: " . $crawl_result . "\n\n";
        
        ##  Get all the text on the page, break it down to sentences
        ##  (this part needs some love)
        $sentences = pick_random_sentences($crawl_result);
        $tries = 0;
        while($sentences == false){
            $tries++;
            if($tries >= 10){
                $random_link = get_random_link($sentence);
            } 
            else {
                echo "crawl results before splice:\n";
                echo var_dump($crawl_results);
                echo "\n";
                array_splice($crawl_results, array_search($crawl_result, $crawl_results), 1);
            }
            if(empty($crawl_results)){
                echo "crawl results empty for some reason still...?\n";
            }
            $crawl_result = $crawl_results[array_rand($crawl_results)];        
            if($debug) echo "No sentences from the link above got past the filter, trying a new link ({$crawl_result})\n";
            $sentences = pick_random_sentences($crawl_result);
        }
        if($debug) echo "SENTENCES FROM RANDOM PAGE: " . $sentences . "\n\n";
        
        ##  Submit it to the language generator
        $ai_text = get_ai_text($sentences);
        if($debug) echo "AI TEXT RESULT: " .  $ai_text . "\n\n";
        
        ##  Pick as many sentences as will fit in the twitter character limit.
        ##  (why not merge this with the function above?)
        $tweet = format_paragraph($ai_text);
        if($debug) echo "TWEET: {$tweet}\n\n";
        
        
        ##  Send an email confirmation.
        $result = send_mail($tweet);
        if($result){
            echo "It's up to you now...\n\n";
        } else {
            echo "Mail sending failed =/.\n\n";
        }
    
        main(true);

    } catch(exception $e){
        echo $e->getMessage();
        main();
    }
}


main()





?>