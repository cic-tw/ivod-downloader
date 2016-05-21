#!/usr/bin/env php

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

$application = new Application([
    'title' => 'IVOD 下載器',
    'left' => 50,
    'top' => 50,
    'width' => 480,
    'height' => 360,
    'icon' => realpath(__DIR__) . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'icon.png'
]);


function getIvod($url) {
  $url = preg_replace("/\/300[Kk]/", '/1M', $url);

  if (!$url || strpos($url, 'http://ivod.ly.gov.tw/Play') != 0){
    echo "no ivod url\n";
    return Array(false, '沒有輸入ivod網址');
  }

  $header = get_headers($url);
  if (!strpos($header[0],"200")){
    echo "URL error\n";
    return Array(false, '網址錯誤');
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

  return Array(true, '');
}


$application->on('start', function() use ($application) {
    $header = new Label([
        'text' => "IVOD 下載器",
        'fontSize' => 40,
        'top' => 50,
        'left' => 150,
    ]);
    $label = new Label([
        'text' => "請輸入網址，範例：\nhttp://ivod.ly.gov.tw/Play/VOD/81330/1M/\nhttp://ivod.ly.gov.tw/Play/Full/9572/1M/\n影片會下載至「下載」資料夾。",
        'fontSize' => 14,
        'top' => 250,
        'left' => 40,
    ]);
    $errorMsg = new Label([
        'text' => '',
        'fontSize' => 14,
        'fontColor' => '#ff2500',
        'top' => 320,
        'left' => 40
      ]);
    $inputText = new InputText([
        'width' => 400,
        'height' => 20,
        'left' => 40,
        'top' => 150,
        'fontSize' => 14
      ]);

    $button = new Button([
        'width' => 160,
        'height' => 20,
        'left' => 170,
        'top' => 200,
        'backgroundColor' => '#ff2500',
        'fontColor' => '#ffffff',
        'value' => '馬上下載',
        'fontSize' => 14
      ]);

    $button->on('click', function() use ($inputText, $errorMsg) {
        $errorMsg->setValue('');
        $value = $inputText->getValue();
        $result = getIvod($value);
        if(!$result[0]) {
           $errorMsg->setText($result[1]);
        }
    });
});

$application->run();