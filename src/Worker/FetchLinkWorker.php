<?php


namespace Worker;

use ApiConsumer\Auth\UserProviderInterface;
use ApiConsumer\Fetcher\FetcherService;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

class FetchLinkWorker implements RabbitMQConsumerInterface
{

    /**
     * @var array
     */
    protected $config;

    /**
     * @var UserProviderInterface
     */
    protected $userProvider;

    /**
     * @var FetcherService
     */
    protected $fetcherBuilder;

    /**
     * @var AMQPChannel
     */
    protected $channel;

    /**
     * @param AMQPChannel $channel
     * @param FetcherService $fetcher
     * @param UserProviderInterface $userProvider
     * @param array $config
     */
    public function __construct(
        AMQPChannel $channel,
        FetcherService $fetcher,
        UserProviderInterface $userProvider,
        $config = array()
    ) {

        $this->channel = $channel;
        $this->fetcherBuilder = $fetcher;
        $this->userProvider = $userProvider;
        $this->config = $config;
    }

    public function consume($exchange = 'social', $queue = 'fetch')
    {

        $this->channel->exchange_declare($exchange, 'direct', false, true, false);
        $this->channel->queue_declare($queue, false, true, false, false);
        $this->channel->queue_bind($queue, $exchange);
        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume(
            $queue,
            '',
            false,
            false,
            false,
            false,
            function (AMQPMessage $message) {

                $messageBody = unserialize($message->body);
                $resourceOwner = $messageBody['resourceOwner'];
                $userId = $messageBody['userId'];

                $user = $this->userProvider->getUsersByResource(
                    $resourceOwner,
                    $userId
                );

                $this->fetcherBuilder->fetch($user['id'], $resourceOwner);

                $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
            }
        );

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }

    }
}
