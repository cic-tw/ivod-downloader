<?php
require 'vendor/autoload.php';
use Goutte\Client;
date_default_timezone_set('Asia/Taipei');

use Gui\Application;
use Gui\Components\Button;
use Gui\Components\Label;
use Gui\Components\InputText;

$legislator = null;
$committee = null;
$time = null;
$filename = null;

function getIvod($url) {
  $url = preg_replace("/\/300[Kk]/", '/1M', $url);

  if (!$url || strpos($url, 'http://ivod.ly.gov.tw/Play') != 0){
    echo 'Not Ivod url';
    return false;
  }

  $header = get_headers($url);
  if (!strpos($header[0],"200")){
    echo 'URL error';
    return false;
  }

  $client = new Client();

  $crawler = $client->request('GET', $url);
  $info = array();
  
  $crawler->filter('.video-text > p')->each(function ($node) {
    $text = $node->text();
    if (preg_match("#會議時間：(.*\ .*)#m", $text, $matches)) {
      $GLOBALS['time'] = $matches[1];
      $GLOBALS['time'] = str_replace(' ', '_', $GLOBALS['time']);
    }

    if (preg_match("#委員名稱：(.*)#m", $text, $matches)) {
      $GLOBALS['legislator'] = $matches[1];
    }
  });

  $crawler->filter('.video-text > h4')->each(function ($node) {
    $text = $node->text();
    if (preg_match("#主辦單位\ ：(.*)#m", $text, $matches)) {
      $GLOBALS['committee'] = $matches[1];
    }
  });

  if ($GLOBALS['legislator']){
    $GLOBALS['filename'] = $GLOBALS['time'] . '_' . $GLOBALS['committee'] . '_' . $GLOBALS['legislator'];
  } else {
    $GLOBALS['filename'] = $GLOBALS['time'] . '_' . $GLOBALS['committee'];
  }

  //clear all
  $GLOBALS['time'] = null;
  $GLOBALS['committee'] = null;
  $GLOBALS['legislator'] = null;

  $crawler->filter('.video > script')->each(function ($node) {
    $script_text = $node->text();
      if (preg_match("#readyPlayer\(\'http\:\/\/ivod\.ly\.gov\.tw\/public\/scripts\/\'\,\'(.*\.mp4\/manifest\.f4m)\'\)\;#m", $script_text, $matches)){
        $cmd = "php AdobeHDS.php --quality high --delete --manifest " . $matches[1] . " --outdir ~/Downloads --outfile " . $GLOBALS['filename'];
        echo $cmd;
        system($cmd);
        system('rm *-Frag*');
      }
  });

  $GLOBALS['filename'] = null;
}

$application = new Application();

$application->on('start', function() use ($application) {
    $label = new Label([
        'text' => "Ivod下載器。請輸入網址，範例：\nhttp://ivod.ly.gov.tw/Play/VOD/81330/1M",
        'fontSize' => 10,
        'top' => 10,
        'left' => 10,
    ]);
    $inputText = (new InputText())
        ->setLeft(20)
        ->setTop(60)
        ->setWidth(300);
    $button = (new Button())
        ->setLeft(40)
        ->setTop(100)
        ->setWidth(200)
        ->setValue('下載ivod');

    $button->on('click', function() use ($inputText) {
        $value = $inputText->getValue();
        getIvod($value);
    });
});

$application->run();