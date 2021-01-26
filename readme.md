# Websockets examples of use

## chat_ratchet
https://code-boxx.com/php-live-chat-websocket/
- основывается на phpRatchet
- JS - vanilla
- локально работает отлично, на сервере нужно сделать Nginx-websockets proxy

run server:
```
php -q C:\OSpanel\domains\test\websockets\chat_ratchet\2-chat-server.php
```
Open url:  
http://test/websockets/chat_ratchet/3a-chat-client.php

## chat_jq1
https://phppot.com/php/simple-php-chat-using-websocket/
- jquery: jquery-1.9.1
- вообще старый код
- сам чат работает и выглядит симпатично

run server:
```
php -q C:\OSpanel\domains\test\websockets\chat_jq1\php-socket.php
```
Open url:  
http://test/websockets/chat_jq1/index.php

## chat_jq2
https://nomadphp.com/blog/92/build-a-chat-system-with-php-sockets-and-w3c-web-sockets-apis

- jquery 3.3.1
- код содержит ошибки и без допила не работает
- чат работает, дизайн не плох, выровнен по центру

run server:  
```
php -q C:\OSpanel\domains\test\websockets\chat_jq2\server.php
```
Open url:  
http://test/websockets/chat_jq2/index.php



