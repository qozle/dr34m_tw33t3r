<?PHP
namespace Spatie\Crawler\CrawlObservers;

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use \debug;
use \init;
use \main;
use \get_ai_text;
use \pick_random_sentence;
use \format_paragraph;
use \generate_nonce;
use \generate_oAuth;
use \twitter_request;



//  This is our class that we'll instantiate that has some callback functions
class NetCrawlObserver extends CrawlObserver {
    // public $internal_urls;

    function __construct(){
        $this->internal_urls = [];
    }


    //  Called when the crawler will crawl the URL (before?).
    public function willCrawl(UriInterface $url):void {
        // echo "About to crawl {$url}...\n";
    }


    //  Called when the url is crawled successfully.
    public function crawled(UriInterface $url, ResponseInterface $response, ?UriInterface $foundOnUrl = null):void {
        // echo "Crawled {$url}...\n";
        array_push($this->internal_urls, $url);
    }

    
    //  Called when the url fails.
    public function crawlFailed(UriInterface $url, RequestException $requestException, ?UriInterface $foundOnUrl = null):void {
        // echo "\nCouldn't crawl {$url}.\n\n";
    }


    //  Called when the crawling has finished.
    public function finishedCrawling():void {
        global $debug;
        try{
            if($debug) echo "Finished crawling the site, these are the internal URLS:\n\n";
            foreach($this->internal_urls as $url){
                if($debug) echo $url . "\n";
            }
            if($debug) echo "\n";
            if(count($this->internal_urls)){
                $random_i = array_rand($this->internal_urls);
                $random_url = $this->internal_urls[$random_i];
                if($debug) echo "We pick {$random_url}\n";
                if($debug) echo "Making a sentence of two randomly selected sentences to feed to the AI.\n";
                $sentence = pick_random_sentence($random_url);
                if($debug) echo "We got:\n";
                if($debug) echo $sentence . "\n";
                if($debug) echo "Feeding sentence to AI...\n";
                $ai_text_raw = json_decode(get_ai_text($sentence), true);
                if($debug) echo "Removing our origial sentence from the result...\n";
                $ai_text = str_replace($sentence, '', $ai_text_raw['output']);
                
                //  Now we have the AI text.  We need to format it so it will fit into a tweet with a 280 character limit.  One strategy for doing this is to break the paragraph up into sentences, get the count of each sentence, and add sentences (in order) until adding the next one would break the limit.
                if($debug) echo $ai_text;
                if($debug) echo "\n";
    
                $tweet = format_paragraph($ai_text);
    
                
                if(preg_match('/(\n|\r|\r\n)/', $tweet)){
                    echo "\nWe found some new line characters, generating new text...\n";
                    main();
                } else {
                    if($debug) echo "\nSending tweet...\n\n";
                    $resp = twitter_request($tweet);
                }
    
    
                if($resp['code'] == 200) echo "\n\nTweet succesful =)\n\n";
                else {
                    echo "There was an issue tweeting:\n\n";
                    echo "Code: " . $resp['code'] . "\n";
                    echo "JSON: \n" . $resp['json'] . "\n\n";
                    echo "Raw response: \n" . $resp['resp'] . "\n";
                }
            } else {
                if($debug) echo "There were no internal URLs, finding another site...\n\n";
                main();
            }

        } finally {
            main();
        }
    }
}
?>