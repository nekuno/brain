<?php

namespace Model\User;

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
            'groups' => array(),
        );

        foreach ($groups as $group){
            $choices['groups'][$group] = $group;
        }
        
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
            default:
                break;
        }

        return $publicField;
    }
}