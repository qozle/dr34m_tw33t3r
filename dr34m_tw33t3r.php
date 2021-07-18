<?PHP
require_once('vendor/autoload.php');
require_once('lib/lib.php');

$debug = true;

//  Generate a random word.
function main(){
    global $debug;
    //  Random time between 2m and 4h
    $seconds = rand(120, 14400);
    //  Let's figure out when that will be.
    $datetime = date_create()->add(new DateInterval("PT{$seconds}S"))->format('m/d/y G:i:s');
    echo "\n\nGoing to do the next tweet at: {$datetime}\n\n";

    sleep($seconds);
    if($debug) echo "Building search query...\n";
    $sentence = build_sentence();
    if($debug) echo "Getting results for: {$sentence}\n";
    $random_link = get_random_link($sentence);
    if($random_link != false){
        if($debug) echo "Crawling {$random_link}\n";
        crawl($random_link);
    } else {
        "\n\nwelp, guess not, sorry.\n";
    }
}

main();


//  Loop as long as the script is running, pretty sure this is bad practice,
//  but the server this is running on restarts daily so.
// function init(){
//     while(1){
//         $seconds = rand(120, 14400);
//         $minutes = round($seconds / 60, 2);
//         $hours = round($minutes / 60, 2);
//         echo "\n\nGonna tweet in {$seconds} seconds, or about {$minutes} minutes, or about {$hours} hours...\n\n";
//         sleep($seconds);
//         main();
//     }
// }

// init();



?>