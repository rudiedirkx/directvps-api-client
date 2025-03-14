<?php

namespace rdx\directvps;

use RuntimeException;

class Server {

	/** @var AssocArray */
	protected array $info = [];

	final public function __construct(
		readonly public string $platform,
		readonly public string $id,
		protected string $hostname,
		protected string $label,
	) {}

	public function getKey() : string {
		return sprintf('%s/%s', $this->platform, $this->id);
	}

	public function getStatus() : ?string {
		return $this->info['status']['plain'] ?? null; // @phpstan-ignore offsetAccess.nonOffsetAccessible
	}

	/**
	 * @return list<string>
	 */
	public function getIpAddresses() : ?array {
		return $this->info['ipaddresses'] ?? null;
	}

	public function __debugInfo() : array {
		return [
			'key' => $this->getKey(),
			'status' => $this->getStatus(),
		];
	}

	public function __toString() : string {
		return sprintf('%s (%s)', $this->hostname, $this->label);
	}

	/**
	 * @param AssocArray $info
	 */
	static public function fromServersApiV2(array $info) : static {
		if (!isset($info['platform'], $info['id'], $info['hostname'], $info['label'])) {
			throw new RuntimeException(sprintf("Can't construct Server from info array: %s", json_encode($info)));
		}

		$server = new static($info['platform'], $info['id'], $info['hostname'], $info['label']);
		$server->info = $info;
		return $server;
	}

}
