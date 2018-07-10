<?php

namespace Service;

use Everyman\Neo4j\Node;
use Model\Exception\ValidationException;
use Model\Filters\FilterContentManager;
use Model\Filters\FilterUsersManager;
use Model\Group\Group;
use Model\Thread\ContentThread;
use Model\Thread\Thread;
use Model\Thread\ThreadCachedManager;
use Model\Thread\ThreadDataManager;
use Model\Thread\ThreadManager;
use Model\Thread\UsersThread;

class ThreadService
{
    protected $threadManager;
    protected $filterUsersManager;
    protected $filterContentManager;
    protected $threadCachedManager;
    protected $threadDataManager;

    /**
     * ThreadService constructor.
     * @param ThreadManager $threadManager
     * @param FilterUsersManager $filterUsersManager
     * @param FilterContentManager $filterContentManager
     * @param ThreadCachedManager $threadCachedManager
     * @param ThreadDataManager $threadDataManager
     */
    public function __construct(ThreadManager $threadManager, FilterUsersManager $filterUsersManager, FilterContentManager $filterContentManager, ThreadCachedManager $threadCachedManager, ThreadDataManager $threadDataManager)
    {
        $this->threadManager = $threadManager;
        $this->filterUsersManager = $filterUsersManager;
        $this->filterContentManager = $filterContentManager;
        $this->threadCachedManager = $threadCachedManager;
        $this->threadDataManager = $threadDataManager;
    }

    public function getByThreadId($threadId)
    {
        $thread = $this->threadManager->getById($threadId);
        $this->setFilters($thread);
//        $this->setCachedResults($thread);

        return $thread;
    }

    public function getByThreadIdAndUserId($threadId, $userId)
    {
        $thread = $this->threadManager->getByIdAndUserId($threadId, $userId);
        $this->setFilters($thread);
//        $this->setCachedResults($thread);

        return $thread;
    }

    public function getAllByUserId($userId)
    {
        $threads = $this->threadManager->getAllByUserId($userId);

        foreach ($threads as $thread)
        {
            $this->setCachedResults($thread);
        }

        return $threads;
    }

    public function createThread($userId, array $data)
    {
        $thread = $this->threadManager->create($userId, $data);

        $filters = $this->getFiltersData($data);

        try{
            $this->validateFilters($data, $thread->getId(), $userId);
        } catch (ValidationException $e)
        {
            $this->deleteById($thread->getId());
            throw $e;
        }

        $thread = $this->updateFilters($thread, $filters);

        return $thread;
    }

    public function createDefaultThreads($userId, $scenario)
    {
        $threadsData = $this->threadDataManager->getDefaultThreads($userId, $scenario);
        $threads = $this->createBatchForUser($userId, $threadsData);

        foreach ($threads as $thread)
        {
            $this->threadManager->setAsDefault($thread->getId());
            $thread->setDefault(true);
        }

        return $threads;
    }

    /**
     * @param $userId
     * @param array $threadsData
     * @return Thread[]
     * @throws \Exception
     */
    public function createBatchForUser($userId, array $threadsData)
    {
        $returnThreads = array();

        $existingThreads = $this->threadManager->getAllByUserId($userId);

        foreach ($threadsData as $threadData) {
            foreach ($existingThreads as $existingThread) {
                if ($threadData['name'] == $existingThread->getName()) {
                    continue 2;
                }
            }

            $returnThreads[] = $this->createThread($userId, $threadData);
        }

        return $returnThreads;
    }

    public function createGroupThread(Group $group, $userId)
    {
        $groupData = $this->threadDataManager->getGroupThreadData($group, $userId);
        $thread = $this->createThread($userId, $groupData);
        $thread = $this->threadManager->joinToGroup($thread, $group);

        return $thread;
    }

    public function updateThread($threadId, $userId, array $data)
    {
        $this->validateFilters($data, $threadId, $userId);
        //TODO: remove userId, get from thread?
        $thread = $this->threadManager->update($threadId, $userId, $data);
        $filters = $this->getFiltersData($data);

        $thread = $this->updateFilters($thread, $filters);

        return $thread;
    }

    public function deleteGroupThreads($userId, $groupId)
    {
        $threads = $this->threadManager->getAllByUserId($userId);
        foreach ($threads as $thread)
        {
            $this->setFilters($thread);
        }

        foreach ($threads as $thread) {
            if (!$thread instanceof UsersThread) {
                continue;
            }

            $filter = $thread->getFilterUsers();
            if (!$filter || !$filter->get('groups') || !is_array($filter->get('groups'))) {
                continue;
            }

            /** @var Node $groupNode */
            foreach ($filter->get('groups') as $groupNode) {
                if ($groupNode->getId() == $groupId) {
                    $this->deleteById($thread->getId());
                }
            }
        }
    }

    protected function setFilters(Thread $thread)
    {
        if ($thread instanceof UsersThread)
        {
            $filters = $this->filterUsersManager->getFilterUsersByThreadId($thread->getId());
            $thread->setFilterUsers($filters);
        } else {
            /** @var ContentThread $thread */
            $filters = $this->filterContentManager->getFilterContentByThreadId($thread->getId());
            $thread->setFilterContent($filters);
        }
    }

    protected function validateFilters($data, $threadId, $userId = null)
    {
        $filters = $this->getFiltersData($data);

        $thread = $this->threadManager->getById($threadId);
        if ($thread instanceof UsersThread)
        {
            $this->filterUsersManager->validateOnUpdate($filters, $userId);
        } else {
            $this->filterContentManager->validateOnUpdate($filters);
        }
    }

    protected function updateFilters(Thread $thread, $filters)
    {
        $threadId = $thread->getId();
        if ($thread instanceof UsersThread) {
            $filters = $this->filterUsersManager->updateFilterUsersByThreadId($threadId, $filters);
            $thread->setFilterUsers($filters);
        } else {
            /** @var ContentThread $thread */
            $filters = $this->filterContentManager->updateFilterContentByThreadId($threadId, $filters);
            $thread->setFilterContent($filters);
        }

//        $this->recacheResults($thread);

        return $thread;
    }

    protected function getFiltersData(array $data)
    {
        return isset($data['filters']) ? $data['filters'] : array();
    }

    public function cacheResults($thread, $firstResults, $total){
        return $this->threadCachedManager->cacheResults($thread, $firstResults, $total);
    }

    protected function recacheResults($thread)
    {
        //delete cached
//        $this->threadCachedManager->deleteCachedResults($thread);

        //cache
        //$this->cacheResults()
    }

    protected function setCachedResults(Thread $thread)
    {
        if ($thread instanceof UsersThread) {
            $cached = $this->threadCachedManager->getCachedUsersRecommendations($thread);
            $thread->setCached($cached);
        } else {
            $cached = $this->threadCachedManager->getCachedContentRecommendations($thread);
            $thread->setCached($cached);
        }
    }

    public function deleteById($threadId)
    {
        $thread = $this->getByThreadId($threadId);
        if ($thread instanceof UsersThread)
        {
            $filters = $thread->getFilterUsers();
            $this->filterUsersManager->delete($filters);
        } else {
            /** @var $thread ContentThread */
            $filters = $thread->getFilterContent();
            $this->filterContentManager->delete($filters);
        }

        $this->threadManager->deleteById($threadId);

        return $thread;
    }
}