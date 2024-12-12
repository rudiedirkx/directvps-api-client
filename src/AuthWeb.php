<?php

namespace rdx\directvps;

use GuzzleHttp\Cookie\CookieJar;

class AuthWeb implements Auth {

	protected CookieJar $cookies;

	public function __construct(
		protected string $username,
		#[\SensitiveParameter]
		protected string $password,
		#[\SensitiveParameter]
		protected string $tfaSecret = '',
	) {
		$this->cookies = new CookieJar();
	}

	public function cookies() : CookieJar {
		return $this->cookies;
	}

	public function logIn(Client $client) : bool {
		throw new \RuntimeException("Not yet implemented.");

		// return true;
	}

}
