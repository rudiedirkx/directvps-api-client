<?php

use rdx\directvps\AuthSession;
use rdx\directvps\AuthWeb;
use rdx\directvps\Client;

require __DIR__ . '/inc.bootstrap.php';

$client = new Client(new AuthSession(CONTROLPANEL_SESSION));

var_dump($client->logIn());
dump($accounts = $client->getAccounts());

$account = $client->chooseAccount(reset($accounts));
// dump($account);

dump($servers = $account->getServers());

$server = $account->chooseServer(reset($servers));
// dump($server);

dump($client->_requests);
