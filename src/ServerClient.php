<?php

namespace rdx\directvps;

use RuntimeException;

class ServerClient {

	public function __construct(
		readonly public AccountClient $client,
		protected Server $server,
	) {}

	public function reboot() : void {
		throw new RuntimeException("Not yet implemented.");
	}

}
