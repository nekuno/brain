<?php

namespace Service\GraphExplore;

class GraphDataBuilder
{
    public function buildAnswers($source, $target)
    {
        return $this->buildRelationship($source, $target, 'ANSWERS');
    }

    public function buildIsAnswerOf($source, $target)
    {
        return $this->buildRelationship($source, $target, 'IS_ANSWER_OF');
    }

    public function buildRelationship($source, $target, $type = null)
    {
        $relationship = array(
            'source' => $source,
            'target' => $target,
        );

        if ($type) {
            $relationship['type'] = $type;
        }

        return $relationship;
    }

    public function buildLink(array $link)
    {
        return array(
            'id' => $link['id'],
            'url' => $link['url'],
            'label' => 'Link',
        );
    }

    public function buildUser(array $user)
    {
        return array(
            'id' => $user['id'],
            'username' => $user['username'],
            'label' => 'User',
        );
    }

    public function buildQuestion(array $question)
    {
        return array(
            'id' => $question['id'],
            'text' => $question['text'],
            'label' => 'Question'
        );
    }

    public function buildAnswersData(array $question)
    {
        $answers = $question['answers'];

        foreach ($answers as &$answer)
        {
            $answer['label'] = 'Answer';
        }

        return $answers;
    }
}