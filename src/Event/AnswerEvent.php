<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 14/11/14
 * Time: 17:20
 */

namespace Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class AnswerEvent
 * @package Event
 */
class AnswerEvent extends Event
{

    private $userId;

    private $questionId;

    /**
     * @param $userId
     * @param $questionId
     */
    public function __construct($userId, $questionId)
    {

        $this->userId = $userId;
        $this->questionId = $questionId;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {

        return $this->userId;
    }

    /**
     * @return mixed
     */
    public function getQuestion()
    {

        return $this->questionId;
    }

} 