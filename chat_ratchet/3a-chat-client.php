<?php
require_once('config.php');
?>

<!-- (A) CSS + JS -->
<link rel="stylesheet" href="3b-chat-client.css"/>
<script>
    const HOST = '<?=HOST?>'
    const PORT = '<?=PORT?>'
    const CHAT_USER_COLOR = '<?=CHAT_USER_COLOR_PICK?>'
</script>
<script src="3c-chat-client.js"></script>

<!-- (B) CHAT DOCKET -->
<div id="chat-wrap">
    <!-- (B1) CHAT MESSAGES -->
    <div id="chat-messages"></div>

    <!-- (B2) SET NAME -->
    <form id="chat-name" onsubmit="return chat.start()">
        <input type="text" id="chat-name-set" placeholder="What is your name?" value="John Doe" required/>
        <input type="submit" id="chat-name-go" value="Start"/>
    </form>

    <!-- (B3) SEND MESSAGE -->
    <form id="chat-send" onsubmit="return chat.send()">
        <input type="text" id="chat-send-text" placeholder="Enter message" required/>
        <input type="submit" id="chat-send-go" value="Send"/>
    </form>
</div>