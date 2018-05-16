<?php

namespace Model\Recommendation;

use Everyman\Neo4j\Query\ResultSet;
use Everyman\Neo4j\Query\Row;
use Model\LanguageText\LanguageTextManager;
use Model\Photo\PhotoManager;
use Model\Metadata\MetadataUtilities;
use Model\Neo4j\GraphManager;
use Model\Profile\ProfileManager;
use Model\Metadata\UserFilterMetadataManager;
use Paginator\PaginatedInterface;

abstract class AbstractUserRecommendationPaginatedManager implements PaginatedInterface
{
    /**
     * @var GraphManager
     */
    protected $gm;

    protected $metadataUtilities;

    /**
     * @var UserFilterMetadataManager
     */
    protected $userFilterMetadataManager;

    /**
     * @var PhotoManager
     */
    protected $pm;

    /**
     * @var ProfileManager
     */
    protected $profileModel;

    protected $languageTextManager;

    public function __construct(GraphManager $gm, MetadataUtilities $metadataUtilities, UserFilterMetadataManager $userFilterMetadataManager, PhotoManager $pm, ProfileManager $profileModel, LanguageTextManager $languageTextManager)
    {
        $this->gm = $gm;
        $this->metadataUtilities = $metadataUtilities;
        $this->userFilterMetadataManager = $userFilterMetadataManager;
        $this->pm = $pm;
        $this->profileModel = $profileModel;
        $this->languageTextManager = $languageTextManager;
    }

    /**
     * Hook point for validating the query.
     * @param array $filters
     * @return boolean
     */
    public function validateFilters(array $filters)
    {
        $hasId = isset($filters['id']);

        return $hasId;
    }

    public function getUsersByPopularity(array $filtersArray, $offset, $limit, $additionalCondition = null)
    {
        $id = $filtersArray['id'];

        $parameters = array(
            'offset' => (integer)$offset,
            'limit' => (integer)$limit,
            'userId' => (integer)$id
        );

        $filters = $this->applyFilters($filtersArray);

        $qb = $this->gm->createQueryBuilder();

        $qb->setParameters($parameters);

        $qb->match('(anyUser:UserEnabled)')
            ->where('{userId} <> anyUser.qnoow_id');

        if (null !== $additionalCondition) {
            $qb->add('', $additionalCondition);
        }

        $qb->optionalMatch('(:User {qnoow_id: { userId }})-[m:MATCHES]-(anyUser)')
            ->optionalMatch('(:User {qnoow_id: { userId }})-[s:SIMILARITY]-(anyUser)')
            ->with(
                'distinct anyUser,
                (CASE WHEN EXISTS(m.matching_questions) THEN m.matching_questions ELSE 0.01 END) AS matching_questions,
                (CASE WHEN EXISTS(s.similarity) THEN s.similarity ELSE 0.01 END) AS similarity'
            )
            ->match('(anyUser)<-[:PROFILE_OF]-(p:Profile)');

        $qb->optionalMatch('(p)-[:LOCATION]->(l:Location)');

        $qb->with('anyUser, p, matching_questions', 'similarity');
        $qb->where($filters['conditions'])
            ->with('anyUser', 'p', 'matching_questions', 'similarity');

        foreach ($filters['matches'] as $match) {
            $qb->match($match);
        }

        $qb->with('anyUser, p, matching_questions', 'similarity')
            ->optionalMatch('(p)<-[optionOf:OPTION_OF]-(option:ProfileOption)')
            ->with(
                'anyUser, p, matching_questions, similarity',
                'collect(distinct {option: option, detail: (
                    CASE WHEN EXISTS(optionOf.detail) 
                        THEN optionOf.detail 
                        ELSE null 
                    END)})
                     AS options'
            )
            ->optionalMatch('(p)-[tagged:TAGGED]-(tag:ProfileTag)')
            ->with('anyUser, p, matching_questions', 'similarity', 'options', 'collect(distinct {tag: tag, tagged: tagged}) AS tags')
            ->optionalMatch('(anyUser)<-[likes:LIKES]-(:User)')
            ->with('anyUser, p, matching_questions', 'similarity', 'options', 'tags', 'count(likes) as popularity')
            ->with('anyUser', 'options', 'tags', 'popularity', 'p', 'matching_questions', 'similarity');

        $qb->returns(
            'anyUser.qnoow_id AS id',
            'anyUser.username AS username',
            'anyUser.slug AS slug',
            'anyUser.photo AS photo',
            'p.birthday AS birthday',
            'p AS profile',
            'options',
            'tags',
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

    protected function applyFilters(array $filters)
    {
        $filters = $filters['userFilters'];

        $conditions = array();
        $matches = array();

        $metadata = $this->userFilterMetadataManager->getMetadata();
        foreach ($metadata as $name => $filter) {
            $value = isset($filters[$name]) ? $filters[$name] : null;
            if ($value && !empty($value)) {
                if ($name == 'groups') {
                    $matches[] = $this->buildGroupMatch($value);
                    continue;
                }

                switch ($filter['type']) {
                    case 'text':
                    case 'textarea':
                        $conditions[] = "p.$name =~ '(?i).*$value.*'";
                        break;
                    case 'integer':
                        $conditions[] = $this->buildIntegerCondition($name, $value);
                        break;
                    case 'integer_range':
                        $min = isset($value['min']) ? (integer)$value['min'] : (isset($filter['min']) ? $filter['min'] : null);
                        $max = isset($value['max']) ? (integer)$value['max'] : (isset($filter['max']) ? $filter['max'] : null);
                        if ($min) {
                            $conditions[] = "($min <= p.$name)";
                        }
                        if ($max) {
                            $conditions[] = "(p.$name <= $max)";
                        }
                        break;
                    case 'birthday_range':
                        $age_min = isset($value['min']) ? $value['min'] : null;
                        $age_max = isset($value['max']) ? $value['max'] : null;
                        $birthdayRange = $this->metadataUtilities->getBirthdayRangeFromAgeRange($age_min, $age_max);
                        $min = $birthdayRange['min'];
                        $max = $birthdayRange['max'];
                        if ($min) {
                            $conditions[] = "('$min' <= p.$name)";
                        }
                        if ($max) {
                            $conditions[] = "(p.$name <= '$max')";
                        }
                        break;
                    case 'location_distance':
                        $distance = (int)$value['distance'];
                        $latitude = (float)$value['location']['latitude'];
                        $longitude = (float)$value['location']['longitude'];
                        $conditions[] = "(NOT l IS NULL AND EXISTS(l.latitude) AND EXISTS(l.longitude) AND
                        " . $distance . " >= toInt(6371 * acos( cos( radians(" . $latitude . ") ) * cos( radians(l.latitude) ) * cos( radians(l.longitude) - radians(" . $longitude . ") ) + sin( radians(" . $latitude . ") ) * sin( radians(l.latitude) ) )))";
                        break;
                    case 'boolean':
                        $conditions[] = "p.$name = true";
                        break;
                    case 'multiple_choices':
                        $query = $this->getChoiceMatch($name, $value);
                        $matches[] = $query;
                        break;
                    case 'double_choice':
                        $profileLabelName = $this->metadataUtilities->typeToLabel($name);
                        $value = implode("', '", $value);
                        $matches[] = "(p)<-[:OPTION_OF]-(option$name:$profileLabelName) WHERE option$name.id IN ['$value']";
                        break;
                    case 'double_multiple_choices':
                        $profileLabelName = $this->metadataUtilities->typeToLabel($name);
                        $matchQuery = "(p)<-[rel$name:OPTION_OF]-(option$name:$profileLabelName)";
                        $whereQueries = array();
                        $choices = $value['choices'];
                        $details = isset($value['details']) ? $value['details'] : null;
                        foreach ($choices as $choice) {
                            $whereQuery = " option$name.id = '$choice'";
                            if ($details) {
                                $whereQuery .= " AND (";
                                foreach ($details as $detail) {
                                    $whereQuery .= "rel$name.detail = '" . $detail . "' OR ";
                                }
                                $whereQuery = trim($whereQuery, 'OR ') . ')';
                            }
                            $whereQueries[] = $whereQuery;
                        }
                        $matches[] = $matchQuery . ' WHERE (' . implode(' OR ', $whereQueries) . ')';
                        break;
                    case 'choice_and_multiple_choices':
                        $profileLabelName = $this->metadataUtilities->typeToLabel($name);
                        $matchQuery = "(p)<-[rel$name:OPTION_OF]-(option$name:$profileLabelName)";
                        $whereQueries = array();
                        $choice = $value['choice'];
                        $details = isset($value['details']) ? $value['details'] : null;
                        $whereQuery = " option$name.id = '$choice'";
                        if ($details) {
                            $whereQuery .= " AND (";
                            foreach ($details as $detail) {
                                $whereQuery .= "rel$name.detail = '" . $detail . "' OR ";
                            }
                            $whereQuery = trim($whereQuery, 'OR ') . ')';
                        }
                        $whereQueries[] = $whereQuery;
                        $matches[] = $matchQuery . ' WHERE (' . implode(' OR ', $whereQueries) . ')';
                        break;
                    case 'tags':
                        $canonicalText = $this->languageTextManager->buildCanonical($value['name']);
                        $tagLabelName = $this->metadataUtilities->typeToLabel($name);
                        $matchQuery = "(p)<-[:TAGGED]-(tag$name:$tagLabelName)";
                        $whereQuery = " (tag$name)<-[:TEXT_OF]-(:TextLanguage{canonical: '$canonicalText'})";
                        $matches[] = $matchQuery . ' WHERE (' . $whereQuery . ')';
                        break;
                    case 'tags_and_choice':
                        $tagLabelName = $this->metadataUtilities->typeToLabel($name);
                        $matchQuery = "(p)<-[rel$name:TAGGED]-(tag$name:ProfileTag:$tagLabelName)";
                        $whereQueries = array();
                        foreach ($value as $dataValue) {
                            $tagValue = $dataValue['tag'];
                            $canonicalText = $this->languageTextManager->buildCanonical($tagValue['name']);
                            $choice = isset($dataValue['choices']) ? $dataValue['choices'] : null;
                            $whereQuery = " (tag$name)<-[:TEXT_OF]-(:TextLanguage{canonical: '$canonicalText'})";
                            if (!null == $choice) {
                                $whereQuery .= " AND rel$name.detail = '$choice'";
                            }

                            $whereQueries[] = $whereQuery;
                        }
                        $matches[] = $matchQuery . ' WHERE (' . implode('OR', $whereQueries) . ')';
                        break;
                    case 'tags_and_multiple_choices':
                        $tagLabelName = $this->metadataUtilities->typeToLabel($name);
                        $matchQuery = "(p)<-[rel$name:TAGGED]-(tag$name:ProfileTag:$tagLabelName)";
                        $whereQueries = array();
                        foreach ($value as $dataValue) {
                            $tagValue = $dataValue['tag'];
                            $canonicalText = $this->languageTextManager->buildCanonical($tagValue['name']);
                            $choices = isset($dataValue['choices']) ? $dataValue['choices'] : array();

                            $whereQuery = " (tag$name)<-[:TEXT_OF]-(:TextLanguage{canonical: '$canonicalText'})";
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

    protected function getChoiceMatch($name, $value)
    {
        $queries = array();

        $profileLabelName = $this->metadataUtilities->typeToLabel($name);
        $needsDescriptiveGenderFix = $name === 'descriptiveGender' && (in_array('man', $value) || in_array('woman', $value));
        if ($needsDescriptiveGenderFix) {
            $gendersArray = ['man' => 'male', 'woman' => 'female'];
            $filteringGenders = [];
            foreach ($gendersArray as $descriptive => $gender) {
                if (in_array($descriptive, $value)) {
                    $filteringGenders[] = $gender;
                }
            }
            $queries[] = $this->getChoiceMatch('gender', $filteringGenders);
            $value = array_diff($value, ['man', 'woman']);
        }
        if (!empty($value)) {
            $value = implode("', '", $value);
            $queries[] = "(p)<-[:OPTION_OF]-(option$name:$profileLabelName) WHERE option$name.id IN ['$value']";
        }

        return implode(' MATCH ', $queries);
    }

    /**
     * @param array $filters
     * @return array
     */
    protected function getPopularUserFilters(array $filters)
    {
        $conditions = array();
        $matches = array();

        $userFilterMetadata = $this->userFilterMetadataManager->getMetadata();
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

    /**
     * @param ResultSet $result
     * @return UserRecommendation[]
     */
    public function buildUserRecommendations(ResultSet $result)
    {
        $response = array();
        /** @var Row $row */
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
            $user->setSlug($row->offsetGet('slug'));
            $user->setPhoto($photo);
            $user->setMatching($row->offsetGet('matching_questions'));
            $user->setSimilarity($row->offsetGet('similarity'));
            $user->setAge($age);
            $user->setLike($row->offsetGet('like'));

            $profile = $this->profileModel->build($row);
            $user->setProfile($profile);
            if (!empty($profile->get('location'))) {
                $user->setLocation($profile->get('location'));
            }

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

    protected function buildIntegerCondition($name, $value)
    {
        switch ($name) {
            case 'compatibility':
                $attribute = 'matching_questions';
                break;
            case 'similarity':
                $attribute = 'similarity';
                break;
            default:
                return '';
        }

        $valuePerOne = intval($value) / 100;

        return "($valuePerOne <= $attribute)";
    }

    protected function getCanonicalTags($tags)
    {
        foreach ($tags as $tag)
        {

        }
    }
}