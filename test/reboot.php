<?php

use rdx\directvps\Account;
use rdx\directvps\AccountClient;
use rdx\directvps\AuthSession;
use rdx\directvps\AuthWeb;
use rdx\directvps\Client;
use rdx\directvps\Server;
use rdx\directvps\ServerClient;

require __DIR__ . '/inc.bootstrap.php';

$accountId = $_SERVER['argv'][1] ?? '';
if (!$accountId) {
	echo "Need account argument\n";
	exit(1);
}

$serverKey = $_SERVER['argv'][2] ?? '';
if (!preg_match('#^[^/]+/[^/]+$#', $serverKey)) {
	echo "Need key argument, like 'fra1/19e70814-5b5d-14bf-ab2f-64ba2d84daef'\n";
	exit(1);
}
[$serverPlatform, $serverId] = explode('/', $serverKey);

$client = new Client(new AuthSession(CONTROLPANEL_SESSION));

$account = new AccountClient($client, new Account($accountId, 'name'));
$server = new ServerClient($account, new Server($serverPlatform, $serverId, 'hostname', 'label'));
dump($server);

if (!$client->logIn()) {
	echo "Not logged in\n";
	exit(1);
}

echo "Rebooting...\n";

$server->reboot();
