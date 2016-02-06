<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Worker;

use Model\Neo4j\Neo4jException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Service\SocialNetwork;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class SocialNetworkDataProcessorWorker
 * @package Worker
 */
class SocialNetworkDataProcessorWorker extends LoggerAwareWorker implements RabbitMQConsumerInterface
{

    /**
     * @var AMQPChannel
     */
    protected $channel;

    /**
     * @var SocialNetwork
     */
    protected $sn;


    public function __construct(AMQPChannel $channel, EventDispatcher $dispatcher, SocialNetwork $sn)
    {
        $this->channel = $channel;
        $this->dispatcher = $dispatcher;
        $this->sn = $sn;
    }

    /**
     * { @inheritdoc }
     */
    public function consume()
    {

        $exchangeName = 'brain.topic';
        $exchangeType = 'topic';
        $topic = 'brain.social_network.*';
        $queueName = 'brain.social_network';

        $this->channel->exchange_declare($exchangeName, $exchangeType, false, true, false);
        $this->channel->queue_declare($queueName, false, true, false, false);
        $this->channel->queue_bind($queueName, $exchangeName, $topic);
        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume($queueName, '', false, false, false, false, array($this, 'callback'));

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }

    }

    /**
     * { @inheritdoc }
     */
    public function callback(AMQPMessage $message)
    {

        $data = json_decode($message->body, true);

        $trigger = $this->getTrigger($message);

        $userId = $data['id'];
        $socialNetworks = $data['socialNetworks'];
        try {
            switch ($trigger) {
                case 'added':
                    $this->sn->setSocialNetworksInfo($userId, $socialNetworks, $this->logger);
                    break;
                default;
                    throw new \Exception('Invalid social network trigger');
            }
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Worker: Error fetching for user %d with message %s on file %s, line %d', $userId, $e->getMessage(), $e->getFile(), $e->getLine()));
            if ($e instanceof Neo4jException) {
                $this->logger->error(sprintf('Query: %s' . "\n" . 'Data: %s', $e->getQuery(), print_r($e->getData(), true)));
            }
            $this->dispatchError($e, 'Social network trigger');
        }
        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);

        $this->memory();
    }
}
