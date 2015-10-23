<?php


namespace EventListener;

use Doctrine\ORM\EntityManager;
use Event\FetchEvent;
use Event\MatchingExpiredEvent;
use Event\ProcessLinksEvent;
use Event\ContentRatedEvent;
use Model\Entity\DataStatus;
use Service\AMQPManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class UserDataStatusSubscriber
 * @package EventListener
 */
class UserDataStatusSubscriber implements EventSubscriberInterface
{

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var AMQPManager
     */
    protected $amqpManager;

    /**
     * @param EntityManager $entityManager
     * @param AMQPManager $amqpManager
     */
    public function __construct(EntityManager $entityManager, AMQPManager $amqpManager)
    {

        $this->entityManager = $entityManager;
        $this->amqpManager = $amqpManager;
    }

    /**
     * { @inheritdoc }
     */
    public static function getSubscribedEvents()
    {

        return array(
            \AppEvents::FETCH_START => array('onFetchStart'),
            \AppEvents::FETCH_FINISH => array('onFetchFinish'),
            \AppEvents::PROCESS_START => array('onProcessStart'),
            \AppEvents::PROCESS_FINISH => array('onProcessFinish'),
            \AppEvents::MATCHING_EXPIRED => array('onMatchingExpired'),
            \AppEvents::CONTENT_RATED => array('onContentRated'),
        );
    }

    public function onFetchStart(FetchEvent $event)
    {

        $status = $this->getCurrentDataStatus($event);

        $status->setFetched(false);

        $this->saveStatus($status);
    }

    public function getCurrentDataStatus(FetchEvent $event)
    {

        $user = $event->getUser();
        $resourceOwner = $event->getResourceOwner();

        if ($this->entityManager->getConnection()->ping() === false) {
            $this->entityManager->getConnection()->close();
            $this->entityManager->getConnection()->connect();
        }

        $repository = $this->entityManager->getRepository('\Model\Entity\DataStatus');
        $dataStatus = $repository->findOneBy(array('userId' => $user, 'resourceOwner' => $resourceOwner));

        if (null === $dataStatus) {
            $dataStatus = new DataStatus();
            $dataStatus->setUserId($user);
            $dataStatus->setResourceOwner($resourceOwner);
        }

        return $dataStatus;
    }

    /**
     * @param $status
     */
    public function saveStatus($status)
    {

        $this->entityManager->persist($status);
        $this->entityManager->flush();
    }

    public function onFetchFinish(FetchEvent $event)
    {

        $dataStatus = $this->getCurrentDataStatus($event);

        $dataStatus->setFetched(true);

        $this->saveStatus($dataStatus);

    }

    public function onProcessStart(ProcessLinksEvent $event)
    {

        $status = $this->getCurrentDataStatus($event);

        $status->setProcessed(false);

        $this->saveStatus($status);
    }

    public function onProcessFinish(ProcessLinksEvent $event)
    {

        $status = $this->getCurrentDataStatus($event);

        $status->setProcessed(true);

        $this->saveStatus($status);

        $user = $event->getUser();
        $resourceOwner = $event->getResourceOwner();

        $data = array(
            'userId' => $user,
            'resourceOwner' => $resourceOwner,
        );

        $this->amqpManager->enqueueMessage($data, 'brain.matching.process_finished');
    }

    public function onMatchingExpired(MatchingExpiredEvent $event)
    {

        $data = array(
            'user_1_id' => $event->getUser1(),
            'user_2_id' => $event->getUser2(),
            'matching_type' => $event->getType(),
        );

        $this->amqpManager->enqueueMessage($data, 'brain.matching.matching_expired');

    }

    public function onContentRated(ContentRatedEvent $event)
    {

        $data = array(
            'userId' => $event->getUser(),
        );

        $this->amqpManager->enqueueMessage($data, 'brain.matching.content_rated');
    }

}
