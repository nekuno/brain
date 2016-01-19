<?php


namespace Worker;

use ApiConsumer\Fetcher\FetcherService;
use Doctrine\DBAL\Connection;
use Model\Neo4j\Neo4jException;
use Model\User\LookUpModel;
use Model\User\SocialNetwork\SocialProfileManager;
use Model\User\TokensModel;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

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
     * @var TokensModel
     */
    protected $tm;

    /**
     * @var LookupModel
     */
    protected $lookupModel;

    /**
     * @var FetcherService
     */
    protected $fetcherService;

    /**
     * @var Connection
     */
    protected $connectionBrain;

    /**
     * @var SocialProfileManager
     */
    protected $socialProfileManager;

    public function __construct(AMQPChannel $channel,
                                FetcherService $fetcherService,
                                TokensModel $tm,
                                LookUpModel $lm,
                                SocialProfileManager $socialProfileManager,
                                Connection $connectionBrain)
    {

        $this->channel = $channel;
        $this->fetcherService = $fetcherService;
        $this->tm = $tm;
        $this->lookupModel = $lm;
        $this->socialProfileManager = $socialProfileManager;
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

            switch($this->getTrigger($message)){
                case 'twitter_profile':
                    $username=$data['username'];
                    break;
                default:
                    break;
            }
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Worker: Error fetching for user %d with message %s on file %s, line %d', $userId, $e->getMessage(), $e->getFile(), $e->getLine()));
            if ($e instanceof Neo4jException) {
                $this->logger->error(sprintf('Query: %s' . "\n" . 'Data: %s', $e->getQuery(), print_r($e->getData(), true)));
            }
        }

        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);

        $this->memory();
    }

}
