<?php
/**
 * Example of use SSL communication through http proxy
 */
$apns_settings = array(
        "host" => 'gateway.sandbox.push.apple.com',
        "port" => 2196,
        "certificate" => 'ipad.pem',
        "passphrase" => '',
);

$proxy = 'proxyhttp:8080';

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


while(!feof($apns)) {
    echo "Read:\n";
    $apple_response = fread($apns, 38);
    
    if ($apple_response) {
         echo $apple_response."\n";
         $array = unpack("Ntime/nlenght/H64tocken", $apple_response);
         var_dump($array);
      }
    else {
         echo "Nothing returned\n";
      } 
}

fclose($apns);
