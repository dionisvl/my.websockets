<?php
// (A) COMMAND LINE ONLY!
if (PHP_SAPI !== "cli") {
	die("Please run this in the command line");
}

// (B) REQUIRE RATCHET
require "vendor/autoload.php";

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

require_once('config.php');

// (C) CHAT CLASS
class Chat implements MessageComponentInterface
{
	// (C1) PROPERTIES
	private $debug = true; // Debug mode
	protected $clients; // Connect clients

	// (C2) CONSTRUCTOR - INIT LIST OF CLIENTS
	public function __construct()
	{
		$this->clients = new \SplObjectStorage;
		if ($this->debug) {
			echo "Chat server started.\r\n";
		}
	}

	// (C3) ON CLIENT CONNECT - STORE INTO $THIS->CLIENTS
	public function onOpen(ConnectionInterface $conn): void
	{
		$this->clients->attach($conn);
		if ($this->debug) {
			echo "Client connected: {$conn->resourceId}\r\n";
		}
	}

	// (C4) ON CLIENT DISCONNECT - REMOVE FROM $THIS->CLIENTS
	public function onClose(ConnectionInterface $conn): void
	{
		$this->clients->detach($conn);
		if ($this->debug) {
			echo "Client disconnected: {$conn->resourceId}\r\n";
		}
	}

	// (C5) ON ERROR
	public function onError(ConnectionInterface $conn, \Exception $e): void
	{
		$conn->close();
		if ($this->debug) {
			echo "Client error: {$conn->resourceId} | {$e->getMessage()}\r\n";
		}
	}

	// (C6) ON RECEIVING MESSAGE FROM CLIENT - SEND TO EVERYONE
	public function onMessage(ConnectionInterface $from, $msg): void
	{
		if ($this->debug) {
			echo "Received message from {$from->resourceId}: {$msg}\r\n";
		}

		$msg = json_decode($msg, true, 512, JSON_THROW_ON_ERROR);
		$msg['datetime'] = date('Y-m-d H:i:s');
		$msg = json_encode($msg, JSON_THROW_ON_ERROR);


		foreach ($this->clients as $client) {
			$client->send($msg);
		}
	}
}

// (D) WEBSOCKET SERVER START!
$server = IoServer::factory(new HttpServer(new WsServer(new Chat())), PORT);
$server->run();