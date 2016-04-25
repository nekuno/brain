<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Model\User;


use Model\Neo4j\GraphManager;
use Model\User;

class UserFilterModel extends FilterModel
{
    /**
     * @param $userId
     * @return array
     */
    public function getChoiceOptionIds($userId)
    {
        $groups = $this->getGroupsIds($userId);

        $choices = array(
            'groups' => $groups,
        );

        return $choices;
    }

//TODO: Use groupModel->getAllByUserId when groupModel has not filterUsersManagers dependency (QS-982)
    private function getGroupsIds($userId)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb ->match('(u:User{qnoow_id: { userId }})')
            ->match('(g:Group)<-[:BELONGS_TO]-(u)')
            ->setParameter('userId', $userId)
            ->returns('id(g) as group');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        $return = array();
        foreach ($result as $row) {
            $return[] = $row->offsetGet('group');
        }

        return $return;
    }

    //TODO: Move common uses to FilterModel
    protected function modifyPublicFieldByType($publicField, $name, $values, $locale)
    {
        $publicField = parent::modifyPublicFieldByType($publicField, $name, $values, $locale);
        switch($values['type']) {
            case 'multiple_choices':
                $publicField['choices'] = array();
                if (isset($choiceOptions[$name])) {
                    $publicField['choices'] = $choiceOptions[$name];
                }
                $publicField['max_choices'] = isset($values['max_choices']) ? $values['max_choices'] : 999;
                break;
            default:
                break;
        }
    }


}