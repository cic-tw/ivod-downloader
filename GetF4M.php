<?php
require __DIR__ . '/vendor/autoload.php';
use Goutte\Client;
date_default_timezone_set('Asia/Taipei');
var_dump($argv);
$url = $argv[2];
// $url = 'http://ivod.ly.gov.tw/Play/VOD/86271/1M/';
// $url = 'http://ivod.ly.gov.tw/Play/Full/9572/1M/';
$url = preg_replace("/\/300[Kk]/", '/1M', $url);

if (!$url || !strpos($url, 'ttp://ivod.ly.gov.tw/Play')){
  exit(1);
}

$header = get_headers($url);
if (!strpos($header[0],"200")){
  exit(2);
}

$client = new Client();

$crawler = $client->request('GET', $url);
$info = array();
$legislator = null;
$committee = null;
$time = null;
$filename = null;

$crawler->filter('.video-text > p')->each(function ($node) {
  $text = $node->text();
  if (preg_match("#會議時間：(.*\ .*)#m", $text, $matches)) {
    global $time;
    $time = $matches[1];
    $time = str_replace(' ', '_', $time);
  }

  if (preg_match("#委員名稱：(.*)#m", $text, $matches)) {
    global $legislator;
    $legislator = $matches[1];
    $info['legislator'] = $legislator;
  }
});

$crawler->filter('.video-text > h4')->each(function ($node) {
  $text = $node->text();
  if (preg_match("#主辦單位\ ：(.*)#m", $text, $matches)) {
    global $committee;
    $committee = $matches[1];
    $info['committee'] = $committee;
  }
});

if ($legislator){
  $filename = $time . '_' . $committee . '_' . $legislator;
} else {
  $filename = $time . '_' . $committee;
}

$crawler->filter('.video > script')->each(function ($node) {
  $script_text = $node->text();
    if (preg_match("#readyPlayer\(\'http\:\/\/ivod\.ly\.gov\.tw\/public\/scripts\/\'\,\'(.*\.mp4\/manifest\.f4m)\'\)\;#m", $script_text, $matches)){
      global $filename;
      $cmd = "php AdobeHDS.php --quality high --delete --manifest " . $matches[1] . " --outdir ~/Downloads --outfile " . $filename;
      echo $cmd;
      system($cmd);
    }
});
echo 'QUITAPP\n';

?>
