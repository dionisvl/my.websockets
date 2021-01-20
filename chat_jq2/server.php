<?php
require_once("config.php");


/**
 * The following code for handshake with new incoming connections and encrypt and decrypt messages incoming and outgoing over sockets:
 */


function send_message($msg)
{
    global $clients;
    foreach ($clients as $changed_socket) {
        @socket_write($changed_socket, $msg, strlen($msg));
    }
    return true;
}

function unmask($text)
{
    $length = ord($text[1]) & 127;
    if ($length === 126) {
        $masks = substr($text, 4, 4);
        $data = substr($text, 8);
    } elseif ($length === 127) {
        $masks = substr($text, 10, 4);
        $data = substr($text, 14);
    } else {
        $masks = substr($text, 2, 4);
        $data = substr($text, 6);
    }
    $text = "";
    for ($i = 0; $i < strlen($data); ++$i) {
        $text .= $data[$i] ^ $masks[$i % 4];
    }
    return $text;
}

function mask($text)
{
    $b1 = 0x80 | (0x1 & 0x0f);
    $length = strlen($text);

    if ($length <= 125) {
        $header = pack('CC', $b1, $length);
    } elseif ($length > 125 && $length < 65536) {
        $header = pack('CCn', $b1, 126, $length);
    } elseif ($length >= 65536) {
        $header = pack('CCNN', $b1, 127, $length);
    }
    return $header . $text;
}

function perform_handshaking($received_header, $client_conn, $host, $port)
{
    $headers = [];
    $lines = preg_split("/\r\n/", $received_header);
    foreach ($lines as $line) {
        $line = rtrim($line);
        if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
            $headers[$matches[1]] = $matches[2];
        }
    }
    $secKey = $headers['Sec-WebSocket-Key'];
    $secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));

    $upgrade = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
        "Upgrade: websocket\r\n" .
        "Connection: Upgrade\r\n" .
        "WebSocket-Origin: ".HOST."\r\n" .
        "WebSocket-Location: ws://".HOST.":".PORT."/".SUB_FOLDER."/chat-daemon.php\r\n" .
        "Sec-WebSocket-Accept:$secAccept\r\n\r\n";

    socket_write($client_conn, $upgrade, strlen($upgrade));
}


/**
 * Now add following code to create bind and listen tcp/ip sockets
 */
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($socket, 0, PORT);
socket_listen($socket);
$clients = [$socket];

/**
 * Ok now a endless loop that will use for handling incoming and send messages:
 */
while (true) {
    $changed = $clients;
    socket_select($changed, $null, $null, 0, 10);

    if (in_array($socket, $changed, true)) {
        $socket_new = socket_accept($socket);
        $clients[] = $socket_new;
        $header = socket_read($socket_new, 1024);
        perform_handshaking($header, $socket_new, HOST, PORT);
        socket_getpeername($socket_new, $ip);
        $response = mask(json_encode(['type' => 'system', 'message' => $ip . ' connected'], JSON_THROW_ON_ERROR));
        send_message($response);
        $found_socket = array_search($socket, $changed, true);
        unset($changed[$found_socket]);
    }

    foreach ($changed as $changed_socket) {
        while (socket_recv($changed_socket, $buf, 1024, 0) >= 1) {
            $received_text = unmask($buf);
            $tst_msg = json_decode($received_text, true, 512);
            $user_name = $tst_msg['name'];
            $user_message = $tst_msg['message'];
            $user_color = $tst_msg['color'];
            $response_text = mask(
                json_encode(
                    ['type' => 'usermsg', 'name' => $user_name, 'message' => $user_message, 'color' => $user_color],
                    JSON_THROW_ON_ERROR
                )
            );
            send_message($response_text);
            break 2;
        }

        $buf = @socket_read($changed_socket, 1024, PHP_NORMAL_READ);
        if ($buf === false) {
            $found_socket = array_search($changed_socket, $clients, true);
            socket_getpeername($changed_socket, $ip);
            unset($clients[$found_socket]);

            $response = mask(json_encode(['type' => 'system', 'message' => $ip . ' disconnected'], JSON_THROW_ON_ERROR));
            send_message($response);
        }
    }
}
socket_close($socket);