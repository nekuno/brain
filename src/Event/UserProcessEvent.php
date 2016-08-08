<?php


namespace Event;

use Symfony\Component\EventDispatcher\Event;

class UserProcessEvent extends Event
{
	const SIMILARITY = 'SIMILARITY';
	const MATCHING = 'MATCHING';

	protected $userId;

	protected $process;

	public function __construct($userId, $process)
	{
		$this->userId = (integer)$userId;
		$this->process = $process;
	}

	public function getUserId()
	{

		return $this->userId;
	}

	public function getProcess()
	{

		return $this->process;
	}

}
