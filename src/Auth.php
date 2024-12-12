<?php

namespace rdx\directvps;

use GuzzleHttp\Cookie\CookieJar;

interface Auth {

	public function cookies() : CookieJar;

	public function logIn(Client $client) : bool;

}
