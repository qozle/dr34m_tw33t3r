<?PHP
namespace Spatie\Crawler\CrawlObservers;

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use \crawl_results;

class NetCrawlObserver extends CrawlObserver {
    function __construct(){
        $this->internal_urls = [];
    }


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


    public function finishedCrawling() {
        global $debug;
        global $limit;
        global $crawl_results;

        $crawl_results = $this->internal_urls; 
        
    }
}
?>