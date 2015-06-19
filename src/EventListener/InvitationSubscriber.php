<?php

/**
 * Created by Manolo Salsas (manolez@gmail.com)
 */

namespace EventListener;

use Model\User\AnswerModel;
use Model\User\InvitationModel;
use Event\AnswerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InvitationSubscriber implements EventSubscriberInterface
{
    /**
     * @var AnswerModel
     */

    const INVITATIONS_PER_HUNDRED_ANSWERS = 10;

    protected $answerModel;

    public function __construct(AnswerModel $answerModel, InvitationModel $invitationModel)
    {
        $this->answerModel = $answerModel;
        $this->invitationModel = $invitationModel;
    }

    public static function getSubscribedEvents()
    {
        return array(
            \AppEvents::ANSWER_ADDED => array('onAnswerAdded'),
        );
    }

    public function onAnswerAdded(AnswerEvent $event)
    {

        $user = $event->getUser();

        $userAnswersCount = $this->answerModel->getNumberOfUserAnswers($user->getId())->offsetGet('nOfAnswers');

        if($userAnswersCount['nOfAnswers'] % 100 === 0) {
            $this->invitationModel->addUserAvailable($user->getId(), self::INVITATIONS_PER_HUNDRED_ANSWERS);
        }
    }
}
