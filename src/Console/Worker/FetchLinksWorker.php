<?php


namespace Console\Worker;


use ApiConsumer\Auth\UserProviderInterface;
use ApiConsumer\Fetcher\FetcherService;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

class FetchLinksWorker
{

    protected $queue = 'fetch';

    protected $exchange = 'social';

    /**
     * @var UserProviderInterface
     */
    protected $userProvider;

    /**
     * @var FetcherService
     */
    protected $fetcher;

    /**
     * @var AMQPConnection
     */
    protected $connection;

    public function __construct(AMQPConnection $amqp, FetcherService $fetcher, UserProviderInterface $userProvider)
    {
        $this->connection = $amqp;
        $this->fetcher = $fetcher;
        $this->userProvider = $userProvider;
    }

    public function process()
    {

        $channel = $this->connection->channel();

        $channel->queue_declare($this->queue, false, true, false, false);

        $channel->queue_bind($this->queue, $this->exchange);

        $channel->basic_qos(null, 1, null);

        $channel->basic_consume($this->queue, '', false, false, false, false, array($this, 'processMessage'));

        while (count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();

    }

    public function processMessage(AMQPMessage $message)
    {
        $messageBody = unserialize($message->body);
        $resourceOwner = $messageBody['resourceOwner'];
        $userId = $messageBody['userId'];

        $user = $this->userProvider->getUsersByResource(
            $resourceOwner,
            $userId
        );



        $this->fetcher->fetch($user['id'], $resourceOwner);

        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
    }
}
