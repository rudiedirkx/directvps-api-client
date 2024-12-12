<?php

namespace rdx\directvps;

use RuntimeException;

class AccountClient {

	protected array $servers;

	public function __construct(
		readonly public Client $client,
		protected Account $account,
	) {}

	public function getServers() : array {
		if (isset($this->servers)) return $this->servers;

		$rsp = $this->client->getJson(sprintf('https://mijn.directvps.nl/api/v2/%s/servers', $this->account->getId()));
		$json = (string) $rsp->getBody();
		$data = json_decode($json, true);

		$servers = [];
		foreach ($data['servers'] as $info) {
			$server = Server::fromServersApiV2($info);
			$servers[] = $server;
		}

		return $this->servers = $servers;
	}

	public function chooseServer(Server $server) : ServerClient {
		// if (!isset($this->servers[ $server->getKey() ])) {
		// 	throw new RuntimeException(sprintf("Invalid server '%s'", $server->getKey()));
		// }

		// Request https://mijn.directvps.nl/api/v2/<account>/servers/<platform>/<server>/info for power options?

		return new ServerClient($this, $server);
	}

}
