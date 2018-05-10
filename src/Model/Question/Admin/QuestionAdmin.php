<?php

namespace Model\Question\Admin;

class QuestionAdmin implements \JsonSerializable
{
    protected $questionId;

    protected $questionText = array('en' => '', 'es' => '');

    protected $answers = array();

    protected $answered = 0;

    protected $skipped = 0;

    protected $categories = array();

    /**
     * @return mixed
     */
    public function getQuestionId()
    {
        return $this->questionId;
    }

    /**
     * @return array
     */
    public function getQuestionText()
    {
        return $this->questionText;
    }

    /**
     * @return array
     */
    public function getAnswers()
    {
        return $this->answers;
    }

    /**
     * @return int
     */
    public function getAnswered()
    {
        return $this->answered;
    }

    /**
     * @return int
     */
    public function getSkipped()
    {
        return $this->skipped;
    }

    /**
     * @param mixed $questionId
     */
    public function setQuestionId($questionId)
    {
        $this->questionId = $questionId;
    }

    /**
     * @param $locale
     * @param array $questionText
     */
    public function setQuestionText($locale, $questionText)
    {
        $this->questionText[$locale] = $questionText;
    }

    /**
     * @param array $answers
     */
    public function setAnswers($answers)
    {
        $this->answers = $answers;
    }

    /**
     * @param int $answered
     */
    public function setAnswered($answered)
    {
        $this->answered = $answered;
    }

    /**
     * @param int $skipped
     */
    public function setSkipped($skipped)
    {
        $this->skipped = $skipped;
    }

    /**
     * @return array
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @param array $categories
     */
    public function setCategories($categories)
    {
        $this->categories = $categories;
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function toArray()
    {
        return array(
            'questionId' => $this->questionId,
            'textEs' => $this->questionText['es'],
            'textEn' => $this->questionText['en'],
            'answers' => $this->answers,
            'answered' => $this->answered,
            'skipped' => $this->skipped,
            'categories' => $this->categories,
        );
    }
}