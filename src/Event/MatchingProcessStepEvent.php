<?php

namespace Event;

class MatchingProcessStepEvent extends MatchingProcessEvent {
	
	protected $percentage;

	public function __construct($userId, $percentage) {
		parent::__construct($userId);
		$this->percentage = (integer) $percentage;
	}

	public function getPercentage() {
		return $this->percentage;
	}
}
