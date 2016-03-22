<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Model\User;


use Model\Neo4j\GraphManager;
use Model\User;

class UserFilterModel extends FilterModel
{
    protected $groupModel;

    public function __construct(GraphManager $gm, array $metadata, $defaultLocale, GroupModel $groupModel)
    {
        parent::__construct($gm, $metadata, $defaultLocale);

        $this->groupModel = $groupModel;
    }

    /**
     * @param null $locale
     * @param User $user
     * @return array
     */
    public function getFilters($locale = null, User $user = null)
    {
        $metadata = parent::getFilters($locale);

        //user-dependent filters
        if (null !== $user){
            $dynamicFilters = array();
            $dynamicFilters['groups'] = $this->groupModel->getByUser($user->getId());

            foreach ($dynamicFilters['groups'] as $group) {
                $metadata['groups']['choices'][$group['id']] = $group['name'];
            }

            if ($dynamicChoices['groups'] = null || $dynamicFilters['groups'] == array()) {
                unset($metadata['groups']);
            }
        }

        return $metadata;
    }




}