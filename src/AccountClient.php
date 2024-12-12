<?php

namespace rdx\directvps;

use RuntimeException;

class AccountClient {

	/** @var list<Server> */
	protected array $servers;

	public function __construct(
		readonly public Client $client,
		readonly public Account $account,
	) {}

	/**
	 * @return list<Server>
	 */
	public function getServers() : array {
		if (isset($this->servers)) return $this->servers;

		$rsp = $this->client->getJson(sprintf('https://mijn.directvps.nl/api/v2/%s/servers', $this->account->id));
		$json = (string) $rsp->getBody();
		$data = json_decode($json, true);

		if (!is_array($data) || !is_array($data['servers'] ?? '')) {
			throw new RuntimeException(sprintf("Invalid servers response JSON: %s", $json));
		}

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
