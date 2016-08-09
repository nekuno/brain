<?php

namespace Event;

use Symfony\Component\EventDispatcher\Event;

class SimilarityProcessEvent extends Event {
	
	protected $userId;

	public function __construct($userId) {
		$this->userId = (integer) $userId;
	}

	public function getUserId() {
		return $this->userId;
	}
}
