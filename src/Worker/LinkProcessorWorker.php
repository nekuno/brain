<?php


namespace Worker;

use ApiConsumer\Fetcher\FetcherService;
use ApiConsumer\Fetcher\ProcessorService;
use Doctrine\DBAL\Connection;
use ApiConsumer\Factory\ResourceOwnerFactory;
use ApiConsumer\ResourceOwner\TwitterResourceOwner;
use Model\Neo4j\Neo4jException;
use Model\User\SocialNetwork\SocialProfileManager;
use Model\User\TokensModel;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\EventDispatcher\EventDispatcher;

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
     * @var FetcherService
     */
    protected $fetcherService;

    protected $processorService;

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
                                EventDispatcher $dispatcher,
                                FetcherService $fetcherService,
                                ProcessorService $processorService,
                                ResourceOwnerFactory $resourceOwnerFactory,
                                TokensModel $tm,
                                SocialProfileManager $socialProfileManager,
                                Connection $connectionSocial,
                                Connection $connectionBrain)
    {
        $this->channel = $channel;
        $this->dispatcher = $dispatcher;
        $this->fetcherService = $fetcherService;
        $this->processorService = $processorService;
        $this->resourceOwnerFactory = $resourceOwnerFactory;
        $this->tm = $tm;
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
        $exclude = array_key_exists('exclude', $data) ? $data['exclude'] : array();

        try {

            if (!$public) {
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
                $links = $this->fetcherService->fetch($token, $exclude);
                $this->processorService->process($links, $userId);

                if ($resourceOwner === TokensModel::TWITTER) {

                    $profiles = $this->socialProfileManager->getSocialProfiles($userId, $resourceOwner, true);
                    foreach ($profiles as $profile) {

                        /** @var TwitterResourceOwner $twitterResourceOwner */
                        $twitterResourceOwner = $this->resourceOwnerFactory->build($resourceOwner);
                        $username = $twitterResourceOwner->getUsername(array('url' => $profile->getUrl()));
//                        try{
//                            $twitterResourceOwner->dispatchChannel(array(
//                                'url' => $profile->getUrl(),
//                                'username' => $username,
//                            ));
//                        } catch (\Exception $e){
//                            $this->dispatchError($e, 'Error adding twitter channel');
//                            $this->logger->error('Error adding twitter channel: '. $e->getMessage());
//                        }

                        $this->logger->info(sprintf('Enqueued fetching old tweets for username %s', $username));
                    };
                }
            }

        } catch (\Exception $e) {
            $this->logger->error(sprintf('Worker: Error fetching for user %d with message %s on file %s, line %d', $userId, $e->getMessage(), $e->getFile(), $e->getLine()));
            if ($e instanceof Neo4jException) {
                $this->logger->error(sprintf('Query: %s' . "\n" . 'Data: %s', $e->getQuery(), print_r($e->getData(), true)));
            }
            $this->dispatchError($e, 'Fetching');
        }

        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);

        $this->memory();
    }

}
