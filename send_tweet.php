<?PHP
require('/home/qozle/dream_tweeter/creds.php');
require('/home/qozle/dream_tweeter/lib/tweet_lib.php');
require('/home/qozle/dream_tweeter/vendor/autoload.php');



if(!isset($_GET['tweet']) || !isset($_GET['sec'])){
    echo "Hey I think you forgot something...";
    die();
} else {
    $tweet = $_GET['tweet'];
    $pw = $_GET['sec'];
}


if(password_verify($secure_password, $pw)){
    $resp = twitter_request($tweet);
    if($resp['code'] == 200){
        echo "Tweet posted successfully!";
    } else {
        echo "There was a problem tweeting =/.";
        echo "<br>";
        echo var_dump($json);
    }

} else {
    echo "Whoa now, slow down there buddy...";
}




?>