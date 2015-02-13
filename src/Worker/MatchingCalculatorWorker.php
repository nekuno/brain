<?php


namespace Worker;

use Model\User\Matching\MatchingModel;
use Model\User\Similarity\SimilarityModel;
use Model\UserModel;
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

    /**
     * @var UserModel
     */
    protected $userModel;

    /**
     * @var MatchingModel
     */
    protected $matchingModel;

    /**
     * @var SimilarityModel
     */
    protected $similarityModel;

    public function __construct(AMQPChannel $channel, UserModel $userModel, MatchingModel $matchingModel, SimilarityModel $similarityModel)
    {

        $this->channel = $channel;
        $this->userModel = $userModel;
        $this->matchingModel = $matchingModel;
        $this->similarityModel = $similarityModel;
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

        $trigger = $data['trigger'];

        switch ($trigger) {
            case 'content_rated':
            case 'process_finished':

                $userA = $data['userId'];
                $this->logger->info(sprintf('Calculating matching by trigger "%s" for user "%s"', $trigger, $userA));

                try {
                    $usersWithSameContent = $this->userModel->getByCommonLinksWithUser($userA);

                    foreach ($usersWithSameContent as $currentUser) {
                        $userB = $currentUser['qnoow_id'];
                        $this->similarityModel->calculateSimilarityByInterests($userA, $userB);
                    }
                } catch (\Exception $e) {
                    $this->logger->error(
                        sprintf(
                            'Worker: Error calculating similarity for user %d with message %s on file %s, line %d',
                            $userA,
                            $e->getMessage(),
                            $e->getFile(),
                            $e->getLine()
                        )
                    );
                }
                break;
            case 'question_answered':

                $userA = $data['userId'];
                $this->logger->info(sprintf('Calculating matching by trigger "%s" for user "%s"', $trigger, $userA));

                try {
                    $usersAnsweredQuestion = $this->userModel->getByQuestionAnswered($data['question_id']);

                    foreach ($usersAnsweredQuestion as $currentUser) {
                        $userB = $currentUser['qnoow_id'];
                        if ($userA <> $userB) {
                            $this->similarityModel->calculateSimilarityByQuestions($userA, $userB);
                            $this->matchingModel->calculateMatchingBetweenTwoUsersBasedOnAnswers($userA, $userB);
                        }
                    }

                } catch (\Exception $e) {
                    $this->logger->error(
                        sprintf(
                            'Worker: Error calculating matching and similarity for user %d with message %s on file %s, line %d',
                            $userA,
                            $e->getMessage(),
                            $e->getFile(),
                            $e->getLine()
                        )
                    );
                }
                break;
            case 'matching_expired':

                $matchingType = $data['matching_type'];
                $user1 = $data['user_1_id'];
                $user2 = $data['user_2_id'];

                try {
                    switch ($matchingType) {
                        case 'content':
                            $this->similarityModel->getSimilarity($user1, $user2);
                            break;
                        case 'answer':
                            $this->matchingModel->calculateMatchingBetweenTwoUsersBasedOnAnswers($user1, $user2);
                            break;
                    }
                } catch (\Exception $e) {
                    $this->logger->error(
                        sprintf(
                            'Worker: Error calculating matching between user %d and user %d with message %s on file %s, line %d',
                            $user1,
                            $user2,
                            $e->getMessage(),
                            $e->getFile(),
                            $e->getLine()
                        )
                    );
                }
                break;
            default;
                throw new \Exception('Invalid matching calculation trigger');
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
