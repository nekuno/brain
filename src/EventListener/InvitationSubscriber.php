<?php

namespace EventListener;

use Model\Neo4j\GraphManager;
use Everyman\Neo4j\Query\Row;
use Event\AnswerEvent;
use Model\User\InvitationModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class InvitationSubscriber
 * @package EventListener
 */
class InvitationSubscriber implements EventSubscriberInterface
{
    const INVITATIONS_PER_HUNDRED_ANSWERS = 10;

    protected $gm;
    protected $im;

    public function __construct(GraphManager $gm, InvitationModel $im)
    {
        $this->gm = $gm;
        $this->im = $im;
    }

    public static function getSubscribedEvents()
    {
        return array(
            \AppEvents::ANSWER_ADDED => array('onAnswerAdded'),
        );
    }

    public function onAnswerAdded(AnswerEvent $event)
    {
        $userId = $event->getUser();

        $userAnswersCount = $this->getNumberOfUserAnswers($userId);

        if((int)$userAnswersCount === 1) {
            $this->addUserAvailable($userId, self::INVITATIONS_PER_HUNDRED_ANSWERS);
	        for ($i = 0; $i < self::INVITATIONS_PER_HUNDRED_ANSWERS; $i++) {
	        	$this->im->create(array(
	        		'userId' => $userId,
			        'available' => 1,
		        ));
	        }
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
            ->set('u.available_invitations = coalesce(u.available_invitations, 0) + { nOfAvailable }')
            ->setParameters(array(
                'nOfAvailable' => (integer)$nOfAvailable,
                'userId' => (integer)$userId,
            ));

        $query = $qb->getQuery();

        $query->getResultSet();
    }

    private function getNumberOfUserAnswers($userId)
    {
        $count = 0;

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(a:Answer)<-[ua:ANSWERS]-(u:User)')
            ->where('u.qnoow_id = { userId }')
            ->setParameter('userId', (integer)$userId)
            ->returns('count(ua) AS nOfAnswers');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if ($result->count() > 0) {
            /* @var $row Row */
            $row = $result->current();
            $count = $row->offsetGet('nOfAnswers');
        }

        return $count;
    }
}
