<?php


namespace EventListener;

use Doctrine\ORM\EntityManager;
use Event\StatusEvent;
use Model\Entity\UserDataStatus;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
            \AppEvents::USER_DATA_FETCH_START => array('onUserDataFetchStart'),
            \AppEvents::USER_DATA_FETCH_FINISH => array('onUserDataFetchFinish'),
            \AppEvents::USER_DATA_PROCESS_START => array('onUserDataProcessStart'),
            \AppEvents::USER_DATA_PROCESS_FINISH => array('onUserDataProcessFinish'),
        );
    }

    public function onUserDataFetchFinish(StatusEvent $event)
    {

        $user = $event->getUser();
        $resourceOwner = $event->getResourceOwner();

        

        $this->enqueueMatchingCalculation($user, $resourceOwner);

    }

    /**
     * @param $user
     * @param $resourceOwner
     */
    private function enqueueMatchingCalculation($user, $resourceOwner)
    {

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

}
