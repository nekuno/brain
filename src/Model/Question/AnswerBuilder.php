<?php

namespace Model\Question;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;

class AnswerBuilder
{
    public function buildUserAnswer(Row $row)
    {
        $this->checkMandatoryKeys($row);

        list($question, $answerNode, $userAnswer, $rates) = $this->getRowData($row);

        $acceptedAnswers = $this->buildAcceptedAnswers($row);

        $answer = new Answer();
        $answer->setQuestionId($question->getId());
        $answer->setAnswerId($answerNode->getId());
        $answer->setAcceptedAnswers($acceptedAnswers);
        $answer->setRating($rates->getProperty('rating'));
        $answer->setExplanation($userAnswer->getProperty('explanation'));
        $answer->setPrivate($userAnswer->getProperty('private'));
        $answer->setAnsweredAt($userAnswer->getProperty('answeredAt'));
        $this->updateEditable($answer);

        return $answer;
    }

    public function updateEditable(Answer $answer)
    {
        $answeredAt = floor($answer->getAnsweredAt() / 1000);
        $now = time();
        $oneDay = 24 * 3600;

        $untilNextEdit = ($answeredAt + $oneDay) - $now;
        $answer->setEditableIn($untilNextEdit);
        $answer->setEditable($untilNextEdit < 0);
    }

    /**
     * @param Row $row
     */
    protected function checkMandatoryKeys(Row $row)
    {
        $keys = array('question', 'answer', 'userAnswer', 'rates', 'acceptedAnswers');
        foreach ($keys as $key) {
            if (!$row->offsetExists($key)) {
                throw new \RuntimeException(sprintf('"%s" key needed in row', $key));
            }
        }
    }

    /**
     * @param Row $row
     * @return array
     */
    protected function buildAcceptedAnswers(Row $row)
    {
        $acceptedAnswers = array();
        foreach ($row->offsetGet('acceptedAnswers') as $acceptedAnswer) {
            /* @var $acceptedAnswer Node */
            $acceptedAnswers[] = $acceptedAnswer->getId();
        }

        return $acceptedAnswers;
    }

    /**
     * @param Row $row
     * @return array
     */
    protected function getRowData(Row $row)
    {
        $question = $row->offsetGet('question');
        $answerNode = $row->offsetGet('answer');
        $userAnswer = $row->offsetGet('userAnswer');
        $rates = $row->offsetGet('rates');

        return array($question, $answerNode, $userAnswer, $rates);
    }
}