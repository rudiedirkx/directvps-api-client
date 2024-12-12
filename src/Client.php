<?php

namespace rdx\directvps;

use Closure;
use RuntimeException;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\RedirectMiddleware;
use Psr\Http\Message\ResponseInterface;
use rdx\jsdom\Node;

class Client {

	protected Guzzle $guzzle;
	/** @var list<array{string, string, ?float}> */
	public array $_requests = [];
	protected string $csrfTokenPlain = '';
	// protected string $csrfTokenEncrypted = '';
	/** @var list<Account> */
	protected array $accounts = [];

	public function __construct(
		protected Auth $auth,
	) {
		$this->guzzle = new Guzzle([
			'http_errors' => false,
			'cookies' => $auth->cookies(),
			'headers' => [
				// 'User-agent' => 'directvps/1.0',
				'User-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
			],
			'allow_redirects' => false,
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
			$path = parse_url($a['href'], PHP_URL_PATH) ?: $a['href'];
			$id = explode('/', trim($path, '/'))[0];
			$label = $a->textContent;
			$this->accounts[] = new Account($id, $label);
		}

		return true;
	}

	/**
	 * @return list<Account>
	 */
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

	/**
	 * @return AssocArray
	 */
	protected function allowRedirects() : array {
		return [
			'allow_redirects' => [
				'track_redirects' => false,
			] + RedirectMiddleware::$defaultSettings,
		];
	}

	public function getHtml(string $url) : ResponseInterface {
		$this->_requests[] = ['GET', $url, null];
		return $this->wrapRequest(function() use ($url) {
			return $this->guzzle->get($url);
		});
	}

	public function getJson(string $url) : ResponseInterface {
		$this->_requests[] = ['GET', $url, null];
		return $this->wrapRequest(function() use ($url) {
			return $this->guzzle->get($url, [
				'headers' => [
					'Accept' => 'application/json',
					'X-xsrf-token' => $this->getCsrfTokenCookie(),
					'X-csrf-token' => $this->csrfTokenPlain,
				],
			]);
		});
	}

	/**
	 * @param AssocArray $body
	 */
	public function postRedirect(string $url, array $body) : ResponseInterface {
		$this->_requests[] = ['POST', $url, null];
		return $this->wrapRequest(function() use ($url, $body) {
			return $this->guzzle->post($url, [
				'allow_redirects' => $this->allowRedirects(),
				'form_params' => $body,
			]);
		});
	}

	/**
	 * @param AssocArray $body
	 */
	public function patchJson(string $url, array $body) : ResponseInterface {
		$this->_requests[] = ['PATCH', $url, null];
		return $this->wrapRequest(function() use ($url, $body) {
			$options = [
				'headers' => [
					'Accept' => 'application/json',
					'X-xsrf-token' => $this->getCsrfTokenCookie(),
					'X-csrf-token' => $this->csrfTokenPlain,
				],
			];
			if (count($body)) $options['form_params'] = $body;
			return $this->guzzle->patch($url, $options);
		});
	}

	protected function wrapRequest(Closure $request) : ResponseInterface {
		$t = microtime(true);
		try {
			return $request();
		}
		finally {
			$this->_requests[count($this->_requests)-1][2] = microtime(true) - $t; // @phpstan-ignore assign.propertyType
		}
	}

}
