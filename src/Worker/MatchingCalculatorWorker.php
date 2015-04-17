<?php


namespace Worker;

use Doctrine\DBAL\Connection;
use Model\User\Matching\MatchingModel;
use Model\User\Similarity\SimilarityModel;
use Model\UserModel;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class MatchingCalculatorWorker
 * @package Worker
 */
class MatchingCalculatorWorker extends LoggerAwareWorker implements RabbitMQConsumerInterface
{

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

    /**
     * @var Connection
     */
    protected $connectionSocial;

    /**
     * @var Connection
     */
    protected $connectionBrain;

    public function __construct(AMQPChannel $channel, UserModel $userModel, MatchingModel $matchingModel, SimilarityModel $similarityModel, Connection $connectionSocial, Connection $connectionBrain)
    {

        $this->channel = $channel;
        $this->userModel = $userModel;
        $this->matchingModel = $matchingModel;
        $this->similarityModel = $similarityModel;
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

        $trigger = $data['trigger'];

        switch ($trigger) {
            case 'content_rated':
            case 'process_finished':

                $userA = $data['userId'];
                $this->logger->notice(sprintf('[%s] Calculating matching by trigger "%s" for user "%s"', date('Y-m-d H:i:s'), $trigger, $userA));

                try {
                    $status = $this->userModel->calculateStatus($userA);
                    $this->logger->notice(sprintf('Calculating user "%s" new status: "%s"', $userA, $status->getStatus()));
                    $usersWithSameContent = $this->userModel->getByCommonLinksWithUser($userA);

                    foreach ($usersWithSameContent as $currentUser) {
                        $userB = $currentUser['qnoow_id'];
                        $similarity = $this->similarityModel->calculateSimilarityByInterests($userA, $userB);
                        $this->logger->info(sprintf('   Similarity by interests between users %d - %d: %s', $userA, $userB, $similarity));
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
                $questionId = $data['question_id'];
                $this->logger->notice(sprintf('[%s] Calculating matching by trigger "%s" for user "%s" and question "%s"', date('Y-m-d H:i:s'), $trigger, $userA, $questionId));

                try {
                    $status = $this->userModel->calculateStatus($userA);
                    $this->logger->notice(sprintf('Calculating user "%s" new status: "%s"', $userA, $status->getStatus()));
                    $usersAnsweredQuestion = $this->userModel->getByQuestionAnswered($questionId);

                    foreach ($usersAnsweredQuestion as $currentUser) {

                        $userB = $currentUser['qnoow_id'];
                        if ($userA <> $userB) {
                            $similarity = $this->similarityModel->calculateSimilarityByQuestions($userA, $userB);
                            $matching = $this->matchingModel->calculateMatchingBetweenTwoUsersBasedOnAnswers($userA, $userB);
                            $this->logger->info(sprintf('   Similarity by questions between users %d - %d: %s', $userA, $userB, $similarity));
                            $this->logger->info(sprintf('   Matching by questions between users %d - %d: %s', $userA, $userB, $matching));
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

        $this->memory();
    }

}
