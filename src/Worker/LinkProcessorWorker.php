<?php


namespace Worker;

use ApiConsumer\Auth\UserProviderInterface;
use ApiConsumer\Fetcher\FetcherService;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * Class LinkProcessorWorker
 * @package Worker
 */
class LinkProcessorWorker implements RabbitMQConsumerInterface, LoggerAwareInterface
{

    /**
     * @var LoggerInterface
     */
    protected $logger;

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
     * { @inheritdoc }
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
        $resourceOwner = $data['resourceOwner'];
        $userId = $data['userId'];

        $user = $this->userProvider->getUsersByResource($resourceOwner, $userId);

        if($user){
            try {
                $this->fetcherService->fetch($user['id'], $resourceOwner);
            } catch (\Exception $e) {
                $this->logger->error(
                    sprintf(
                        'Worker -> %s',
                        $e->getMessage()
                    )
                );
            }
        }

        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
    }

    /**
     * Sets a logger instance on the object
     *
     * @param LoggerInterface $logger
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {

        $this->logger = $logger;
    }
}
