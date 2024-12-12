<?php

namespace rdx\directvps;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\RedirectMiddleware;
use PragmaRX\Google2FA\Google2FA;
use RuntimeException;
use rdx\jsdom\Node;

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
		// Visit login page
		$rsp = $client->getHtml('https://mijn.directvps.nl/login');

		$html = (string) $rsp->getBody();
		$dom = Node::create($html);

		$input = $dom->query('input[name="_token"]');
		$token = $input['value'];

		// Submit login form
		$rsp = $client->postRedirect('https://mijn.directvps.nl/login', [
			'_token' => $token,
			'email' => $this->username,
			'password' => $this->password,
			'submit' => '',
		]);
// dump($rsp, (string) $rsp->getBody());

		$html = (string) $rsp->getBody();
		$dom = Node::create($html);

		$input = $dom->query('input[name="email"]');
		if ($input) {
			return false;
		}

		$input = $dom->query('input[name="one_time_password"]');
		if ($input && !$this->tfaSecret) {
			throw new RuntimeException("Login requires 2FA, but config doesn't include a secret.");
		}

		$g2fa = new Google2FA();
		$otp = $g2fa->getCurrentOtp($this->tfaSecret);

		$input = $dom->query('input[name="_token"]');
		$token = $input['value'];

		// Submit 2FA form
		$rsp = $client->postRedirect('https://mijn.directvps.nl/2fa', [
			'_token' => $token,
			'one_time_password' => $otp,
			'submit' => '',
		]);
// dump($rsp, (string) $rsp->getBody());

		$html = (string) $rsp->getBody();
		$dom = Node::create($html);

		$input = $dom->query('input[name="one_time_password"]');
		if ($input) {
			return false;
		}

		return true;
	}

	public function getControlpanelSessionCookie() : string {
		return urldecode($this->cookies->getCookieByName('directvps_controlpanel_session')?->getValue() ?? '');
	}

}
