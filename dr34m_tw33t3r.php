<?PHP
require_once('vendor/autoload.php');
require_once('lib/lib.php');

$debug = true;
//  Could be a const?
$limit = 0;


// //  Generate a random word.
// function main(bool $sleep = false){
//     global $debug;
//     if($sleep){
//         //  Random time between 2m and 4h
//         $seconds = rand(120, 14400);
//         //  Let's figure out when that will be.
//         $datetime = date_create()->add(new DateInterval("PT{$seconds}S"))->format('m/d/y G:i:s');
//         echo "\n\nGoing to do the next tweet at: {$datetime}\n\n";
//         sleep($seconds);
//     } 

//     if($debug) echo "Building search query...\n";
//     $sentence = build_sentence();
//     if($debug) echo "Getting results for: {$sentence}\n";
//     $random_link = get_random_link($sentence);
//     if($random_link != false){
//         if($debug) echo "Crawling {$random_link}\n";
//         crawl($random_link);
//     } else {
//         "\n\nwelp, guess not, sorry.\n";
//     }
// }

// main();


function main(bool $sleep = false){
    global $limit;
    global $debug;

    if($sleep){
        //  Random time between 2m and 4h
        $seconds = rand(120, 14400);
        //  Let's figure out when that will be.
        $datetime = date_create()->add(new DateInterval("PT{$seconds}S"))->format('m/d/y G:i:s');
        echo "\n\nGoing to do the next tweet at: {$datetime}\n\n";
        sleep($seconds);
    } 
    

}

main();

?>