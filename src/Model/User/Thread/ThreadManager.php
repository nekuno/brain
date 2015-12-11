<?php

namespace Model\User\Thread;


use Everyman\Neo4j\Label;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Exception\ValidationException;
use Model\Neo4j\GraphManager;
use Model\UserModel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ThreadManager
{
    const LABEL_THREAD = 'Thread';
    const LABEL_THREAD_USERS = 'ThreadUsers';
    const LABEL_THREAD_CONTENT = 'ThreadContent';

    /** @var  GraphManager */
    protected $graphManager;
    /** @var  UserModel */
    protected $userModel;
    /** @var  UsersThreadManager */
    protected $usersThreadManager;
    /** @var  ContentThreadManager */
    protected $contentThreadManager;

    /**
     * ThreadManager constructor.
     * @param GraphManager $graphManager
     * @param UserModel $userModel
     * @param UsersThreadManager $um
     * @param ContentThreadManager $cm
     */
    public function __construct(GraphManager $graphManager, UserModel $userModel, UsersThreadManager $um, ContentThreadManager $cm)
    {
        $this->graphManager = $graphManager;
        $this->userModel = $userModel;
        $this->usersThreadManager = $um;
        $this->contentThreadManager = $cm;
    }

    /**
     * @param $id
     * @return Thread
     * @throws \Exception
     * @throws \Model\Neo4j\Neo4jException
     */
    public function getById($id)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(thread:Thread)')
            ->where('id(thread) = {id}')
            ->returns('thread');
        $qb->setParameter('id', (integer)$id);
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException('Thread with id ' . $id . ' not found');
        }

        /** @var Node $threadNode */
        $threadNode = $result->current()->offsetGet('thread');

        return $this->buildThread($threadNode);
    }

    /**
     * @param $userId
     * @return array of Thread
     * @throws \Exception
     * @throws \Model\Neo4j\Neo4jException
     */
    public function getByUser($userId)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(user:User)')
            ->where('user.qnoow_id= {id}')
            ->optionalMatch('(user)-[:HAS_THREAD]->(thread:Thread)')
            ->returns('user, collect(thread) as threads');
        $qb->setParameter('id', (integer)$userId);
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException('User with id ' . $userId . ' not found');
        }

        /** @var Row $row */
        $row = $result->current();

        $threads = array();
        /** @var Node $threadNode */
        foreach ($row->offsetGet('threads') as $threadNode) {
            $threads[] = $this->buildThread($threadNode);
        }

        return $threads;
    }

    /**
     * Builds a complete Thread object from a neo4j node
     * @param Node $threadNode
     * @return Thread
     * @throws \Exception
     */
    private function buildThread(Node $threadNode)
    {
        $id = $threadNode->getId();

        switch ($category = $this->getCategory($threadNode)) {
            case $this::LABEL_THREAD_USERS: {
                $thread = $this->usersThreadManager->buildUsersThread($id, $threadNode->getProperty('name'));
                $cached = $this->usersThreadManager->getCached($thread);
                break;
            }
            case $this::LABEL_THREAD_CONTENT: {
                $thread = $this->contentThreadManager->buildContentThread($id, $threadNode->getProperty('name'), $threadNode->getProperty('type'));
                $cached = $this->contentThreadManager->getCached($thread);
                break;
            }
            default :
                throw new \Exception('Thread category ' . $category . ' not found or supported');
        }

        $thread->setCached($cached);

        return $thread;
    }

    /**
     * @param Node $threadNode
     * @return null|string
     */
    private function getCategory(Node $threadNode)
    {
        $labels = $threadNode->getLabels();

        /** @var Label $label */
        foreach ($labels as $label) {
            if ($label->getName() != ThreadManager::LABEL_THREAD) {
                return $label->getName();
            }
        }

        return null;
    }

    /**
     * Creates an appropriate neo4j node from a set of filters
     * @param $userId
     * @param $data
     * @return Thread|null
     * @throws \Model\Neo4j\Neo4jException
     */
    public function create($userId, $data)
    {
        //TODO: Avoid duplicate if coincidence of userId and name ?
        $name = isset($data['name']) ? $data['name'] : null;
        $category = isset($data['category']) ? $data['category'] : null;
        $this->validate(array_merge(
            array('userId' => (integer)$userId),
            $data
        ));

        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(u:User{qnoow_id:{userId}})')
            ->create('(thread:' . ThreadManager::LABEL_THREAD . ':' . $category . ')')
            ->set('thread.name = {name}')
            ->create('(u)-[:HAS_THREAD]->(thread)');
        $qb->returns('id(thread) as id');
        $qb->setParameters(array(
            'name' => $name,
            'userId' => (integer)$userId));

        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1) {
            return null;
        }

        $id = $result->current()->offsetGet('id');
        $thread = $this->getById($id);

        return $this->updateFromFilters($thread, $data);
    }

    public function update($threadId, $data)
    {

        $name = isset($data['name']) ? $data['name'] : null;
        $category = isset($data['category']) ? $data['category'] : null;

        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(thread:Thread)')
            ->where('id(thread) = {id}')
            ->remove('thread:' . $this::LABEL_THREAD_USERS .':'. $this::LABEL_THREAD_CONTENT)
            ->set('thread:' . $category)
            ->set('thread.name = {name}');
        $qb->returns('thread');
        $qb->setParameters(array(
            'name' => $name,
            'id' => (integer)$threadId));

        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1) {
            return null;
        }

        $threadNode = $result->current()->offsetGet('thread');
        $thread = $this->buildThread($threadNode);

        return $this->updateFromFilters($thread, $data);

    }

    private function updateFromFilters(Thread $thread, $data)
    {

        $filters = isset($data['filters']) ? $data['filters'] : array();
        switch (get_class($thread)) {
            case 'Model\User\Thread\ContentThread':

                $this->contentThreadManager->update($thread->getId(), $filters);
                break;
            case 'Model\User\Thread\UsersThread':

                $this->usersThreadManager->update($thread->getId(), $filters);
                break;
            default:
                return null;
        }

        return $this->getById($thread->getId());
    }

    public function validate($data)
    {
        $errors = array();
        $metadata = $this->getMetadata();
        foreach ($metadata as $fieldName => $fieldData) {

            $fieldErrors = array();
            if (isset($data[$fieldName])) {

                switch ($fieldData['type']) {
                    case 'choice':
                        if (!in_array($data[$fieldName], $this->getChoices($fieldName))) {
                            $fieldErrors[] = 'Choice not supported.';
                        }
                        break;
                    default:
                        break;
                }
            } else if ($fieldData['required'] === true) {
                $fieldErrors[] = 'It\'s required.';
            }

            if (count($fieldErrors) > 0) {
                $errors[$fieldName] = $fieldErrors;
            }
        }

        if (isset($data['userId'])) {
            try {
                $this->userModel->getById((integer)$data['userId']);
            } catch (NotFoundHttpException $e) {
                $errors['userId'] = array($e->getMessage());
            }
        } else {
            $errors['userId'] = array('User identification not supplied');
        }


        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }

    }

    private function getMetadata()
    {
        $metadata = array(
            'name' => array(
                'type' => 'string',
                'required' => true,
            ),
            'category' => array(
                'type' => 'choice',
                'required' => true
            )
        );
        return $metadata;
    }

    private function getChoices($fieldName)
    {
        switch ($fieldName) {
            case 'category':
                return array(
                    ThreadManager::LABEL_THREAD_USERS,
                    ThreadManager::LABEL_THREAD_CONTENT,
                );
            default:
                return array();
        }
    }

    /**
     * @param Thread $thread Which thread returned the results
     * @param array $items
     * @return Thread
     * @throws \Exception
     * @throws \Model\Neo4j\Neo4jException
     */
    public function cacheResults(Thread $thread, array $items)
    {

        $this->deleteCachedResults($thread);

        $parameters = array(
            'id' => $thread->getId()
        );
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(thread:Thread)')
            ->where('id(thread) = {id}');

        foreach ($items as $item) {
            switch (get_class($thread)) {
                case 'Model\User\Thread\ContentThread':
                    $id = $item['content']['id'];
                    $qb->match('(l:Link)')
                        ->where("id(l) = {$id}")
                        ->merge('(thread)-[:RECOMMENDS]->(l)')
                        ->with('thread');
                    $parameters += array($id => $id);
                    break;
                case 'Model\User\Thread\UsersThread':
                    $id = $item['id'];
                    $qb->match('(u:User)')
                        ->where("u.qnoow_id = {$id}")
                        ->merge('(thread)-[:RECOMMENDS]->(u)')
                        ->with('thread');
                    $parameters += array($id => $id);
                    break;
                default:
                    throw new \Exception('Thread ' . $thread->getId() . ' has a not valid category.');
                    break;
            }
        }

        $qb->returns('thread');
        $qb->setParameters($parameters);
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException('Thread with id ' . $thread->getId() . ' not found');
        }

        /** @var Node $threadNode */
        $threadNode = $result->current()->offsetGet('thread');

        return $this->buildThread($threadNode);

    }

    private function deleteCachedResults(Thread $thread)
    {
        $parameters = array(
            'id' => $thread->getId()
        );
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(thread:Thread)')
            ->where('id(thread) = {id}')
            ->optionalMatch('(thread)-[r:RECOMMENDS]->()')
            ->delete('r');
        $qb->setParameters($parameters);
        $qb->getQuery()->getResultSet();

    }
}

