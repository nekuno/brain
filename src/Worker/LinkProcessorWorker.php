<?php


namespace Worker;

use ApiConsumer\Fetcher\FetcherService;
use Doctrine\DBAL\Connection;
use Model\User\LookUpModel;
use Model\User\TokensModel;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class LinkProcessorWorker
 * @package Worker
 */
class LinkProcessorWorker extends LoggerAwareWorker implements RabbitMQConsumerInterface
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
    protected $connectionSocial;

    /**
     * @var Connection
     */
    protected $connectionBrain;

    public function __construct(AMQPChannel $channel, FetcherService $fetcherService, TokensModel $tm, LookUpModel $lm, Connection $connectionSocial, Connection $connectionBrain)
    {

        $this->channel = $channel;
        $this->fetcherService = $fetcherService;
        $this->tm = $tm;
        $this->lookupModel = $lm;
        $this->connectionSocial = $connectionSocial;
        $this->connectionBrain = $connectionBrain;
    }

    /**
     * { @inheritdoc }
     */
    public function consume()
    {

        $exchangeName = 'brain.topic';
        $exchangeType = 'topic';
        $topic = 'brain.fetching.*';
        $queueName = 'brain.fetching';

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

        // Verify mysql connections are alive
        if ($this->connectionSocial->ping() === false) {
            $this->connectionSocial->close();
            $this->connectionSocial->connect();
        }

        if ($this->connectionBrain->ping() === false) {
            $this->connectionBrain->close();
            $this->connectionBrain->connect();
        }

        $data = json_decode($message->body, true);
        $resourceOwner = $data['resourceOwner'];
        $userId = $data['userId'];
        $public = array_key_exists('public', $data)? $data['public'] : false;

        if ((!array_key_exists('public', $data) && $data['public'] == true)){
            $tokens = $this->tm->getByUserOrResource($userId, $resourceOwner);
        } else {
            $tokens = $this->lookupModel->getSocialProfiles($userId, $resourceOwner);
        }

        foreach ($tokens as $token){

            try {
                $this->fetcherService->fetch($token, $public);
            } catch (\Exception $e) {
                $this->logger->error(sprintf('Worker -> %s', $e->getMessage()));
            }
        }

        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);

        $this->memory();
    }

}
