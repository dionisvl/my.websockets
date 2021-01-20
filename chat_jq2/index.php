<?php
require('config.php');
?>

<!DOCTYPE html>
<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<div class="chat-wrapper">
	<div id="message-box"></div>
	<div class="user-panel">
		<input type="text" name="name" id="name" placeholder="Your Name" maxlength="15"/>
		<input type="text" name="message" id="message" placeholder="Type your message here..." maxlength="100"/>
		<button id="send-message">Send</button>
	</div>
</div>
</body>
</html>

<style type="text/css">
    .chat-wrapper {
        font: bold 11px/normal 'lucida grande', tahoma, verdana, arial, sans-serif;
        background: #00a6bb;
        padding: 20px;
        margin: 20px auto;
        box-shadow: 2px 2px 2px 0 #00000017;
        max-width: 700px;
        min-width: 500px;
    }

    #message-box {
        width: 97%;
        display: inline-block;
        height: 300px;
        background: #fff;
        box-shadow: inset 0 0 2px #00000017;
        overflow: auto;
        padding: 10px;
    }

    .user-panel {
        margin-top: 10px;
    }

    input[type=text] {
        border: none;
        padding: 5px 5px;
        box-shadow: 2px 2px 2px #0000001c;
    }

    input[type=text]#name {
        width: 20%;
    }

    input[type=text]#message {
        width: 60%;
    }

    button#send-message {
        border: none;
        padding: 5px 15px;
        background: #11e0fb;
        box-shadow: 2px 2px 2px #0000001c;
    }
</style>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script type="text/javascript">
  let msgBox = $('#message-box')
  let wsUri = '<?='ws://' . HOST . ':' . PORT . '/' . SUB_FOLDER . '/server.php' ?>'
  websocket = new WebSocket(wsUri)

  websocket.onopen = function (ev) {
    msgBox.append('<div class="system_msg" style="color:#bbbbbb">Welcome to my "Chat box"!</div>')
  }
  websocket.onmessage = function (ev) {
    let response = JSON.parse(ev.data)
    let res_type = response.type
    let user_message = response.message
    let user_name = response.name
    let user_color = response.color
    switch (res_type) {
      case 'usermsg':
        msgBox.append('<div><span class="user_name" style="color:' + user_color + '">' + user_name +
          '</span> : <span class="user_message">' + user_message + '</span></div>')
        break
      case 'system':
        msgBox.append('<div style="color:#bbbbbb">' + user_message + '</div>')
        break
    }
    msgBox[0].scrollTop = msgBox[0].scrollHeight
  }

  websocket.onerror = function (ev) {
    msgBox.append('<div class="system_error">Error Occurred - ' + ev.data + '</div>')
  }
  websocket.onclose = function (ev) { msgBox.append('<div class="system_msg">Connection Closed</div>') }
  $('#send-message').click(function () {
    send_message()
  })

  $('#message').on('keydown', function (event) {
    if (event.which === 13) {
      send_message()
    }
  })

  function send_message () {
    let message_input = $('#message')
    let name_input = $('#name')
    if (message_input.val() === '') {
      alert('Enter your Name please!')
      return
    }
    if (message_input.val() === '') {
      alert('Enter Some message Please!')
      return
    }
    let msg = {
      message: message_input.val(),
      name: name_input.val(),
      color: '<?=COLOR_PICK?>',
    }
    websocket.send(JSON.stringify(msg))
    message_input.val('')
  }
</script>