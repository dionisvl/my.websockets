<?php

class ChatHandler
{
    public function send($message): bool
    {
        global $clientSocketArray;
        $messageLength = strlen($message);
        foreach ($clientSocketArray as $clientSocket) {
            @socket_write($clientSocket, $message, $messageLength);
        }
        return true;
    }

    public function unseal($socketData): string
    {
        $length = ord($socketData[1]) & 127;
        if ($length === 126) {
            $masks = substr($socketData, 4, 4);
            $data = substr($socketData, 8);
        } elseif ($length === 127) {
            $masks = substr($socketData, 10, 4);
            $data = substr($socketData, 14);
        } else {
            $masks = substr($socketData, 2, 4);
            $data = substr($socketData, 6);
        }
        $socketData = "";
        for ($i = 0, $iMax = strlen($data); $i < $iMax; ++$i) {
            $socketData .= $data[$i] ^ $masks[$i % 4];
        }
        return $socketData;
    }

    public function seal($socketData): string
    {
        $b1 = 0x80 | (0x1 & 0x0f);
        $length = strlen($socketData);
        $header = '';

        if ($length <= 125) {
            $header = pack('CC', $b1, $length);
        } elseif ($length > 125 && $length < 65536) {
            $header = pack('CCn', $b1, 126, $length);
        } elseif ($length >= 65536) {
            $header = pack('CCNN', $b1, 127, $length);
        }

        return $header . $socketData;
    }

    public function doHandshake($received_header, $client_socket_resource, $host_name, $port): void
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
        $buffer = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "WebSocket-Origin: $host_name\r\n" .
            "WebSocket-Location: ws://$host_name:$port/demo/shout.php\r\n" .
            "Sec-WebSocket-Accept:$secAccept\r\n\r\n";
        socket_write($client_socket_resource, $buffer, strlen($buffer));
    }

    public function newConnectionACK($client_ip_address): string
    {
        $message = 'New client ' . $client_ip_address . ' joined';
        $messageArray = array('message' => $message, 'message_type' => 'chat-connection-ack');
        $ACK = $this->seal(json_encode($messageArray, JSON_THROW_ON_ERROR));
        return $ACK;
    }

    public function connectionDisconnectACK($client_ip_address): string
    {
        $message = 'Client ' . $client_ip_address . ' disconnected';
        $messageArray = array('message' => $message, 'message_type' => 'chat-connection-ack');
        $ACK = $this->seal(json_encode($messageArray, JSON_THROW_ON_ERROR));
        return $ACK;
    }

    public function createChatBoxMessage($chat_user, $chat_box_message)
    {
        $message = $chat_user . ": <div class='chat-box-message'>" . $chat_box_message . "</div>";
        $messageArray = array('message' => $message, 'message_type' => 'chat-box-html');
        $chatMessage = $this->seal(json_encode($messageArray, JSON_THROW_ON_ERROR));
        return $chatMessage;
    }
}