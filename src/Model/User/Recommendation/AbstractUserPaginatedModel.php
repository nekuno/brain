<?php

namespace Model\User\Recommendation;

use Model\Neo4j\GraphManager;
use Model\User\ProfileFilterModel;
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

    public function __construct(GraphManager $gm, ProfileFilterModel $profileFilterModel)
    {
        $this->gm = $gm;
        $this->profileFilterModel = $profileFilterModel;
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
                        foreach ($value as $dataValue){
                            $choice = $dataValue['choice'];
                            $detail = isset($dataValue['detail']) && !is_null($dataValue['detail']) ? $dataValue['detail'] : null;

                            $whereQuery = " option$name.id = '$choice'";
                            if (!(null==$detail)){
                                $whereQuery.= " AND rel$name.detail = '$detail'";
                            }

                            $whereQueries[] = $whereQuery;
                        }

                        $matches[] = $matchQuery.' WHERE (' . implode('OR', $whereQueries) . ')';
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
                            $choice = isset($dataValue['choices']) && !is_null($dataValue['choices']) ? $dataValue['choices'] : null;
                            $whereQuery = " tag$name.name = '$tagValue'";
                            if (!null==$choice){
                                $whereQuery.= " AND rel$name.detail = '$choice'";
                            }

                            $whereQueries[] = $whereQuery;
                        }
                        $matches[] = $matchQuery.' WHERE (' . implode('OR', $whereQueries . ')');
                        break;
                    case 'tags_and_multiple_choices':
                        $tagLabelName = $this->profileFilterModel->typeToLabel($name);
                        $matchQuery = "(p)<-[rel$name:TAGGED]-(tag$name:ProfileTag:$tagLabelName)";
                        $whereQueries = array();
                        foreach ($value as $dataValue) {
                            $tagValue = $name === 'language' ?
                                $this->profileFilterModel->getLanguageFromTag($dataValue['tag']) :
                                $dataValue['tag'];
                            $choices = isset($dataValue['choices']) && !is_null($dataValue['choices']) ? $dataValue['choices'] : array();

                            $whereQuery = " tag$name.name = '$tagValue'";
                            if (!empty($choices)){
                                $choices = json_encode($choices);
                                $whereQuery .= " AND rel$name.detail IN $choices ";
                            }
                            $whereQueries[] = $whereQuery;
                        }
                        $matches[] = $matchQuery.' WHERE (' . implode('OR', $whereQueries) . ')';
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

    protected function getProfileFilterMetadata(){
        return $this->profileFilterModel->getFilters();
    }
}