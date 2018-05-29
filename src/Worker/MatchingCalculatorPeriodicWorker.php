<?php

namespace Worker;

use Model\Neo4j\Neo4jException;
use Service\AMQPManager;

class MatchingCalculatorPeriodicWorker extends MatchingCalculatorWorker
{
    const TRIGGER_PERIODIC = 'periodic';

    protected $queue = AMQPManager::MATCHING_PERIODIC;

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
            case self:: TRIGGER_PERIODIC:
                $user1 = $data['user_1_id'];
                $user2 = $data['user_2_id'];
                $this->logger->notice(sprintf('[%s] Calculating matching by trigger "%s" for users %d - %d', date('Y-m-d H:i:s'), $trigger, $user1, $user2));

                try {
                    $this->userManager->getById($user1);
                    $this->userManager->getById($user2);
                } catch (\Exception $e) {
                    $this->logger->error(sprintf('Worker: Error calculating similarity and matching between user %d and user %d with message "one of the users does not exist"', $user1, $user2));
                    $this->dispatchError($e, 'Matching with periodic trigger');
                    break;
                }

                try {
                    $this->popularityManager->updatePopularityByUser($user2);
                    $similarity = $this->similarityModel->getSimilarity($user1, $user2);
                    $matching = $this->matchingModel->calculateMatchingBetweenTwoUsersBasedOnAnswers($user1, $user2);
                    $this->logger->info(sprintf('   Similarity between users %d - %d: %s', $user1, $user2, $similarity['similarity']));
                    $this->logger->info(sprintf('   Matching by questions between users %d - %d: %s', $user1, $user2, $matching->getMatching()));
                    $this->userStatsService->updateShares($user1, $user2);
                } catch (\Exception $e) {
                    $this->logger->error(sprintf('Worker: Error calculating similarity and matching between user %d and user %d with message %s on file %s, line %d', $user1, $user2, $e->getMessage(), $e->getFile(), $e->getLine()));
                    if ($e instanceof Neo4jException) {
                        $this->logger->error(sprintf('Query: %s' . "\n" . 'Data: %s', $e->getQuery(), print_r($e->getData(), true)));
                    }
                    $this->dispatchError($e, 'Matching with periodic trigger');
                }
                break;
            default;
                throw new \Exception('Invalid periodic matching calculation trigger');
        }
    }
}
