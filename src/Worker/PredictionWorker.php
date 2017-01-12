<?php


namespace Worker;

use Model\LinkModel;
use Model\Neo4j\Neo4jException;
use Model\User\Affinity\AffinityModel;
use Model\User\Similarity\SimilarityModel;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Service\AffinityRecalculations;
use Symfony\Component\EventDispatcher\EventDispatcher;


class PredictionWorker extends LoggerAwareWorker implements RabbitMQConsumerInterface
{

    /**
     * @var AMQPChannel
     */
    protected $channel;

    /**
     * @var AffinityRecalculations
     */
    protected $affinityRecalculations;

    /**
     * @var AffinityModel
     */
    protected $affinityModel;

    /**
     * @var LinkModel
     */
    protected $linkModel;

    /**
     * @var SimilarityModel
     */
    protected $similarityModel;

    const TRIGGER_RECALCULATE = 'recalculate';
    const TRIGGER_LIVE = 'live';

    public function __construct(AMQPChannel $channel,
                                EventDispatcher $dispatcher,
                                AffinityRecalculations $affinityRecalculations,
                                AffinityModel $affinityModel,
                                LinkModel $linkModel)
    {
        $this->channel = $channel;
        $this->dispatcher = $dispatcher;
        $this->linkModel = $linkModel;
        $this->affinityModel = $affinityModel;
        $this->affinityRecalculations = $affinityRecalculations;
    }

    /**
     * { @inheritdoc }
     */
    public function consume()
    {

        $exchangeName = 'brain.topic';
        $exchangeType = 'topic';
        $topic = 'brain.prediction.*';
        $queueName = 'brain.prediction';

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

        $data = json_decode($message->body, true);

        $userId = $data['userId'];

        $trigger = $this->getTrigger($message);

        switch ($trigger) {
            case $this::TRIGGER_RECALCULATE:
                try {
                    $this->affinityRecalculations->recalculateAffinities($userId, 100, 20);
                } catch (\Exception $e) {
                    $this->logger->error(sprintf('Worker: Error recalculating affinity for user %d with message %s on file %s, line %d', $userId, $e->getMessage(), $e->getFile(), $e->getLine()));
                    if ($e instanceof Neo4jException) {
                        $this->logger->error(sprintf('Query: %s' . "\n" . 'Data: %s', $e->getQuery(), print_r($e->getData(), true)));
                    }
                    $this->dispatchError($e, 'Affinity recalculating with trigger recalculate');
                }
                break;
            case $this::TRIGGER_LIVE:
                try {
                    $links = $this->linkModel->getLivePredictedContent($userId);
                    foreach ($links as $link) {
                        $affinity = $this->affinityModel->getAffinity($userId, $link->getContent()['id']);
                        $this->logger->info(sprintf('Affinity between user %s and link %s: %s', $userId, $link->getContent()['id'], $affinity['affinity']));
                    }
                } catch (\Exception $e) {
                    $this->logger->error(sprintf('Worker: Error calculating live affinity for user %d with message %s on file %s, line %d', $userId, $e->getMessage(), $e->getFile(), $e->getLine()));
                    if ($e instanceof Neo4jException) {
                        $this->logger->error(sprintf('Query: %s' . "\n" . 'Data: %s', $e->getQuery(), print_r($e->getData(), true)));
                    }
                    $this->dispatchError($e, 'Affinity recalculating with live trigger');
                }

                break;
            default;
                throw new \Exception('Invalid affinity calculation trigger: ' . $trigger);
        }

        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);

        $this->memory();
    }

}
