<?php


namespace Worker;

use ApiConsumer\Fetcher\FetcherService;
use ApiConsumer\Fetcher\GetOldTweets\GetOldTweets;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use Doctrine\DBAL\Connection;
use Model\Neo4j\Neo4jException;
use Model\User\TokensModel;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class LinkProcessorWorker
 * @package Worker
 */
class ChannelWorker extends LoggerAwareWorker implements RabbitMQConsumerInterface
{

    /**
     * @var AMQPChannel
     */
    protected $channel;

    /**
     * @var FetcherService
     */
    protected $fetcherService;

    /**
     * @var Connection
     */
    protected $connectionBrain;

    /**
     * @var GetOldTweets
     */
    protected $getOldTweets;

    public function __construct(AMQPChannel $channel,
                                EventDispatcher $dispatcher,
                                FetcherService $fetcherService,
                                GetOldTweets $getOldTweets,
                                Connection $connectionBrain)
    {

        $this->channel = $channel;
        $this->dispatcher = $dispatcher;
        $this->fetcherService = $fetcherService;
        $this->getOldTweets = $getOldTweets;
        $this->connectionBrain = $connectionBrain;
    }

    /**
     * { @inheritdoc }
     */
    public function consume()
    {

        $exchangeName = 'brain.topic';
        $exchangeType = 'topic';
        $topic = 'brain.channel.*';
        $queueName = 'brain.channel';

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

        if ($this->connectionBrain->ping() === false) {
            $this->connectionBrain->close();
            $this->connectionBrain->connect();
        }


        try{
            $data = json_decode($message->body, true);

            if (!isset($data['userId'])){
                throw new \Exception('Enqueued message does not include userId parameter');
            }
            $userId = $data['userId'];

            if (!isset($data['resourceOwner'])){
                throw new \Exception('Enqueued message does not include resourceOwner parameter');
            }
            $resourceOwner = $data['resourceOwner'];

            switch($resourceOwner){
                case TokensModel::TWITTER:

                    if (!isset($data['username'])){
                        throw new \Exception('Enqueued message does not include  username parameter');
                    }
                    $username = $data['username'];
                    $this->logger->info(sprintf('Using GetOldTweets to fetch from %s', $username));

                    $links = $this->getOldTweets->fetchFromUser($username);
                    $this->logger->info(sprintf('Total %d links fetched from tweets from %s',count($links), $username));
                    break;
                default:
                    throw new \Exception('Resource %s not supported in this queue', $resourceOwner);
            }
            $this->logger->info(sprintf('Start processing %d links for user %d', count($links), $userId));

            $preprocessedLinks = array();
            foreach ($links as $link)
            {
                $preprocessedLink = new PreprocessedLink($link['url']);
                $preprocessedLink->setLink($link);
                $preprocessedLink->setSource($resourceOwner);
                $preprocessedLinks[] = $preprocessedLink;
            }

            $processedLinks = $this->fetcherService->processLinks($preprocessedLinks, $userId);

            $this->logger->info(sprintf('Processed %d links for user %d', count($processedLinks), $userId));

        } catch (\Exception $e) {
            $this->logger->error(sprintf('Worker: Error fetching for channel with message %s on file %s, line %d', $e->getMessage(), $e->getFile(), $e->getLine()));
            if ($e instanceof Neo4jException) {
                $this->logger->error(sprintf('Query: %s' . "\n" . 'Data: %s', $e->getQuery(), print_r($e->getData(), true)));
            }
            $this->dispatchError($e, 'Channel fetching');
        }

        /** @var AMQPChannel $channel */
        $channel = $message->delivery_info['channel'];
        $channel->basic_ack($message->delivery_info['delivery_tag']);

        $this->memory();
    }

}
