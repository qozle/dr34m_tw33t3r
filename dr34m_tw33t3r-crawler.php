<?PHP
namespace Spatie\Crawler\CrawlObservers;

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;


//  This is our class that we'll instantiate that has some callback functions
class NetCrawlObserver extends CrawlObserver {
    // public $internal_urls;

    function __construct(){
        $this->internal_urls = [];
        $this->select_limit = 0;
    }


    public function select_internal_url(){
        global $debug;
        $random_i = array_rand($this->internal_urls);
        $random_url = $this->internal_urls[$random_i];
        if(preg_match('/\.(pdf|jpg|jpeg|gif|doc)$/', $random_url) && $this->select_limit < 25){
            $this->select_limit++;
            array_splice($this->internal_urls, $random_i, 1);
            return $this->select_internal_url();
        } else {
            return $random_url;
        }
      
    }


    //  Called when the crawler will crawl the URL (before?).
    public function willCrawl(UriInterface $url):void {
        // echo "About to crawl {$url}...\n\n";
    }


    //  Called when the url is crawled successfully.
    public function crawled(UriInterface $url, ResponseInterface $response, ?UriInterface $foundOnUrl = null):void {
        // echo "Crawled {$url}...\n\n";
        array_push($this->internal_urls, $url);
    }

    
    //  Called when the url fails.
    public function crawlFailed(UriInterface $url, RequestException $requestException, ?UriInterface $foundOnUrl = null):void {
        // echo "\nCouldn't crawl {$url}.\n\n";
    }


    //  Called when the crawling has finished.
    public function finishedCrawling() {
        global $debug;
        global $limit;
        global $crawl_results;
        //  If we finished crawling and we somehow have no internal links from it
        //  then we should just restart the whole process.
        try{

            // if($debug){
            //     echo "Finished crawling the site, these are the internal URLS:\n\n";
            //     foreach($this->internal_urls as $url){
            //         echo $url . "\n";
            //     }
            //     echo "\n\n";
            // } 

            $crawl_results = $this->internal_urls; 

            // if($debug) echo "\n\nMaking a sentence of two randomly selected sentences to feed to the AI.\n\n";
            // $sentence = pick_random_sentences($random_url);
            // if($sentence == false){
            //     return main();
            // }
            // if($debug) echo "We got:\n\n";
            // if($debug) echo $sentence . "\n";
            // if($debug) echo "\nFeeding sentence to AI...\n\n";
            // $ai_text = get_ai_text($sentence);
            
            // //  Now we have the AI text.  We need to format it so it will fit into a tweet with a 280 character limit.  One strategy for doing this is to break the paragraph up into sentences, get the count of each sentence, and add sentences (in order) until adding the next one would break the limit.
            // if($debug) echo $ai_text;
            // if($debug) echo "\n";
            
            // $tweet = format_paragraph($ai_text);
            // if($tweet == ''){
            //     return main();
            // }

            // if($debug) echo "\nSending tweet...\n\n";
            // $resp = twitter_request($tweet);

            // if($resp['code'] == 200){
            //     echo "\n\nTweet succesful =)\n\n";
            //     return main(true);
            // } 
            // else {
            //     echo "There was an issue tweeting:\n\n";
            //     echo "Code: " . $resp['code'] . "\n";
            //     echo "Raw response: \n" . $resp['resp'] . "\n";
            // }
            

        } catch(exception $e){
            echo $e->message;
        }
    }


}
?>