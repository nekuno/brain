<?php

namespace Model\User\Recommendation;

use Everyman\Neo4j\Query\ResultSet;
use Manager\PhotoManager;
use Model\Neo4j\GraphManager;
use Model\User\GhostUser\GhostUserManager;
use Model\User\ProfileFilterModel;
use Model\User\UserFilterModel;
use Paginator\PaginatedInterface;

abstract class AbstractUserPaginatedModel implements PaginatedInterface
{
    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var ProfileFilterModel
     */
    protected $profileFilterModel;

    /**
     * @var UserFilterModel
     */
    protected $userFilterModel;

    /**
     * @var PhotoManager
     */
    protected $pm;

    public function __construct(GraphManager $gm, ProfileFilterModel $profileFilterModel, UserFilterModel $userFilterModel, PhotoManager $pm)
    {
        $this->gm = $gm;
        $this->profileFilterModel = $profileFilterModel;
        $this->userFilterModel = $userFilterModel;
        $this->pm = $pm;
    }

    /**
     * Hook point for validating the query.
     * @param array $filters
     * @return boolean
     */
    public function validateFilters(array $filters)
    {
        $hasId = isset($filters['id']);
        $hasProfileFilters = isset($filters['profileFilters']);

        return $hasId && $hasProfileFilters;
    }

    public function getUsersByPopularity($filters, $offset, $limit, $additionalCondition = null)
    {
        $id = $filters['id'];

        $parameters = array(
            'offset' => (integer)$offset,
            'limit' => (integer)$limit,
            'userId' => (integer)$id
        );

        $filters = $this->profileFilterModel->splitFilters($filters);

        $profileFilters = $this->getProfileFilters($filters['profileFilters']);
        $userFilters = $this->getUserFilters($filters['userFilters']);
        $qb = $this->gm->createQueryBuilder();

        $qb->setParameters($parameters);

        $qb->match('(anyUser:User)')
            ->where('{userId} <> anyUser.qnoow_id', 'NOT (anyUser:' . GhostUserManager::LABEL_GHOST_USER . ')')
            ->optionalMatch('(:User {qnoow_id: { userId }})-[m:MATCHES]-(anyUser)')
            ->optionalMatch('(:User {qnoow_id: { userId }})-[s:SIMILARITY]-(anyUser)')
            ->with(
                'anyUser,
                (CASE WHEN EXISTS(m.matching_questions) THEN m.matching_questions ELSE 0.01 END) AS matching_questions,
                (CASE WHEN EXISTS(s.similarity) THEN s.similarity ELSE 0.01 END) AS similarity'
            )
            ->where($userFilters['conditions'])
            ->match('(anyUser)<-[:PROFILE_OF]-(p:Profile)');

        $qb->optionalMatch('(p)-[:LOCATION]->(l:Location)');

        $qb->with('anyUser, p, l, matching_questions', 'similarity');
        $qb->where($profileFilters['conditions'])
            ->with('anyUser', 'p', 'l', 'matching_questions', 'similarity');

        if (null !== $additionalCondition) {
            $qb->add('', $additionalCondition);
        }

        foreach ($profileFilters['matches'] as $match) {
            $qb->match($match);
        }
        foreach ($userFilters['matches'] as $match) {
            $qb->match($match);
        }

        $qb->with('DISTINCT anyUser, p, l, matching_questions', 'similarity')
            ->optionalMatch('(anyUser)<-[likes:LIKES]-(:User)')
            ->with('anyUser', 'count(likes) as popularity', 'p', 'l', 'matching_questions', 'similarity');

        $qb->returns(
            'anyUser.qnoow_id AS id,
             anyUser.username AS username,
             anyUser.photo AS photo,
             p.birthday AS birthday,
             l.locality + ", " + l.country AS location',
            'matching_questions',
            'similarity',
            '0 AS like',
            'popularity'
        )
            ->orderBy('popularity DESC')
            ->skip('{ offset }')
            ->limit('{ limit }');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        return $this->buildUserRecommendations($result);
    }

    /**
     * @param array $filters
     * @return array
     */
    protected function getProfileFilters(array $filters)
    {
        $conditions = array();
        $matches = array();

        $profileFilterMetadata = $this->getProfileFilterMetadata();
        foreach ($profileFilterMetadata as $name => $filter) {
            if (isset($filters[$name])) {
                $value = $filters[$name];
                switch ($filter['type']) {
                    case 'text':
                    case 'textarea':
                        $conditions[] = "p.$name =~ '(?i).*$value.*'";
                        break;
                    case 'integer_range':
                        $min = (integer)$value['min'];
                        $max = (integer)$value['max'];
                        $conditions[] = "($min <= p.$name AND p.$name <= $max)";
                        break;
                    case 'date':

                        break;
                    //To use from social
                    case 'birthday':
                        $min = $value['min'];
                        $max = $value['max'];
                        $conditions[] = "('$min' <= p.$name AND p.$name <= '$max')";
                        break;
                    case 'birthday_range':
                        $birthdayRange = $this->profileFilterModel->getBirthdayRangeFromAgeRange($value['min'], $value['max']);
                        $min = $birthdayRange['min'];
                        $max = $birthdayRange['max'];
                        $conditions[] = "('$min' <= p.$name AND p.$name <= '$max')";
                        break;
                    case 'location_distance':
                    case 'location':
                        $distance = (int)$value['distance'];
                        $latitude = (float)$value['location']['latitude'];
                        $longitude = (float)$value['location']['longitude'];
                        $conditions[] = "(NOT l IS NULL AND EXISTS(l.latitude) AND EXISTS(l.longitude) AND
                        " . $distance . " >= toInt(6371 * acos( cos( radians(" . $latitude . ") ) * cos( radians(l.latitude) ) * cos( radians(l.longitude) - radians(" . $longitude . ") ) + sin( radians(" . $latitude . ") ) * sin( radians(l.latitude) ) )))";
                        break;
                    case 'boolean':
                        $conditions[] = "p.$name = true";
                        break;
                    case 'choice':
                    case 'multiple_choices':
                        $profileLabelName = $this->profileFilterModel->typeToLabel($name);
                        $value = implode("', '", $value);
                        $matches[] = "(p)<-[:OPTION_OF]-(option$name:$profileLabelName) WHERE option$name.id IN ['$value']";
                        break;
                    case 'double_choice':
                        $profileLabelName = $this->profileFilterModel->typeToLabel($name);
                        $value = implode("', '", $value);
                        $matches[] = "(p)<-[:OPTION_OF]-(option$name:$profileLabelName) WHERE option$name.id IN ['$value']";
                        break;
                    case 'double_multiple_choices':
                        $profileLabelName = $this->profileFilterModel->typeToLabel($name);
                        $matchQuery = "(p)<-[rel$name:OPTION_OF]-(option$name:$profileLabelName)";
                        $whereQueries = array();
                        foreach ($value as $dataValue) {
                            $choice = $dataValue['choice'];
                            $detail = isset($dataValue['detail']) ? $dataValue['detail'] : null;

                            $whereQuery = " option$name.id = '$choice'";
                            if (!(null == $detail)) {
                                $whereQuery .= " AND rel$name.detail = '$detail'";
                            }

                            $whereQueries[] = $whereQuery;
                        }

                        $matches[] = $matchQuery . ' WHERE (' . implode('OR', $whereQueries) . ')';
                        break;
                    case 'tags':
                        $tagLabelName = $this->profileFilterModel->typeToLabel($name);
                        $matches[] = "(p)<-[:TAGGED]-(tag$name:$tagLabelName) WHERE tag$name.name = '$value'";
                        break;
                    case 'tags_and_choice':
                        $tagLabelName = $this->profileFilterModel->typeToLabel($name);
                        $matchQuery = "(p)<-[rel$name:TAGGED]-(tag$name:ProfileTag:$tagLabelName)";
                        $whereQueries = array();
                        foreach ($value as $dataValue) {
                            $tagValue = $name === 'language' ?
                                $this->profileFilterModel->getLanguageFromTag($dataValue['tag']) :
                                $dataValue['tag'];
                            $choice = isset($dataValue['choices']) ? $dataValue['choices'] : null;
                            $whereQuery = " tag$name.name = '$tagValue'";
                            if (!null == $choice) {
                                $whereQuery .= " AND rel$name.detail = '$choice'";
                            }

                            $whereQueries[] = $whereQuery;
                        }
                        $matches[] = $matchQuery . ' WHERE (' . implode('OR', $whereQueries . ')');
                        break;
                    case 'tags_and_multiple_choices':
                        $tagLabelName = $this->profileFilterModel->typeToLabel($name);
                        $matchQuery = "(p)<-[rel$name:TAGGED]-(tag$name:ProfileTag:$tagLabelName)";
                        $whereQueries = array();
                        foreach ($value as $dataValue) {
                            $tagValue = $name === 'language' ?
                                $this->profileFilterModel->getLanguageFromTag($dataValue['tag']) :
                                $dataValue['tag'];
                            $choices = isset($dataValue['choices']) ? $dataValue['choices'] : array();

                            $whereQuery = " tag$name.name = '$tagValue'";
                            if (!empty($choices)) {
                                $choices = json_encode($choices);
                                $whereQuery .= " AND rel$name.detail IN $choices ";
                            }
                            $whereQueries[] = $whereQuery;
                        }
                        $matches[] = $matchQuery . ' WHERE (' . implode('OR', $whereQueries) . ')';
                        break;
                    default:
                        break;
                }
            }
        }

        return array(
            'conditions' => $conditions,
            'matches' => $matches
        );
    }

    protected function getProfileFilterMetadata()
    {
        return $this->profileFilterModel->getFilters();
    }

    /**
     * @param array $filters
     * @return array
     */
    protected function getUserFilters(array $filters)
    {
        $conditions = array();
        $matches = array();

        $userFilterMetadata = $this->getUserFilterMetadata();
        foreach ($userFilterMetadata as $name => $filter) {
            if (isset($filters[$name]) && !empty($filters[$name])) {
                $value = $filters[$name];
                switch ($name) {
                    case 'groups':
                        $matches[] = $this->buildGroupMatch($value);
                        break;
                    case 'compatibility':
                        $conditions[] = $this->buildMatchingCondition($value);
                        break;
                    case 'similarity':
                        $conditions[] = $this->buildsimilarityCondition($value);
                        break;
                }
            }
        }

        return array(
            'conditions' => $conditions,
            'matches' => $matches
        );
    }

    /**
     * @param array $filters
     * @return array
     */
    protected function getPopularUserFilters(array $filters)
    {
        $conditions = array();
        $matches = array();

        $userFilterMetadata = $this->getUserFilterMetadata();
        foreach ($userFilterMetadata as $name => $filter) {
            if (isset($filters[$name]) && !empty($filters[$name])) {
                $value = $filters[$name];
                switch ($name) {
                    case 'groups':
                        $matches[] = $this->buildGroupMatch($value);
                        break;
                    case 'compatibility':
                        $conditions[] = 'false';
                        break;
                    case 'similarity':
                        $conditions[] = 'false';
                        break;
                }
            }
        }

        return array(
            'conditions' => $conditions,
            'matches' => $matches
        );
    }

    protected function getUserFilterMetadata()
    {
        return $this->userFilterModel->getFilters();
    }

    /**
     * @param ResultSet $result
     * @return UserRecommendation[]
     */
    public function buildUserRecommendations(ResultSet $result)
    {
        $response = array();
        foreach ($result as $row) {

            $age = null;
            if ($row['birthday']) {
                $date = new \DateTime($row['birthday']);
                $now = new \DateTime();
                $interval = $now->diff($date);
                $age = $interval->y;
            }

            $photo = $this->pm->createProfilePhoto();
            $photo->setPath($row->offsetGet('photo'));
            $photo->setUserId($row->offsetGet('id'));

            $user = new UserRecommendation();
            $user->setId($row->offsetGet('id'));
            $user->setUsername($row->offsetGet('username'));
            $user->setPhoto($photo);
            $user->setMatching($row->offsetGet('matching_questions'));
            $user->setSimilarity($row->offsetGet('similarity'));
            $user->setAge($age);
            $user->setLocation($row->offsetGet('location'));
            $user->setLike($row->offsetGet('like'));

            $response[] = $user;
        }

        return $response;
    }

    protected function buildGroupMatch($value)
    {
        foreach ($value as $index => $groupId) {
            $value[$index] = (int)$groupId;
        }
        $jsonValues = json_encode($value);

        return "(anyUser)-[:BELONGS_TO]->(group:Group) WHERE id(group) IN $jsonValues";
    }

    protected function buildMatchingCondition($value)
    {
        $valuePerOne = intval($value) / 100;

        return "($valuePerOne <= matching_questions)";
    }

    protected function buildsimilarityCondition($value)
    {
        $valuePerOne = intval($value) / 100;

        return "($valuePerOne <= similarity)";
    }
}