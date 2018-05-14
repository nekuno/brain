<?php

namespace Model\Relations;

use Paginator\PaginatedInterface;

class RelationsPaginatedManager extends RelationsManager implements PaginatedInterface
{
    public function validateFilters(array $filters)
    {
        $isRelationshipOk = isset($filters['relation']) && in_array($filters['relation'], $this::getRelations());

        return $isRelationshipOk;
    }

    public function slice(array $filters, $offset, $limit)
    {
        list ($relation, $from, $to) = $this->getParameters($filters);

        $relations = $this->getAll($relation, $from, $to);
        $userReports = $this->aggregateRelationsByUserTo($relations);
        $userReports = array_slice($userReports, $offset, $limit);

        $this->orderByRelationCount($userReports);

        return $userReports;
    }

    protected function aggregateRelationsByUserTo(array $relations)
    {
        $userList = array();
        foreach ($relations as $relation) {
            $userTo = $relation['to'];
            $userToId = $userTo['qnoow_id'];
            if ($this->isUserInCurrentList($userToId, $userList)) {
                $userList[$userToId]['relations'][] = $relation;
            } else {
                $userList[$userToId] = array(
                    'user' => $userTo,
                    'relations' => array($relation)
                );
            }
        }

        return $userList;
    }

    protected function orderByRelationCount(array &$userReports)
    {
        $size = array();
        foreach ($userReports as $userId => $relations) {
            $size[$userId] = count($relations);
        }
        array_multisort($size, SORT_DESC, $userReports);
    }

    /**
     * @param $userToId
     * @param array $userList
     * @return bool
     */
    protected function isUserInCurrentList($userToId, array $userList)
    {
        return isset($userList[$userToId]) && is_array($userList[$userToId]);
    }

    public function countTotal(array $filters)
    {
        list ($relation, $from, $to) = $this->getParameters($filters);

        return $this->count($relation, $from, $to);
    }

    protected function getParameters(array $filters)
    {
        $relation = $filters['relation'];
        $to = isset($filters['to']) ? $filters['to'] : null;
        $from = isset($filters['from']) ? $filters['from'] : null;

        return array($relation, $from, $to);
    }
}