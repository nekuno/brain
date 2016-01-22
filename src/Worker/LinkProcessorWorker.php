<?php


namespace Worker;

use ApiConsumer\Fetcher\FetcherService;
use Doctrine\DBAL\Connection;
use Http\OAuth\Factory\ResourceOwnerFactory;
use Http\OAuth\ResourceOwner\TwitterResourceOwner;
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

    /**
     * @var SocialProfileManager
     */
    protected $socialProfileManager;

    /**
     * @var ResourceOwnerFactory
     */
    protected $resourceOwnerFactory;

    public function __construct(AMQPChannel $channel,
                                FetcherService $fetcherService,
                                ResourceOwnerFactory $resourceOwnerFactory,
                                TokensModel $tm,
                                LookUpModel $lm,
                                SocialProfileManager $socialProfileManager,
                                Connection $connectionSocial,
                                Connection $connectionBrain)
    {

        $this->channel = $channel;
        $this->fetcherService = $fetcherService;
        $this->resourceOwnerFactory = $resourceOwnerFactory;
        $this->tm = $tm;
        $this->lookupModel = $lm;
        $this->socialProfileManager = $socialProfileManager;
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
        $public = array_key_exists('public', $data) ? $data['public'] : false;

        try {

            if (!(array_key_exists('public', $data) && $data['public'] == true)) {
                $tokens = $this->tm->getByUserOrResource($userId, $resourceOwner);
            } else {
                $profiles = $this->socialProfileManager->getSocialProfiles($userId, $resourceOwner, false);
                $tokens = array();
                foreach ($profiles as $profile) {
                    $tokens[] = $this->tm->buildFromSocialProfile($profile);
                }
            }

            foreach ($tokens as $token) {

                $token['public'] = $public;
                $this->fetcherService->fetch($token);

                if ($resourceOwner === TokensModel::TWITTER) {

                    $profiles = $this->socialProfileManager->getSocialProfiles($userId, $resourceOwner, true);
                    foreach ($profiles as $profile) {

                        /** @var TwitterResourceOwner $twitterResourceOwner */
                        $twitterResourceOwner = $this->resourceOwnerFactory->build($resourceOwner);
                        $twitterResourceOwner->dispatchChannel(array(
                            'url' => $profile->getUrl(),
                            'username' => $profile->getUserId(),
                        ));
                    };
                }
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
