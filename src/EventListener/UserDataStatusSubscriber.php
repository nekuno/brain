<?php


namespace EventListener;

use AppEvents;
use Doctrine\ORM\EntityManager;
use Event\UserDataEvent;
use Model\Entity\DataStatus;
use PhpAmqpLib\Connection\AMQPConnection;
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
     * @var AMQPConnection
     */
    protected $connection;

    /**
     * @param EntityManager $entityManager
     * @param AMQPConnection $connection
     */
    public function __construct(EntityManager $entityManager, AMQPConnection $connection)
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
     */
    public function onUserDataFetchFinish(UserDataEvent $event)
    {

        $dataStatus = $this->getCurrentDataStatus($event);

        $dataStatus->setFetched(true);

        $this->saveStatus($dataStatus);

        $this->enqueueMatchingCalculation($event);
    }

    /**
     * @param UserDataEvent $event
     */
    private function enqueueMatchingCalculation(UserDataEvent $event)
    {

        $user = $event->getUser();
        $resourceOwner = $event->getResourceOwner();

        $data = array(
            'userId' => $user['id'],
            'resourceOwner' => $resourceOwner,
            'type' => 'process_finished',
        );

        $message = new AMQPMessage(json_encode($data, JSON_UNESCAPED_UNICODE));

        $exchangeName = 'brain.topic';
        $exchangeType = 'topic';
        $routingKey = 'brain.matching.process';
        $topic = 'brain.matching.*';
        $queueName = 'brain.matching';

        $channel = $this->connection->channel();
        $channel->exchange_declare($exchangeName, $exchangeType, false, true, false);
        $channel->queue_declare($queueName, false, true, false, false);
        $channel->queue_bind($queueName, $exchangeName, $topic);
        $channel->basic_publish($message, $exchangeName, $routingKey);
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
}
