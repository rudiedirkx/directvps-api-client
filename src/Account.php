<?php

namespace rdx\directvps;

class Account {

	final public function __construct(
		readonly public string $id,
		protected string $label,
	) {}

	public function __toString() : string {
		return $this->label;
	}

}
