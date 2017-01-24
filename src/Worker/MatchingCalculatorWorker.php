<?php

namespace Worker;

use Doctrine\DBAL\Connection;
use Event\MatchingProcessEvent;
use Event\MatchingProcessStepEvent;
use Event\SimilarityProcessEvent;
use Event\SimilarityProcessStepEvent;
use Event\UserStatusChangedEvent;
use Model\Neo4j\Neo4jException;
use Model\Questionnaire\QuestionModel;
use Model\User;
use Model\User\Matching\MatchingModel;
use Model\User\Similarity\SimilarityModel;
use Manager\UserManager;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Service\AffinityRecalculations;
use Symfony\Component\EventDispatcher\EventDispatcher;


class MatchingCalculatorWorker extends LoggerAwareWorker implements RabbitMQConsumerInterface
{

    const TRIGGER_PERIODIC = 'periodic';
    /**
     * @var AMQPChannel
     */
    protected $channel;
    /**
     * @var UserManager
     */
    protected $userManager;
    /**
     * @var MatchingModel
     */
    protected $matchingModel;
    /**
     * @var SimilarityModel
     */
    protected $similarityModel;
    /**
     * @var QuestionModel
     */
    protected $questionModel;
    /**
     * @var AffinityRecalculations
     */
    protected $affinityRecalculations;
    /**
     * @var Connection
     */
    protected $connectionBrain;
    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    public function __construct(AMQPChannel $channel, UserManager $userManager, MatchingModel $matchingModel, SimilarityModel $similarityModel, QuestionModel $questionModel, AffinityRecalculations $affinityRecalculations, Connection $connectionBrain, EventDispatcher $dispatcher)
    {

        $this->channel = $channel;
        $this->userManager = $userManager;
        $this->matchingModel = $matchingModel;
        $this->similarityModel = $similarityModel;
        $this->questionModel = $questionModel;
        $this->affinityRecalculations = $affinityRecalculations;
        $this->connectionBrain = $connectionBrain;
        $this->dispatcher = $dispatcher;
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
        if ($this->connectionBrain->ping() === false) {
            $this->connectionBrain->close();
            $this->connectionBrain->connect();
        }

        $data = json_decode($message->body, true);

        $trigger = $this->getTrigger($message);

        switch ($trigger) {
            case 'content_rated':
            case 'process_finished':

                $userA = $data['userId'];
                $this->logger->notice(sprintf('[%s] Calculating matching by trigger "%s" for user "%s"', date('Y-m-d H:i:s'), $trigger, $userA));

                try {
                    $status = $this->userManager->calculateStatus($userA);
                    $this->logger->notice(sprintf('Calculating user "%s" new status: "%s"', $userA, $status->getStatus()));
                    if ($status->getStatusChanged()) {
                        $userStatusChangedEvent = new UserStatusChangedEvent($userA, $status->getStatus());
                        $this->dispatcher->dispatch(\AppEvents::USER_STATUS_CHANGED, $userStatusChangedEvent);
                    }
                    $usersWithSameContent = $this->userManager->getByCommonLinksWithUser($userA, 1000);

                    $processId = time();
                    $similarityProcessEvent = new SimilarityProcessEvent($userA, $processId);
                    $this->dispatcher->dispatch(\AppEvents::SIMILARITY_PROCESS_START, $similarityProcessEvent);
                    $usersCount = count($usersWithSameContent);
                    $prevPercentage = 0;
                    foreach ($usersWithSameContent as $userIndex => $currentUser) {
                        /* @var $currentUser User */
                        $userB = $currentUser->getId();
                        $similarity = $this->similarityModel->getSimilarityBy(SimilarityModel::INTERESTS, $userA, $userB);
                        $percentage = round(($userIndex + 1) / $usersCount * 100);
                        $this->logger->info(sprintf('   Similarity by interests between users %d - %d: %s', $userA, $userB, $similarity['interests']));
                        if ($percentage > $prevPercentage) {
                            $similarityProcessStepEvent = new SimilarityProcessStepEvent($userA, $processId, $percentage);
                            $this->dispatcher->dispatch(\AppEvents::SIMILARITY_PROCESS_STEP, $similarityProcessStepEvent);
                            $prevPercentage = $percentage;
                        }
                    }
                    $this->dispatcher->dispatch(\AppEvents::SIMILARITY_PROCESS_FINISH, $similarityProcessEvent);

                    $usersAnsweredQuestion = $this->userManager->getByUserQuestionAnswered($userA, 800);
                    $this->processUsersAnsweredQuestion($userA, $usersAnsweredQuestion);

                    $this->processUserAffinities($userA);

                } catch (\Exception $e) {
                    $this->logger->error(sprintf('Worker: Error calculating similarity for user %d with message %s on file %s, line %d', $userA, $e->getMessage(), $e->getFile(), $e->getLine()));
                    if ($e instanceof Neo4jException) {
                        $this->logger->error(sprintf('Query: %s' . "\n" . 'Data: %s', $e->getQuery(), print_r($e->getData(), true)));
                    }
                    $this->dispatchError($e, 'Matching because process finished or content rated');
                }
                break;
            case 'question_answered':

                $userA = $data['userId'];
                $questionId = $data['question_id'];
                $this->logger->notice(sprintf('[%s] Calculating matching by trigger "%s" for user "%s" and question "%s"', date('Y-m-d H:i:s'), $trigger, $userA, $questionId));

                try {
                    $status = $this->userManager->calculateStatus($userA);
                    $this->logger->notice(sprintf('Calculating user "%s" new status: "%s"', $userA, $status->getStatus()));
                    if ($status->getStatusChanged()) {
                        $userStatusChangedEvent = new UserStatusChangedEvent($userA, $status->getStatus());
                        $this->dispatcher->dispatch(\AppEvents::USER_STATUS_CHANGED, $userStatusChangedEvent);
                    }

                    if (!$this->questionModel->userHasCompletedRegisterQuestions($userA)) {
                        break;
                    }
                    $usersAnsweredQuestion = $this->userManager->getByQuestionAnswered($questionId, 800);
                    $this->processUsersAnsweredQuestion($userA, $usersAnsweredQuestion);

                    $this->processUserAffinities($userA);

                } catch (\Exception $e) {
                    $this->logger->error(sprintf('Worker: Error calculating matching and similarity for user %d with message %s on file %s, line %d', $userA, $e->getMessage(), $e->getFile(), $e->getLine()));
                    if ($e instanceof Neo4jException) {
                        $this->logger->error(sprintf('Query: %s' . "\n" . 'Data: %s', $e->getQuery(), print_r($e->getData(), true)));
                    }
                    $this->dispatchError($e, 'Matching because question answered');
                }
                break;
            case 'matching_expired':

                $matchingType = $data['matching_type'];
                $user1 = $data['user_1_id'];
                $user2 = $data['user_2_id'];
                $this->logger->notice(sprintf('[%s] Calculating matching by trigger "%s" for users %d - %d', date('Y-m-d H:i:s'), $trigger, $user1, $user2));

                try {
                    switch ($matchingType) {
                        case 'content':
                            $similarity = $this->similarityModel->getSimilarity($user1, $user2);
                            $this->logger->info(sprintf('   Similarity between users %d - %d: %s', $user1, $user2, $similarity['similarity']));
                            break;
                        case 'answer':
                            $matching = $this->matchingModel->calculateMatchingBetweenTwoUsersBasedOnAnswers($user1, $user2);
                            $this->logger->info(sprintf('   Matching by questions between users %d - %d: %s', $user1, $user2, $matching));
                            break;
                    }
                } catch (\Exception $e) {
                    $this->logger->error(sprintf('Worker: Error calculating matching between user %d and user %d with message %s on file %s, line %d', $user1, $user2, $e->getMessage(), $e->getFile(), $e->getLine()));
                    if ($e instanceof Neo4jException) {
                        $this->logger->error(sprintf('Query: %s' . "\n" . 'Data: %s', $e->getQuery(), print_r($e->getData(), true)));
                    }
                    $this->dispatchError($e, 'Matching because matching expired');
                }
                break;
            case $this:: TRIGGER_PERIODIC:
                $user1 = $data['user_1_id'];
                $user2 = $data['user_2_id'];
                $this->logger->notice(sprintf('[%s] Calculating matching by trigger "%s" for users %d - %d', date('Y-m-d H:i:s'), $trigger, $user1, $user2));

                try {
                    $similarity = $this->similarityModel->getSimilarity($user1, $user2);
                    $matching = $this->matchingModel->calculateMatchingBetweenTwoUsersBasedOnAnswers($user1, $user2);
                    $this->logger->info(sprintf('   Similarity between users %d - %d: %s', $user1, $user2, $similarity['similarity']));
                    $this->logger->info(sprintf('   Matching by questions between users %d - %d: %s', $user1, $user2, $matching));
                } catch (\Exception $e) {
                    $this->logger->error(sprintf('Worker: Error calculating similarity and matching between user %d and user %d with message %s on file %s, line %d', $user1, $user2, $e->getMessage(), $e->getFile(), $e->getLine()));
                    if ($e instanceof Neo4jException) {
                        $this->logger->error(sprintf('Query: %s' . "\n" . 'Data: %s', $e->getQuery(), print_r($e->getData(), true)));
                    }
                    $this->dispatchError($e, 'Matching with periodic trigger');
                }
                break;
            default;
                throw new \Exception('Invalid matching calculation trigger');
        }

        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);

        $this->memory();
    }

    private function processUsersAnsweredQuestion($userA, $usersAnsweredQuestion)
    {
        $processId = time();
        $matchingProcessEvent = new MatchingProcessEvent($userA, $processId);
        $this->dispatcher->dispatch(\AppEvents::MATCHING_PROCESS_START, $matchingProcessEvent);
        $usersCount = count($usersAnsweredQuestion);
        $this->logger->info(sprintf('   Processing %d users', $usersCount));
        $prevPercentage = 0;
        foreach ($usersAnsweredQuestion as $userIndex => $currentUser) {
            /* @var $currentUser User */
            $userB = $currentUser->getId();
            if ($userA <> $userB) {
                $similarity = $this->similarityModel->getSimilarityBy(SimilarityModel::QUESTIONS, $userA, $userB);
                $matching = $this->matchingModel->calculateMatchingBetweenTwoUsersBasedOnAnswers($userA, $userB);
                $percentage = round(($userIndex + 1)/$usersCount * 100);
                $this->logger->info(sprintf('   Similarity by questions between users %d - %d: %s', $userA, $userB, $similarity['questions']));
                $this->logger->info(sprintf('   Matching by questions between users %d - %d: %s', $userA, $userB, $matching));
                if ($percentage > $prevPercentage) {
                    $matchingProcessStepEvent = new MatchingProcessStepEvent($userA, $processId, $percentage);
                    $this->dispatcher->dispatch(\AppEvents::MATCHING_PROCESS_STEP, $matchingProcessStepEvent);
                    $prevPercentage = $percentage;
                }
            }
        }
        $this->dispatcher->dispatch(\AppEvents::MATCHING_PROCESS_FINISH, $matchingProcessEvent);
    }

    private function processUserAffinities($userId) {
        $this->logger->info(sprintf('   Recalculating affinities for user %d', $userId));
        $this->affinityRecalculations->recalculateAffinities($userId, 100, 20);
        $this->logger->info(sprintf('   Finished recalculating affinities for user %d', $userId));
    }

}
