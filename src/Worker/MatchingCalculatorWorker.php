<?php


namespace Worker;

use Model\User\MatchingModel;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * Class MatchingCalculatorWorker
 * @package Worker
 */
class MatchingCalculatorWorker implements RabbitMQConsumerInterface, LoggerAwareInterface
{

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var AMQPChannel
     */
    protected $channel;

    protected $model;

    public function __construct(AMQPChannel $channel, MatchingModel $model)
    {

        $this->channel = $channel;
        $this->model = $model;
    }

    /**
     * { @inheritdoc }
     */
    public function consume()
    {

        $exchangeName = 'brain.topic';
        $exchangeType = 'topic';
        $topic = 'brain.matching.*';
        $queueName = 'brain.matching';

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

        $data = json_decode($message->body, true);

        $eventType = $data['type'];

        switch ($eventType) {
            case 'process_finished':
                try {
                    $this->model->recalculateMatchingByContentOfUserWhenNewContentIsAdded($data['userId']);
                } catch (\Exception $e) {
                    $this->logger->debug(
                        sprintf(
                            'Worker: Error calculating matching for user %d with message %s on file %s, line %d',
                            $data['userId'],
                            $e->getMessage(),
                            $e->getFile(),
                            $e->getLine()
                        )
                    );
                }

                break;
            case 'question_answered':
                try {

                    $this->model->recalculateMatchingOfUserByAnswersWhenNewQuestionsAreAnswered(
                        $data['user_id'],
                        array($data['question_id'])
                    );
                } catch (\Exception $e) {
                    $this->logger->debug(
                        sprintf(
                            'Worker: Error calculating matching for user %d with message %s on file %s, line %d',
                            $data['userId'],
                            $e->getMessage(),
                            $e->getFile(),
                            $e->getLine()
                        )
                    );
                }
                break;
            case 'content_rated':

                // TODO: handle this event
                break;
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
