<?php

namespace Service\Validator;

use Model\Exception\ErrorList;
use Model\Question\Answer;

class AnswerValidator extends Validator
{
    public function validateOnCreate($data)
    {
        $this->validate($data);
    }

    public function validateOnUpdate($data)
    {
        $this->validateExpired($data);
        $this->validate($data);
    }

    protected function validate($data)
    {
        $this->validateUserInData($data);

        foreach ($data['acceptedAnswers'] as $acceptedAnswer) {
            if (!is_int($acceptedAnswer)) {
                $errorList = new ErrorList();
                $errorList->addError('acceptedAnswers', 'acceptedAnswers items must be integers');
                $this->throwException($errorList);
            }
            $this->validateAnswerId($data['questionId'], $acceptedAnswer);
        }

        $metadata = $this->metadata;
        return $this->validateMetadata($data, $metadata);
    }

    public function validateExpired($data)
    {
        /** @var Answer $answer */
        $answer = $data['userAnswer'];
        if (!$answer->isEditable()) {
            $errorList = new ErrorList();
            $errorList->addError('answer', sprintf('This answer cannot be edited now. Please wait %s seconds', $answer->getEditableIn()));
            $this->throwException($errorList);
        }
    }

    protected function validateAnswerId($questionId, $answerId, $desired = true)
    {
        $errorList = new ErrorList();
        $errorList->setErrors('answerId', $this->existenceValidator->validateAnswerId($questionId, $answerId, $desired));

        $this->throwException($errorList);
    }

    public function validateQuestionId($questionId)
    {
        $errorList = new ErrorList();
        $errorList->setErrors('questionId', $this->existenceValidator->validateQuestionId($questionId));

        $this->throwException($errorList);
    }
}