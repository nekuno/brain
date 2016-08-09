<?php

namespace Event;

use Symfony\Component\EventDispatcher\Event;

class MatchingProcessEvent extends Event {

	protected $userId;

	public function __construct($userId) {
		$this->userId = (integer) $userId;
	}

	public function getUserId() {
		return $this->userId;
	}
}
