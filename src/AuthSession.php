<?php

namespace rdx\directvps;

use GuzzleHttp\Cookie\CookieJar;

class AuthSession implements Auth {

	protected CookieJar $cookies;

	public function __construct(
		string $controlpanelSession,
		// string $cpToken,
	) {
		$this->cookies = new CookieJar(false, [
			[
				'Domain' => 'mijn.directvps.nl',
				'Name' => 'directvps_controlpanel_session',
				'Value' => $controlpanelSession,
			],
			// [
			// 	'Domain' => 'mijn.directvps.nl',
			// 	'Name' => 'dvps_cp_token',
			// 	'Value' => $cpToken,
			// ],
		]);
	}

	public function cookies() : CookieJar {
		return $this->cookies;
	}

	public function logIn(Client $client) : bool {
		return true;
	}

}
