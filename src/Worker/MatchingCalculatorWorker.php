<?php

namespace Worker;

use Doctrine\ORM\EntityManagerInterface;
use Event\MatchingProcessEvent;
use Event\MatchingProcessStepEvent;
use Event\SimilarityProcessEvent;
use Event\SimilarityProcessStepEvent;
use Event\UserStatusChangedEvent;
use Model\Neo4j\Neo4jException;
use Model\Popularity\PopularityManager;
use Model\Question\QuestionManager;
use Model\User\User;
use Model\Matching\MatchingManager;
use Model\Similarity\SimilarityManager;
use Model\User\UserManager;
use Psr\Log\LoggerInterface;
use Service\AffinityRecalculations;
use Service\AMQPManager;
use Service\EventDispatcherHelper;
use Service\UserStatsService;

class MatchingCalculatorWorker extends LoggerAwareWorker implements RabbitMQConsumerInterface
{

    const TRIGGER_QUESTION = 'question_answered';
    const TRIGGER_CONTENT_RATED = 'content_rated';
    const TRIGGER_PROCESS_FINISHED = 'process_finished';
    const TRIGGER_MATCHING_EXPIRED = 'matching_expired';

    protected $queue = AMQPManager::MATCHING;

    /**
     * @var UserManager
     */
    protected $userManager;
    /**
     * @var MatchingManager
     */
    protected $matchingModel;
    /**
     * @var SimilarityManager
     */
    protected $similarityModel;
    /**
     * @var UserStatsService
     */
    protected $userStatsService;
    /**
     * @var QuestionManager
     */
    protected $questionModel;
    /**
     * @var AffinityRecalculations
     */
    protected $affinityRecalculations;
    /**
     * @var PopularityManager
     */
    protected $popularityManager;
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    public function __construct(
        LoggerInterface $logger,
        UserManager $userManager,
        MatchingManager $matchingModel,
        SimilarityManager $similarityModel,
        UserStatsService $userStatsService,
        QuestionManager $questionModel,
        AffinityRecalculations $affinityRecalculations,
        PopularityManager $popularityManager,
        EntityManagerInterface $em,
        EventDispatcherHelper $dispatcherHelper
    ) {
        parent::__construct($logger, $dispatcherHelper);
        $this->userManager = $userManager;
        $this->matchingModel = $matchingModel;
        $this->similarityModel = $similarityModel;
        $this->userStatsService = $userStatsService;
        $this->questionModel = $questionModel;
        $this->affinityRecalculations = $affinityRecalculations;
        $this->popularityManager = $popularityManager;
        $this->em = $em;
    }

    /**
     * { @inheritdoc }
     */
    public function callback(array $data, $trigger)
    {
        // Verify mysql connections are alive
        if ($this->em->getConnection()->ping() === false) {
            $this->em->getConnection()->close();
            $this->em->getConnection()->connect();
        }

        switch ($trigger) {
            case self::TRIGGER_CONTENT_RATED:
            case self::TRIGGER_PROCESS_FINISHED:

                $userA = $data['userId'];
                $this->logger->notice(sprintf('[%s] Calculating matching by trigger "%s" for user "%s"', date('Y-m-d H:i:s'), $trigger, $userA));

                try {
                    $status = $this->userManager->calculateStatus($userA);
                    $this->logger->notice(sprintf('Calculating user "%s" new status: "%s"', $userA, $status->getStatus()));
                    if ($status->getStatusChanged()) {
                        $userStatusChangedEvent = new UserStatusChangedEvent($userA, $status->getStatus());
                        $this->dispatcherHelper->dispatch(\AppEvents::USER_STATUS_CHANGED, $userStatusChangedEvent);
                    }
                    $usersWithSameContent = $this->userManager->getByCommonLinksWithUser($userA, 1000);

                    $processId = time();
                    $similarityProcessEvent = new SimilarityProcessEvent($userA, $processId);
                    $this->dispatcherHelper->dispatch(\AppEvents::SIMILARITY_PROCESS_START, $similarityProcessEvent);
                    $usersCount = count($usersWithSameContent);
                    $prevPercentage = 0;
                    $this->popularityManager->updatePopularityByUser($userA);
                    foreach ($usersWithSameContent as $userIndex => $currentUser) {
                        /* @var $currentUser User */
                        $userB = $currentUser->getId();
                        $this->popularityManager->updatePopularityByUser($userB);
                        $similarity = $this->similarityModel->getSimilarityBy(SimilarityManager::INTERESTS, $userA, $userB);
                        $percentage = round(($userIndex + 1) / $usersCount * 100);
                        $this->logger->info(sprintf('   Similarity by interests between users %d - %d: %s', $userA, $userB, $similarity->getInterests()));
                        if ($percentage > $prevPercentage) {
                            $similarityProcessStepEvent = new SimilarityProcessStepEvent($userA, $processId, $percentage);
                            $this->dispatcherHelper->dispatch(\AppEvents::SIMILARITY_PROCESS_STEP, $similarityProcessStepEvent);
                            $prevPercentage = $percentage;
                        }
                        $this->userStatsService->updateShares($userA, $userB);
                    }
                    $this->dispatcherHelper->dispatch(\AppEvents::SIMILARITY_PROCESS_FINISH, $similarityProcessEvent);

                    $usersAnsweredQuestion = $this->userManager->getByUserQuestionAnswered($userA, 800);
                    $this->processUsersAnsweredQuestion($userA, $usersAnsweredQuestion);

                    $this->processUserAffinities($userA);

                } catch (\Exception $e) {
                    $this->logger->error(sprintf('Worker: Error calculating similarity for user %d with message %s on file %s, line %d', $userA, $e->getMessage(), $e->getFile(), $e->getLine()));
                    if ($e instanceof Neo4jException) {
                        $this->logger->error(sprintf('Query: %s' . "\n" . 'Data: %s', $e->getQuery(), print_r($e->getData(), true)));
                    }
                    $this->dispatcherHelper->dispatchError($e, 'Matching because process finished or content rated');
                }
                break;
            case self::TRIGGER_QUESTION:

                $userA = $data['userId'];
                $questionId = $data['question_id'];
                $this->logger->notice(sprintf('[%s] Calculating matching by trigger "%s" for user "%s" and question "%s"', date('Y-m-d H:i:s'), $trigger, $userA, $questionId));

                try {
                    $status = $this->userManager->calculateStatus($userA);
                    $this->logger->notice(sprintf('Calculating user "%s" new status: "%s"', $userA, $status->getStatus()));
                    if ($status->getStatusChanged()) {
                        $userStatusChangedEvent = new UserStatusChangedEvent($userA, $status->getStatus());
                        $this->dispatcherHelper->dispatch(\AppEvents::USER_STATUS_CHANGED, $userStatusChangedEvent);
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
                    $this->dispatcherHelper->dispatchError($e, 'Matching because question answered');
                }
                break;
            case self::TRIGGER_MATCHING_EXPIRED:

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
                            $this->logger->info(sprintf('   Matching by questions between users %d - %d: %s', $user1, $user2, $matching->getMatching()));
                            break;
                    }
                } catch (\Exception $e) {
                    $this->logger->error(sprintf('Worker: Error calculating matching between user %d and user %d with message %s on file %s, line %d', $user1, $user2, $e->getMessage(), $e->getFile(), $e->getLine()));
                    if ($e instanceof Neo4jException) {
                        $this->logger->error(sprintf('Query: %s' . "\n" . 'Data: %s', $e->getQuery(), print_r($e->getData(), true)));
                    }
                    $this->dispatcherHelper->dispatchError($e, 'Matching because matching expired');
                }
                break;
            default;
                throw new \Exception('Invalid matching calculation trigger');
        }
    }

    private function processUsersAnsweredQuestion($userA, $usersAnsweredQuestion)
    {
        $processId = time();
        $matchingProcessEvent = new MatchingProcessEvent($userA, $processId);
        $this->dispatcherHelper->dispatch(\AppEvents::MATCHING_PROCESS_START, $matchingProcessEvent);
        $usersCount = count($usersAnsweredQuestion);
        $this->logger->info(sprintf('   Processing %d users', $usersCount));
        $prevPercentage = 0;
        foreach ($usersAnsweredQuestion as $userIndex => $currentUser) {
            /* @var $currentUser User */
            $userB = $currentUser->getId();
            if ($userA <> $userB) {
                $similarity = $this->similarityModel->getSimilarityBy(SimilarityManager::QUESTIONS, $userA, $userB);
                $matching = $this->matchingModel->calculateMatchingBetweenTwoUsersBasedOnAnswers($userA, $userB);
                $percentage = round(($userIndex + 1) / $usersCount * 100);
                $this->logger->info(sprintf('   Similarity by questions between users %d - %d: %s', $userA, $userB, $similarity->getQuestions()));
                $this->logger->info(sprintf('   Matching by questions between users %d - %d: %s', $userA, $userB, $matching->getMatching()));
                if ($percentage > $prevPercentage) {
                    $matchingProcessStepEvent = new MatchingProcessStepEvent($userA, $processId, $percentage);
                    $this->dispatcherHelper->dispatch(\AppEvents::MATCHING_PROCESS_STEP, $matchingProcessStepEvent);
                    $prevPercentage = $percentage;
                }
            }
        }
        $this->dispatcherHelper->dispatch(\AppEvents::MATCHING_PROCESS_FINISH, $matchingProcessEvent);
    }

    private function processUserAffinities($userId)
    {
        $this->logger->info(sprintf('   Recalculating affinities for user %d', $userId));
        $this->affinityRecalculations->recalculateAffinities($userId, 100, 20);
        $this->logger->info(sprintf('   Finished recalculating affinities for user %d', $userId));
    }

}
