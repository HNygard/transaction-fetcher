<?php

print_r(getUrl('http://sbanken-mock:8000/'));
print_r(getUrl('http://push-msg:8000/'));

while (true) {
    echo 'hei!' . chr(10);
    sleep(5);
}



function getUrl($url, $usepost = false, $data = array()) {
    global $apiKey;
    $followredirect = false;

    echo '---------------------------------------------' . chr(10);

    $time = strval(round(microtime(true) * 10,0));
    $headers = array(
        'Content-Type: application/json',
        'bfx-nonce: ' . $time,
        'bfx-apikey: ' . $apiKey->key,
    );

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_USERAGENT, 'downloader');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    if ($followredirect) {
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    }
    if ($usepost) {
        echo '   POST ' . $url . chr(10);
        //$post_data = http_build_query($req, '', '&');
        curl_setopt($ch, CURLOPT_POST, true);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    }
    else {
        echo '   GET ' . $url . chr(10);
    }
    if (count($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    $res = curl_exec($ch);

    if ($res === false) {
        throw new Exception(curl_error($ch), curl_errno($ch));
    }

    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($res, 0, $header_size);
    $body = substr($res, $header_size);

    echo '   Response size: ' . strlen($body) . chr(10);

    curl_close($ch);
    return array('headers' => $header, 'body' => $body);
}