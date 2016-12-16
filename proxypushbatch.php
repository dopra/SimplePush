<?php
/**
 * Example of use SSL communication through http proxy
 */
$apns_settings = array(
        "host" => 'gateway.sandbox.push.apple.com',
        "port" => 2195,
        "certificate" => 'ipad.pem',
        "passphrase" => '',
);

$proxy = 'proxyhttp:8080';

$tokens = array (
'a5a9dd1ab71d55ae9ad94a6a01a79a40e741fab2d94b9da91de25faf897a5b98',
'564a9aa9a6859aabd0e6dadaae67f6787a5787a271e5ea45b281a28019a1e975',
'ad56a12056faa7a00764998a621280ddebd61a72aa6d146aa1aa0740a65f2e96',
'bd1f1aabf5dbabae4461005efdabaa542a7aad9d07f6aaf812978fd550101bbd',
'6da845e7908b85dffd2a2a149109ef58e46bfda2a49002688dba4df9dad58d46',
'88119670f75dfa968bf58e0102a4a4a5b1ddaa6aaaa5da92ada444d5e5f4456b',
'a52710a8194baad298bd697aaaaa6bae67a7ba552baeaaf5ad2a5aa8d22750e1',
'a75ba5815a1b09df0f08a05a47967a5fe6afe198ea720ae6127a4e191eebf0a1',
'e2418eaa66021e7aab7fa79f02a1f2a65daaab4e0d22ad6665571d6e2aaaa96a',
'0d5e19da44e7da98affd4abf1f77dea1f698f9b09f4e25ada2dfa4b81db91897',
'6ea5a7691b8aaafa570f6dadffe85ab828062f4aee911ad508a8955a708a621d',
'd0e97b22884a5a6afb7fa15a90222aab4aaea48daad498f94b2aea2a7d565d4e',
'ea68a5ea22f05d807912f170f050e218688d162772514e77b776adb6b08b52d8',
'205ddade45946d1f24baa0611af766a1d6daf6024e91a894ae892f860d888990',
'1ab82bea275b8f17b894a52afea79a5bee1a4706ed5feabf2694d8f252209d06',
'8dbae8da24b15aada6f9bd8282ed075fd04a560d8a5d094d9ad0547a292ffad9',
'6b80e0752b8aa67ba079b789fb976879e544deaf04a40afdf7a208dd92167e21',
'1d5aa8d9500d09eaa5ba5524e81fe5b62467a9e6717faf16b82d61a84aa04864',
'a0040ae289202b5aad15b71a678df1e2f00a810dd9829ab7a1a4bbf176d7ba42',
'aa8da4df28a0b6e7a20faeb9f47887b2bff1fa22badf415e96bede80aadaf5a1',
'4289faa70e102aa2b084299d792985247bd7702d1a8fade7a748a819970af1ab',
'a4ff9e90d296b8842aeb24d5fae6f92a9e781e0ead1a0a65b97ba60a876ae01b',
'ab50a420f8d7aaffdf1bfaf61fb056a2ee860fd108e215efdabe12badeba1ab6',
'af6b5866058a61a9a4a87da72414881a9251a1aabfa2efd2f1a7db04bd4f54f1',
);
$message = 'test : '.strftime("%Y %m %d %H:%M:%S");

// Do no change below
$context_options = array(
        'ssl' => array(
                'local_cert' => $apns_settings["certificate"],
                'passphrase' => $apns_settings["passphrase"],
                'peer_name' => $apns_settings["host"],
                'SNI_server_name' => $apns_settings["host"],
        ),
);
$stream_context = stream_context_create($context_options);

// connection to your proxy server
$apns = stream_socket_client('tcp://'.$proxy, $error, $errorString, 2, STREAM_CLIENT_CONNECT, $stream_context);

// destination host and port must be accepted by proxy
$connect_via_proxy = "CONNECT ".$apns_settings["host"].":".$apns_settings["port"]." HTTP/1.1\r\n".
"Host: ".$apns_settings["host"].":".$apns_settings["port"]."\n".
"User-Agent: SimplePush\n".
"Proxy-Connection: Keep-Alive\n\n";
fwrite($apns,$connect_via_proxy,strlen($connect_via_proxy));

// read whole response and check successful "HTTP/1.0 200 Connection established"
if($response = fread($apns,1024)) {
        $parts = explode(' ',$response);
        if($parts[1] !== '200') { 
                die('Connection error: '.trim($response));
        } else {
                echo "R:".$response.":R\n";
        }
} else {
        die('Timeout or other error');
}

echo "Proxy opened communication...\n";

// switch to SSL encrypted communication using local certificate from $context_options
if (stream_socket_enable_crypto($apns,true,STREAM_CRYPTO_METHOD_TLS_CLIENT))
    echo "Switched to SSL OK...\n";
else
    die('some error in SSL negociation');


$body['aps'] = array(
                     'alert' => $message
            //,         'badge' => 18
                     );

$body['category'] = 'message';
$body['sender'] = 'somebody';

$payload = json_encode($body);

$i = 0;
// Build the binary notification
foreach ($tokens as $token) {
    echo "$i: $token:\n";
    // old format command 0 (https://developer.apple.com/library/ios/documentation/NetworkingInternet/Conceptual/RemoteNotificationsPG/Chapters/LegacyFormat.html)
//    $msg = chr(0) . pack('n', 32) . pack('H*', $token) . pack('n', strlen($payload)) . $payload;
    // old format command 1 (https://developer.apple.com/library/ios/documentation/NetworkingInternet/Conceptual/RemoteNotificationsPG/Chapters/LegacyFormat.html)
    $msg = chr(1) . pack('N', $i) . pack('N', 0) . pack('n', 32) . pack('H*', $token) . pack('n', strlen($payload)) . $payload;
    // new format command 2
//    $frame = chr(1) . pack('n', 32) . pack('H*', $token) . chr(2) .  pack('n', strlen($payload)) . $payload . chr(3) . pack('n', 4) . pack('N', $i);
//    $msg = chr(2) . pack('N', strlen($frame)) . $frame;
    
    // Send it to the server
    $result = fwrite($apns, $msg, strlen($msg));
    
    if (!$result) {
            fclose($apns);
            die('Message not delivered' . PHP_EOL);
       }
    else
            echo 'Message successfully delivered' . PHP_EOL;
    
    $i++;

    // if too fast Apple closes the connection without feedback
    usleep(50*1000); // usleep is in us
    // put stream in non blocking
    stream_set_blocking($apns, 0);
    echo "response:";
    $apple_response = "";
    $apple_response = fread($apns, 6);
  
    if ($apple_response) {
         echo "Read ".strlen($apple_response)." bytes : ".$apple_response."\n";
         $array = unpack("Ccommand/Cstatus/Nidentifier", $apple_response);
         var_dump($array);
      }
    else {
         echo "Nothing returned\n";
      } 
    // put stream in blocking
    stream_set_blocking($apns, 1);
  }
    
echo "Final response:";
$apple_response = fread($apns, 6);

if ($apple_response) {
     echo $apple_response."\n";
     $array = unpack("Ccommand/Cstatus/Nidentifier", $apple_response);
     var_dump($array);
  }
else {
     echo "Nothing returned\n";
  } 

fclose($apns);

?>
