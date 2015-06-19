<?php

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
        $availableInvitations = $this->invitationModel->getUserAvailable($user->getId());

        $invitationsShouldHave = $userAnswersCount['nOfAnswers'] / 10;

        if (($newInvitations = $invitationsShouldHave - $availableInvitations) > 0) {
            if ($newInvitations >= 10) {
                $this->invitationModel->setUserAvailable($user->getId(), $invitationsShouldHave);
            }
        }
    }
}
