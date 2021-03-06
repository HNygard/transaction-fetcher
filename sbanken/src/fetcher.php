<?php
require __DIR__ . '/vendor/autoload.php';

$i = 0;
function logInfo($string) {
    global $i, $account_number;
    echo date('Y-m-d H:i:s')
        . ' -- ' . $i
        . (!empty($account_number) ? ' -- ' . $account_number : '')
        . ' -- ' . $string . chr(10);
}

function str_replace_with_x($string) {
    $str = '';
    for($i = 0; $i < strlen($string); $i++) {
        $str .= 'x';
    }
    return $str;
}

class Config {
    var $clientId;
    var $clientSecret;
    var $customerId;
    var $accountNumbers;
    var $serverApi;
    var $pushMessageService;
    var $fetchIntervalInSeconds;
}

$configFile = 'config.json';
if (!file_exists(__DIR__ . '/' . $configFile)) {
    echo '!!! MISSING config.json !!!' . chr(10) . chr(10);
    echo 'Example - Production:' . chr(10);
    echo file_get_contents(__DIR__ . '/config.prod.json') . chr(10) . chr(10);
    echo 'Example - Mock:' . chr(10);
    echo file_get_contents(__DIR__ . '/config.mock.json') . chr(10) . chr(10);
    echo 'Starting with \'config.mock.json\' since no \'config.json\' was provided.' . chr(10) . chr(10);
    $configFile = 'config.mock.json';
}

/* @var Config $config */
$config = json_decode(file_get_contents(__DIR__ . '/' . $configFile));

logInfo('------ SBANKEN FETCHER ------');
logInfo('clientId ........ : ' . str_replace_with_x($config->clientId));
logInfo('customerId ...... : ' . str_replace_with_x($config->customerId));
logInfo('accountNumbers .. : ' . implode(', ', $config->accountNumbers));
logInfo('serverApi ....... : ' . $config->serverApi);
logInfo('----------------------------------------------------------------' . chr(10) . chr(10));

$credentials = new \Pkj\Sbanken\Credentials(
    $config->clientId,
    $config->clientSecret,
    $config->customerId
);

$client = \Pkj\Sbanken\Client::factory($credentials);
$client->setApiServer($config->serverApi);


function getTransactions($accountNumber) {
    global $client;
    $transactionRequest = new \Pkj\Sbanken\Request\TransactionV2ListRequest($accountNumber);
    $transactionRequest->setStartDate(new \DateTime('-10 day'));

    $transactions = $client->TransactionsV2()->getList($transactionRequest);

    foreach ($transactions as $transaction) {
        logInfo($transaction->accountingDate->format('Y-m-d') . ' : '
            . str_pad($transaction->amount, 10, ' ', STR_PAD_LEFT) . ' NOK'
            . '  ----  Transaction ID: ' . str_pad($transaction->transactionId, 10, ' ', STR_PAD_LEFT)
            . '  ----  ' . $transaction->text
        );

        $transaction_folder = '/data/' . $accountNumber . '/' . $transaction->accountingDate->format('Y-m');
        if (!file_exists($transaction_folder)) {
            // Recursive create the folder
            mkdir($transaction_folder, 0777, true);
        }

        // :: Prep for saving
        $transaction_obj = new stdClass();
        $transaction_obj->transactionId = $transaction->transactionId;
        $transaction_obj->accountingDate = $transaction->accountingDate->format('c');
        $transaction_obj->interestDate = $transaction->interestDate->format('c');
        $transaction_obj->otherAccountNumber = $transaction->otherAccountNumber;
        $transaction_obj->otherAccountNumberSpecified = $transaction->otherAccountNumberSpecified;
        $transaction_obj->amount = $transaction->amount;
        $transaction_obj->text = $transaction->text;
        $transaction_obj->transactionType = $transaction->transactionType;
        $transaction_obj->transactionTypeCode = $transaction->transactionTypeCode;
        $transaction_obj->transactionTypeText = $transaction->transactionTypeText;
        $transaction_obj->isReservation = $transaction->isReservation;
        $transaction_obj->reservationType = $transaction->reservationType;
        $transaction_obj->source = $transaction->source;
        $transaction_obj->cardDetailsSpecified = $transaction->cardDetailsSpecified;
        if ($transaction->cardDetailsSpecified) {
            $cardDetails = new stdClass();
            $cardDetails->cardNumber = $transaction->cardDetails->cardNumber;
            $cardDetails->currencyAmount = $transaction->cardDetails->currencyAmount;
            $cardDetails->currencyRate = $transaction->cardDetails->currencyRate;
            $cardDetails->merchantCategoryCode = $transaction->cardDetails->merchantCategoryCode;
            $cardDetails->merchantCategoryDescription = $transaction->cardDetails->merchantCategoryDescription;
            $cardDetails->merchantCity = $transaction->cardDetails->merchantCity;
            $cardDetails->merchantName = $transaction->cardDetails->merchantName;
            $cardDetails->originalCurrencyCode = $transaction->cardDetails->originalCurrencyCode;
            $cardDetails->purchaseDate = $transaction->cardDetails->purchaseDate->format('c');
            $cardDetails->transactionId = $transaction->cardDetails->transactionId;
            $transaction_obj->cardDetails = $cardDetails;
        }

        $transaction_file = $transaction_folder
            . '/' . $transaction->accountingDate->format('Y-m-d')
            . ' - ' . $transaction->transactionId
            . ' - ' . $transaction->amount . ' kr'
            . '.json';
        if (!file_exists($transaction_file)) {
            // -> New transaction
            $obj = new stdClass();
            $obj->lastUpdate = date('c');
            $obj->lastVersion = $transaction_obj;
            $obj->oldVersions = new stdClass();
            file_put_contents($transaction_file, json_encode($obj, JSON_PRETTY_PRINT));
            notifyNewTransaction($transaction_obj);
        }
        else {
            $obj = json_decode(file_get_contents($transaction_file));
            if (json_encode($obj->lastVersion) != json_encode($transaction_obj)) {
                // -> Modified transaction
                $transaction_old = $obj->lastVersion;
                $obj->oldVersions->{$obj->lastUpdate} = $transaction_old;
                $obj->lastUpdate = date('c');
                $obj->lastVersion = $transaction_obj;
                file_put_contents($transaction_file, json_encode($obj, JSON_PRETTY_PRINT));
                notifyModifiedTransaction($transaction_obj, $transaction_old);
            }
        }
    }
}

//print_r(getUrl('http://sbanken-mock:8000/'));
//print_r(getUrl('http://push-msg:8000/'));

while (true) {
    foreach ($config->accountNumbers as $account_number) {
        try {
            if ($i == 0) {
                $client->authorize();
            }

            $i++;
            // TODO: expand API to check if we need to reauthorize
            getTransactions($account_number);
        }
        catch (Exception $e) {
            logInfo('!!!!!!!!!!! EXCEPTION WHILE GETTING ACCOUNT FOR [' . $account_number . '] !!!!!!!!!!!');
            logInfo($e->getMessage());
            echo $e->getTraceAsString() . chr(10) . chr(10);

            // TODO: remove when reauthorize is implemented
            if ($e->getMessage() == 'Api credentials has expired. Please try again.') {
                logInfo('Sbanken API token expired. Reauthorizing...');
                $client->authorize();
            }
        }
        sleep($config->fetchIntervalInSeconds);
    }
}

function notifyNewTransaction($transaction) {
    global $config;
    $msg = new stdClass();
    $msg->title = 'New transaction - ' . $transaction->amount . ' kr, ' . $transaction->text;
    $msg->content = json_encode($transaction, JSON_PRETTY_PRINT);
    getUrl($config->pushMessageService, true, $msg);
}

function notifyModifiedTransaction($transaction, $old_transaction) {
    global $config;
    $modified = array();
    foreach ($transaction as $field => $value) {
        if (is_object($value)) {
            if (!isset($old_transaction->$field)) {
                $modified[$field] = new stdClass();
                $modified[$field]->old = null;
                $modified[$field]->new = $value;
            }
            else {
                // -> New and old value is present and is object
                foreach ($value as $field2 => $value2) {
                    if ($value2 != $old_transaction->$field->$field2) {
                        $modified[$field . '_' . $field2] = new stdClass();
                        $modified[$field . '_' . $field2]->old = $old_transaction->$field->$field2;
                        $modified[$field . '_' . $field2]->new = $value2;
                    }
                }
            }
        }
        else {
            if ($value != $old_transaction->$field) {
                $modified[$field] = new stdClass();
                $modified[$field]->old = $old_transaction->$field;
                $modified[$field]->new = $value;
            }
        }
    }

    $msg = new stdClass();
    $msg->title = 'Modified transaction - ' . $transaction->amount . ' kr, ' . $transaction->text;
    $msg->content = json_encode($modified, JSON_PRETTY_PRINT);
    getUrl($config->pushMessageService, true, $msg);
}

function getUrl($url, $usepost = false, $post_data = array()) {
    $followredirect = false;

    logInfo('---------------------------------------------');

    $post_data_string = json_encode($post_data);
    $headers = array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($post_data_string)
    );

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Sbanken-Fetcher');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    if ($followredirect) {
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    }
    if ($usepost) {
        logInfo('   POST ' . $url);
        //$post_data = http_build_query($req, '', '&');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data_string);
    }
    else {
        logInfo('   GET ' . $url);
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

    logInfo('   Response size: ' . strlen($body));

    curl_close($ch);
    return array('headers' => $header, 'body' => $body);
}