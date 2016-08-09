<?php

namespace Event;

class SimilarityProcessStepEvent extends SimilarityProcessEvent {
	
	protected $percentage;

	public function __construct($userId, $percentage) {
		parent::__construct($userId);
		$this->percentage = (integer) $percentage;
	}

	public function getPercentage() {
		return $this->percentage;
	}
}
