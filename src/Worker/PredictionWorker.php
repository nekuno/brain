<?php


namespace Worker;

use Model\LinkModel;
use Model\User\Affinity\AffinityModel;
use Model\User\Similarity\SimilarityModel;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Service\AffinityRecalculations;

/**
 * Class AffinityCalculatorWorker
 * @package Worker
 */
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
                                AffinityRecalculations $affinityRecalculations,
                                AffinityModel $affinityModel,
                                LinkModel $linkModel)
    {
        $this->channel = $channel;

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
                $this->affinityRecalculations->recalculateAffinities($userId, 100, 20);
                break;
            case $this::TRIGGER_LIVE:

                $links = $this->linkModel->getLivePredictedContent($userId);
                foreach ($links as $link){
                    $affinity = $this->affinityModel->getAffinity($userId, $link['content']['id']);
                    $this->logger->info(sprintf('Affinity between user %s and link %s: %s',$userId,$link['content']['id'], $affinity['affinity']));
                }

                break;
            default;
                throw new \Exception('Invalid affinity calculation trigger: '.$trigger);
        }

        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);

        $this->memory();
    }

}
