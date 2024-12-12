<?php

namespace rdx\directvps;

class Account {

	final public function __construct(
		protected string $id,
		protected string $label,
	) {}

	public function getId() : string {
		return $this->id;
	}

	public function __toString() : string {
		return $this->label;
	}

}
