<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Worker;

use Model\User\SocialNetwork\LinkedinSocialNetworkModel;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

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
     * @var LinkedinSocialNetworkModel
     */
    protected $lm;


    public function __construct(AMQPChannel $channel, LinkedinSocialNetworkModel $lm)
    {

        $this->channel = $channel;
        $this->lm = $lm;
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

        $userId = $data['userId'];
        $profileUrl = $data['profileUrl'];

        switch($trigger) {
            case 'linkedin':
                $this->lm->set($userId, $profileUrl);
                break;
            default;
                throw new \Exception('Invalid social network trigger');
        }

        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);

        $this->memory();
    }

}
