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
	echo "Need server argument, like 'fra1/19e70814-5b5d-14bf-ab2f-64ba2d84daef'\n";
	exit(1);
}
[$serverPlatform, $serverId] = explode('/', $serverKey);

// $client = new Client(new AuthSession(CONTROLPANEL_SESSION));
$client = new Client(new AuthWeb(ACC_USERNAME, ACC_PASSWORD, ACC_2FA_SECRET));

$account = new AccountClient($client, new Account($accountId, 'name'));
$server = new ServerClient($account, new Server($serverPlatform, $serverId, 'hostname', 'label'));
dump($server);

echo "Logging in...\n";
if (!$client->logIn()) {
	echo "Not logged in\n";
	exit(1);
}
dump($client->_requests);

echo "Rebooting...\n";

$server->reboot();
echo "OK\n";

dump($client->_requests);
