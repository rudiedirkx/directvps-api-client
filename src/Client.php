<?php

namespace rdx\directvps;

use RuntimeException;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\RedirectMiddleware;
use Psr\Http\Message\ResponseInterface;
use rdx\jsdom\Node;

class Client {

	protected Guzzle $guzzle;
	public array $_requests = [];
	protected string $csrfTokenPlain = '';
	// protected string $csrfTokenEncrypted = '';
	protected array $accounts = [];

	public function __construct(
		protected Auth $auth,
	) {
		$this->guzzle = new Guzzle([
			'http_errors' => false,
			'cookies' => $auth->cookies(),
			'headers' => [
				'User-agent' => 'directvps/1.0',
			],
			'allow_redirects' => [
				'track_redirects' => true,
			] + RedirectMiddleware::$defaultSettings,
		]);
	}

	public function logIn() : bool {
		return $this->auth->logIn($this) && $this->checkSession();
	}

	protected function checkSession() : bool {
		$rsp = $this->getHtml('https://mijn.directvps.nl/account');
		if ($rsp->getStatusCode() != 200) {
			return false;
		}

		$html = (string) $rsp->getBody();
		$node = Node::create($html);
		$this->extractCsrfTokenFromHtml($node);

		$accountRows = $node->queryAll('.tab-pane.active tbody tr a.text-black[href$="/dashboard"]');

		$this->accounts = [];
		foreach ($accountRows as $a) {
			$id = explode('/', trim(parse_url($a['href'], PHP_URL_PATH), '/'))[0];
			$label = $a->textContent;
			$this->accounts[] = new Account($id, $label);
		}

		return true;
	}

	public function getAccounts() : array {
		return $this->accounts;
	}

	public function chooseAccount(Account $account) : AccountClient {
		// if (!isset($this->accounts[$id])) {
		// 	throw new RuntimeException(sprintf("Invalid account '%s'", $id));
		// }

		return new AccountClient($this, $account);
	}



	protected function extractCsrfTokenFromHtml(Node $node) : void {
		$meta = $node->query('meta[name="csrf-token"]');
		if (!$meta) return;

		$this->csrfTokenPlain = $meta['content'];
	}

	protected function getCsrfTokenCookie() : string {
		return urldecode($this->auth->cookies()->getCookieByName('XSRF-TOKEN')->getValue());
	}

	public function getHtml(string $url) : ResponseInterface {
		$this->_requests[] = ['GET', $url];
// dump($url, $this->guzzle);

		return $this->guzzle->get($url);
	}

	public function getJson(string $url) : ResponseInterface {
		$this->_requests[] = ['GET', $url];
// dump($url, $this->guzzle);

		return $this->guzzle->get($url, [
			'headers' => [
				'Accept' => 'application/json',
				'X-xsrf-token' => $this->getCsrfTokenCookie(),
				'X-csrf-token' => $this->csrfTokenPlain,
			],
		]);
	}

}
