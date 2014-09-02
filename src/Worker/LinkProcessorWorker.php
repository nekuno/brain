<?php


namespace Worker;

use ApiConsumer\Auth\UserProviderInterface;
use ApiConsumer\Fetcher\FetcherService;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class LinkProcessorWorker
 * @package Worker
 */
class LinkProcessorWorker
{

    /**
     * @var AMQPChannel
     */
    protected $channel;

    /**
     * @var UserProviderInterface
     */
    protected $userProvider;

    /**
     * @var FetcherService
     */
    protected $fetcherService;

    /**
     * @var array
     */
    protected $config;

    /**
     * @param AMQPChannel $channel
     * @param FetcherService $fetcherService
     * @param UserProviderInterface $userProvider
     * @param array $config
     */
    public function __construct(
        AMQPChannel $channel,
        FetcherService $fetcherService,
        UserProviderInterface $userProvider,
        $config = array()
    ) {

        $this->channel = $channel;
        $this->fetcherService = $fetcherService;
        $this->userProvider = $userProvider;
        $this->config = $config;
    }

    /**
     * Consume brain.fetching queue
     */
    public function consume()
    {
        $exchangeName = 'brain.direct';
        $exchangeType = 'direct';
        $routingKey = 'brain.fetching.links';
        $queueName = 'brain.fetching';
        $this->channel->exchange_declare($exchangeName, $exchangeType, false, true, false);
        $this->channel->queue_declare($queueName, false, true, false, false);
        $this->channel->queue_bind($queueName, $exchangeName, $routingKey);
        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume(
            $queueName,
            '',
            false,
            false,
            false,
            false,
            array($this, 'callback')
        );

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }

    }

    /**
     * @param AMQPMessage $message
     * @throws \Exception
     */
    public function callback(AMQPMessage $message)
    {

        $data = json_decode($message->body, true);
        $resourceOwner = $data['resourceOwner'];
        $userId = $data['userId'];

        $user = $this->userProvider->getUsersByResource(
            $resourceOwner,
            $userId
        );

        if (!$user) {
            // TODO: handle this
        }

        $this->fetcherService->fetch($userId, $resourceOwner);

        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
    }
}
