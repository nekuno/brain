<?php


namespace EventListener;

use AppEvents;
use Doctrine\ORM\EntityManager;
use Event\MatchingExpiredEvent;
use Event\UserDataEvent;
use Model\Entity\DataStatus;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
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
     * @var AMQPStreamConnection
     */
    protected $connection;

    /**
     * @param EntityManager $entityManager
     * @param AMQPStreamConnection $connection
     */
    public function __construct(EntityManager $entityManager, AMQPStreamConnection $connection)
    {

        $this->entityManager = $entityManager;
        $this->connection = $connection;
    }

    /**
     * { @inheritdoc }
     */
    public static function getSubscribedEvents()
    {

        return array(
            AppEvents::USER_DATA_FETCHING_START => array('onUserDataFetchStart'),
            AppEvents::USER_DATA_FETCHING_FINISH => array('onUserDataFetchFinish'),
            AppEvents::USER_DATA_PROCESS_START => array('onUserDataProcessStart'),
            AppEvents::USER_DATA_PROCESS_FINISH => array('onUserDataProcessFinish'),
            AppEvents::USER_MATCHING_EXPIRED => array('onUserMatchingExpired'),
            AppEvents::USER_DATA_CONTENT_RATED => array('onContentRated'),
        );
    }

    /**
     * @param UserDataEvent $event
     */
    public function onUserDataFetchStart(UserDataEvent $event)
    {

        $status = $this->getCurrentDataStatus($event);

        $status->setFetched(false);

        $this->saveStatus($status);
    }

    /**
     * @param UserDataEvent $event
     * @return \Model\Entity\DataStatus
     */
    public function getCurrentDataStatus(UserDataEvent $event)
    {

        $user = $event->getUser();
        $resourceOwner = $event->getResourceOwner();

        $repository = $this->entityManager->getRepository('\Model\Entity\DataStatus');
        $dataStatus = $repository->findOneBy(array('userId' => $user['id'], 'resourceOwner' => $resourceOwner));

        if (null === $dataStatus) {
            $dataStatus = new DataStatus();
            $dataStatus->setUserId($user['id']);
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

    /**
     * @param UserDataEvent $event
     */
    public function onUserDataFetchFinish(UserDataEvent $event)
    {

        $dataStatus = $this->getCurrentDataStatus($event);

        $dataStatus->setFetched(true);

        $this->saveStatus($dataStatus);

    }

    /**
     * @param UserDataEvent $event
     */
    public function onUserDataProcessStart(UserDataEvent $event)
    {

        $status = $this->getCurrentDataStatus($event);

        $status->setProcessed(false);

        $this->saveStatus($status);
    }

    /**
     * @param UserDataEvent $event
     */
    public function onUserDataProcessFinish(UserDataEvent $event)
    {

        $status = $this->getCurrentDataStatus($event);

        $status->setProcessed(true);

        $this->saveStatus($status);

        $user = $event->getUser();
        $resourceOwner = $event->getResourceOwner();

        $data = array(
            'userId' => $user['id'],
            'resourceOwner' => $resourceOwner,
            'trigger' => 'process_finished',
        );

        $this->enqueueMatchingCalculation($data, 'brain.matching.process');
    }

    /**
     * @param MatchingExpiredEvent $event
     */
    public function onUserMatchingExpired(MatchingExpiredEvent $event)
    {

        $data = array(
            'trigger' => 'matching_expired',
            'user_1_id' => $event->getUser1(),
            'user_2_id' => $event->getUser2(),
            'matching_type' => $event->getType(),
        );

        $this->enqueueMatchingCalculation($data, 'brain.matching.matching_expired');

    }

    public function onContentRated(UserDataEvent $event)
    {

        $data = array(
            'trigger' => 'content_rated',
            'userId' => $event->getUser(),
        );

        $this->enqueueMatchingCalculation($data, 'brain.matching.content_rated');
    }

    /**
     * @param $data
     * @param $routingKey
     */
    private function enqueueMatchingCalculation($data, $routingKey)
    {

        $message = new AMQPMessage(json_encode($data, JSON_UNESCAPED_UNICODE));

        $exchangeName = 'brain.topic';
        $exchangeType = 'topic';
        $topic = 'brain.matching.*';
        $queueName = 'brain.matching';

        $channel = $this->connection->channel();
        $channel->exchange_declare($exchangeName, $exchangeType, false, true, false);
        $channel->queue_declare($queueName, false, true, false, false);
        $channel->queue_bind($queueName, $exchangeName, $topic);
        $channel->basic_publish($message, $exchangeName, $routingKey);
    }

}
