<?php

namespace Model\Question\Admin;

class AnswerAdmin implements \JsonSerializable
{
    protected $answerId;

    protected $text = array('es' => '', 'en' => '');

    /**
     * @param mixed $answerId
     */
    public function setAnswerId($answerId)
    {
        $this->answerId = $answerId;
    }

    /**
     * @param $locale
     * @param array $text
     */
    public function setText($locale, $text)
    {
        $this->text[$locale] = $text;
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function toArray()
    {
        return array(
            'answerId' => $this->answerId,
            'textEs' => $this->text['es'],
            'textEn' => $this->text['en'],
        );
    }

}