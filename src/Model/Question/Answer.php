<?php

namespace Model\Question;

class Answer implements \JsonSerializable
{
    protected $answerId;

    protected $questionId;

    protected $acceptedAnswers = array();

    protected $rating;

    protected $explanation;

    protected $private = false;

    protected $answeredAt;

    protected $editable = true;

    protected $editableIn = 0;

    /**
     * @return mixed
     */
    public function getAnswerId()
    {
        return $this->answerId;
    }

    /**
     * @param mixed $answerId
     */
    public function setAnswerId($answerId)
    {
        $this->answerId = $answerId;
    }

    /**
     * @return mixed
     */
    public function getQuestionId()
    {
        return $this->questionId;
    }

    /**
     * @param mixed $questionId
     */
    public function setQuestionId($questionId)
    {
        $this->questionId = $questionId;
    }

    /**
     * @return array
     */
    public function getAcceptedAnswers()
    {
        return $this->acceptedAnswers;
    }

    /**
     * @param array $acceptedAnswers
     */
    public function setAcceptedAnswers($acceptedAnswers)
    {
        $this->acceptedAnswers = $acceptedAnswers;
    }

    /**
     * @return mixed
     */
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * @param mixed $rating
     */
    public function setRating($rating)
    {
        $this->rating = $rating;
    }

    /**
     * @return mixed
     */
    public function getExplanation()
    {
        return $this->explanation;
    }

    /**
     * @param mixed $explanation
     */
    public function setExplanation($explanation)
    {
        $this->explanation = $explanation;
    }

    /**
     * @return bool
     */
    public function isPrivate()
    {
        return $this->private;
    }

    /**
     * @param bool $private
     */
    public function setPrivate($private)
    {
        $this->private = $private;
    }

    /**
     * @return mixed
     */
    public function getAnsweredAt()
    {
        return $this->answeredAt;
    }

    /**
     * @param mixed $answeredAt
     */
    public function setAnsweredAt($answeredAt)
    {
        $this->answeredAt = $answeredAt;
    }

    /**
     * @return bool
     */
    public function isEditable()
    {
        return $this->editable;
    }

    /**
     * @param bool $editable
     */
    public function setEditable($editable)
    {
        $this->editable = $editable;
    }

    /**
     * @return int
     */
    public function getEditableIn()
    {
        return $this->editableIn;
    }

    /**
     * @param int $editableIn
     */
    public function setEditableIn($editableIn)
    {
        $this->editableIn = $editableIn;
    }

    function jsonSerialize()
    {
        return array(
            'questionId' => $this->getQuestionId(),
            'answerId' => $this->getAnswerId(),
            'acceptedAnswers' => $this->getAcceptedAnswers(),
            'rating' => $this->getRating(),
            'explanation' => $this->getExplanation(),
            'isPrivate' => $this->isPrivate(),
            'answeredAt' => $this->getAnsweredAt(),
            'isEditable' => $this->isEditable(),
            'editableIn' => $this->getEditableIn(),
        );
    }
}