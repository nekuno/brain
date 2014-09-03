<?php


namespace EventListener;

use Doctrine\ORM\EntityManager;
use Event\StatusEvent;
use Model\Entity\DataStatus;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class StatusSubscriber
 * @package EventListener
 */
class StatusSubscriber implements EventSubscriberInterface
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
            \StatusEvents::USER_DATA_FETCHING_START => array('onUserDataFetchStart'),
            \StatusEvents::USER_DATA_FETCHING_FINISH => array('onUserDataFetchFinish'),
            \StatusEvents::USER_DATA_PROCESS_START => array('onUserDataProcessStart'),
            \StatusEvents::USER_DATA_PROCESS_FINISH => array('onUserDataProcessFinish'),
        );
    }

    /**
     * @param StatusEvent $event
     */
    public function onUserDataFetchStart(StatusEvent $event)
    {

        $status = $this->getCurrentDataStatus($event);

        $status->setFetched(false);

        $this->saveStatus($status);
    }

    /**
     * @param StatusEvent $event
     */
    public function onUserDataFetchFinish(StatusEvent $event)
    {

        $dataStatus = $this->getCurrentDataStatus($event);

        $dataStatus->setFetched(true);

        $this->saveStatus($dataStatus);

        $this->enqueueMatchingCalculation($event);
    }

    /**
     * @param StatusEvent $event
     */
    private function enqueueMatchingCalculation(StatusEvent $event)
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
     * @param StatusEvent $event
     */
    public function onUserDataProcessStart(StatusEvent $event)
    {

        $status = $this->getCurrentDataStatus($event);

        $status->setProcessed(false);

        $this->saveStatus($status);
    }

    /**
     * @param StatusEvent $event
     */
    public function onUserDataProcessFinish(StatusEvent $event)
    {

        $status = $this->getCurrentDataStatus($event);

        $status->setProcessed(true);

        $this->saveStatus($status);
    }

    /**
     * @param StatusEvent $event
     * @return \Model\Entity\DataStatus
     */
    public function getCurrentDataStatus(StatusEvent $event)
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
