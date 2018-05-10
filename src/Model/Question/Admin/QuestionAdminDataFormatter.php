<?php

namespace Model\Question\Admin;

class QuestionAdminDataFormatter
{
    public function getCreateData(array $data)
    {
        return array(
            'answerTexts' => $this->getAnswersTexts($data),
            'questionTexts' => $this->getQuestionTexts($data),
            'categories' => isset($data['categories']) ? $data['categories'] : []
        );
    }

    public function getUpdateData(array $rawData)
    {
        $data = array(
            'answerTexts' => $this->getAnswersTexts($rawData),
            'questionTexts' => $this->getQuestionTexts($rawData),
            'categories' => isset($rawData['categories']) ? $rawData['categories'] : []
        );

        if (isset($rawData['questionId'])){
            $data['questionId'] = $rawData['questionId'];
        }

        return $data;
    }

    protected function getAnswersTexts(array $data)
    {
        $answers = array();
        foreach ($data as $key => $value) {
            $internalId = $this->extractAnswerInternalId($key);
            $hasAnswerText = strpos($key, 'answer') !== false;
            $isId = strpos($key, 'Id') !== false;

            if ($hasAnswerText && $isId && !empty($value)) {
                $answers[$internalId]['answerId'] = $value;
            } elseif ($hasAnswerText && !empty($value)) {
                $locale = $this->extractLocale($key);
                $answers[$internalId]['locales'][$locale] = $value;
            }
        }

        return $answers;
    }

    protected function getQuestionTexts(array $data)
    {
        $texts = array();
        foreach ($data as $key => $value) {
            $isQuestionText = strpos($key, 'text') === 0;
            if ($isQuestionText) {
                $locale = $this->extractLocale($key);
                $texts[$locale] = $value;
            }
        }

        return $texts;
    }

    //To change with more locales
    protected function extractLocale($text)
    {
        if (strpos($text, 'Es') !== false) {
            return 'es';
        }

        if (strpos($text, 'En') !== false) {
            return 'en';
        }

        return null;
    }

    protected function extractAnswerInternalId($text)
    {
        $prefixSize = strlen('answer');
        $number = substr($text, $prefixSize, 1);

        return (integer)$number;
    }
}