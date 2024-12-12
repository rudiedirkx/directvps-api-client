<?php

namespace rdx\directvps;

use RuntimeException;

class ServerClient {

	public function __construct(
		readonly public AccountClient $client,
		readonly public Server $server,
	) {}

	public function reboot() : void {
		$url = sprintf(
			'https://mijn.directvps.nl/api/v2/%s/servers/%s/%s/reboot',
			$this->client->account->id,
			$this->server->platform,
			$this->server->id,
		);
		$rsp = $this->client->client->patchJson($url, []);
		if ($rsp->getStatusCode() != 200) {
			throw new RuntimeException(sprintf('Reboot PATCH response code not 200 but %d', $rsp->getStatusCode()));
		}

		$json = (string) $rsp->getBody();
		// {"code":0,"data":[],"locale":"nl","message":"OK","status":"success","success":true}
		$data = json_decode($json, true);
		if (!is_array($data)) {
			throw new RuntimeException(sprintf('Reboot PATCH response not JSON: %s', $json));
		}

		if (
			($data['code'] ?? '') !== 0 ||
			($data['status'] ?? '') !== 'success' ||
			($data['success'] ?? '') !== true
		) {
			throw new RuntimeException(sprintf('Reboot PATCH response unexpected: %s', $json));
		}
	}

}
