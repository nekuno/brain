<?php

/**
 * Created by Manolo Salsas (manolez@gmail.com)
 */

namespace EventListener;

use Model\Neo4j\GraphManager;
use Model\User\AnswerModel;
use Event\AnswerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InvitationSubscriber implements EventSubscriberInterface
{
    /**
     * @var AnswerModel
     */

    const INVITATIONS_PER_HUNDRED_ANSWERS = 10;

    protected $gm;

    public function __construct(GraphManager $gm)
    {
        $this->gm = $gm;
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

        $userAnswersCount = $this->getNumberOfUserAnswers($user->getId());

        if($userAnswersCount['nOfAnswers'] % 100 === 0) {
            $this->addUserAvailable($user->getId(), self::INVITATIONS_PER_HUNDRED_ANSWERS);
        }
    }

    private function addUserAvailable($userId, $nOfAvailable)
    {
        if((string)$nOfAvailable !== (string)(int)$nOfAvailable) {
            throw new \RuntimeException('nOfAvailable must be an integer');
        }
        if((string)$userId !== (string)(int)$userId) {
            throw new \RuntimeException('userId ID must be an integer');
        }

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User)')
            ->where('u.qnoow_id = { userId }')
            ->set('u.available_invitations = u.available_invitations + { nOfAvailable }')
            ->setParameters(array(
                'nOfAvailable' => (integer)$nOfAvailable,
                'userId' => (integer)$userId,
            ));

        $query = $qb->getQuery();

        $query->getResultSet();
    }

    private function getNumberOfUserAnswers($userId)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(a:Answer)<-[ua:ANSWERS]-(u:User)')
            ->where('u.qnoow_id = { userId }')
            ->setParameter('userId', (integer)$userId)
            ->returns('count(ua) AS nOfAnswers');

        $query = $qb->getQuery();

        return $query->getResultSet()->offsetGet('nOfAnswers');
    }
}
