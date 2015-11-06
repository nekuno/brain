<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Worker;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Service\SocialNetwork;

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


    public function __construct(AMQPChannel $channel, SocialNetwork $sn)
    {
        $this->channel = $channel;
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

        switch($trigger) {
            case 'added':
                $this->sn->setSocialNetworksInfo($userId, $socialNetworks, $this->logger);
                break;
            default;
                throw new \Exception('Invalid social network trigger');
        }

        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);

        $this->memory();
    }
}
