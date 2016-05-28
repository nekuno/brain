<?php

namespace Model\User\Recommendation;

use Model\Neo4j\GraphManager;
use Model\User\GhostUser\GhostUserManager;
use Model\User\ProfileFilterModel;
use Model\User\UserFilterModel;
use Paginator\PaginatedInterface;

class UserPopularRecommendationPaginatedModel extends AbstractUserPaginatedModel
{

    protected $userFilterModel;

    public function __construct(GraphManager $gm, ProfileFilterModel $profileFilterModel, UserFilterModel $userFilterModel)
    {
        parent::__construct($gm, $profileFilterModel);
        $this->userFilterModel = $userFilterModel;
    }

    /**
     * Slices the query according to $offset, and $limit.
     * @param array $filters
     * @param int $offset
     * @param int $limit
     * @throws \Exception
     * @return array
     */
    public function slice(array $filters, $offset, $limit)
    {
        $id = $filters['id'];
        $response = array();

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
            ->with('anyUser')
            ->where($userFilters['conditions'])
            ->match('(anyUser)<-[:PROFILE_OF]-(p:Profile)');

        $qb->optionalMatch('(p)-[:LOCATION]->(l:Location)');

        $qb->with('anyUser, p, l');
        $qb->where($profileFilters['conditions'])
            ->with('anyUser', 'p', 'l');

        foreach ($profileFilters['matches'] as $match) {
            $qb->match($match);
        }
        foreach ($userFilters['matches'] as $match) {
            $qb->match($match);
        }

        $qb->with('anyUser, p, l')
            ->optionalMatch('(anyUser)<-[likes:LIKES]-(:User)')
            ->with('anyUser', 'count(likes) as popularity', 'p', 'l');
        
        $qb->returns(
            'DISTINCT anyUser.qnoow_id AS id,
                    anyUser.username AS username,
                    anyUser.picture AS picture,
                    p.birthday AS birthday,
                    l.locality + ", " + l.country AS location',
                    'popularity'
        )
            ->orderBy('popularity DESC')
            ->skip('{ offset }')
            ->limit('{ limit }');

        $query = $qb->getQuery();
        $result = $query->getResultSet();
        foreach ($result as $row) {

            $age = null;
            if ($row['birthday']) {
                $date = new \DateTime($row['birthday']);
                $now = new \DateTime();
                $interval = $now->diff($date);
                $age = $interval->y;
            }

            $user = array(
                'id' => $row['id'],
                'username' => $row['username'],
                'picture' => $row['picture'],
                'matching' => 0,
                'similarity' => $row['popularity'],
                'age' => $age,
                'location' => $row['location'],
                'like' => 0,
            );

            $response[] = $user;
        }

        return $response;
    }

    /**
     * Counts the total results from queryset.
     * @param array $filters
     * @throws \Exception
     * @return int
     */
    public function countTotal(array $filters)
    {
        $id = $filters['id'];
        $count = 0;

        $filters = $this->profileFilterModel->splitFilters($filters);

        $profileFilters = $this->getProfileFilters($filters['profileFilters']);
        $userFilters = $this->getUserFilters($filters['userFilters']);

        $qb = $this->gm->createQueryBuilder();

        $parameters = array('userId' => (integer)$id);

        $qb->setParameters($parameters);

        $qb->match('(u:User {qnoow_id: {userId}})-[:MATCHES|SIMILARITY]-(anyUser:User)')
            ->where('u <> anyUser')
            ->optionalMatch('(u)-[m:MATCHES]-(anyUser)')
            ->optionalMatch('(u)-[s:SIMILARITY]-(anyUser)')
            ->with(
                'u, anyUser,
            (CASE WHEN HAS(m.matching_questions) THEN m.matching_questions ELSE 0 END) AS matching_questions,
            (CASE WHEN HAS(s.similarity) THEN s.similarity ELSE 0 END) AS similarity'
            )
            ->where($userFilters['conditions'])
            ->match('(anyUser)<-[:PROFILE_OF]-(p:Profile)');

        $qb->optionalMatch('(p)-[:LOCATION]->(l:Location)');

        $qb->with('u, anyUser, matching_questions, similarity, p, l');
        $qb->where(
            array_merge(
                array('(matching_questions > 0 OR similarity > 0)'),
                $profileFilters['conditions']
            )
        )
            ->with('u', 'anyUser', 'matching_questions', 'similarity', 'p', 'l');

        foreach ($profileFilters['matches'] as $match) {
            $qb->match($match);
        }
        foreach ($userFilters['matches'] as $match) {
            $qb->match($match);
        }

        $qb->returns('COUNT(DISTINCT anyUser) as total');
        $query = $qb->getQuery();
        $result = $query->getResultSet();

        foreach ($result as $row) {
            $count = $row['total'];
        }

        return $count;
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
                        $conditions[] = "(NOT l IS NULL AND has(l.latitude) AND has(l.longitude) AND
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
                        foreach ($value as $dataValue){
                            $choice = $dataValue['choice'];
                            $detail = $dataValue['detail'];
                            $whereQueries[] = "( option$name.id = '$choice' AND rel$name.detail = '$detail')";
                        }

                        $matches[] = $matchQuery.' WHERE ' . implode('OR', $whereQueries);
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
                            $choice = !is_null($dataValue['choice']) ? $dataValue['choice'] : '';

                            $whereQueries[] = "( tag$name.name = '$tagValue' AND rel$name.detail = '$choice')";
                        }
                        $matches[] = $matchQuery.' WHERE ' . implode('OR', $whereQueries);
                        break;
                    case 'tags_and_multiple_choices':
                        $tagLabelName = $this->profileFilterModel->typeToLabel($name);
                        $matchQuery = "(p)<-[rel$name:TAGGED]-(tag$name:ProfileTag:$tagLabelName)";
                        $whereQueries = array();
                        foreach ($value as $dataValue) {
                            $tagValue = $name === 'language' ?
                                $this->profileFilterModel->getLanguageFromTag($dataValue['tag']) :
                                $dataValue['tag'];
                            $choices = !is_null($dataValue['choices']) ? json_encode($dataValue['choices']) : json_encode(array());

                            $whereQueries[] = "( tag$name.name = '$tagValue' AND rel$name.detail IN $choices )";
                        }
                        $matches[] = $matchQuery.' WHERE ' . implode('OR', $whereQueries);
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
                        foreach ($value as $index => $groupId) {
                            $value[$index] = (int)$groupId;
                        }
                        $jsonValues = json_encode($value);
                        $matches[] = "(anyUser)-[:BELONGS_TO]->(group:Group) WHERE id(group) IN $jsonValues";
                        break;
                    case 'compatibility':
                        $valuePerOne = intval($value) / 100;
                        $conditions[] = "($valuePerOne <= matching_questions)";
                        break;
                    case 'similarity':
                        $valuePerOne = intval($value) / 100;
                        $conditions[] = "($valuePerOne <= similarity)";
                        break;
                }
            }
        }

        return array(
            'conditions' => $conditions,
            'matches' => $matches
        );
    }

    protected function getProfileFilterMetadata(){
        return $this->profileFilterModel->getFilters();
    }

    protected function getUserFilterMetadata(){
        return $this->userFilterModel->getFilters();
    }
}