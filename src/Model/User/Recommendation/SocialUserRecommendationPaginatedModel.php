<?php
/**
 * @author yawmoght <yawmoght@gmail.com>
 */

namespace Model\User\Recommendation;


class SocialUserRecommendationPaginatedModel extends UserRecommendationPaginatedModel
{
    protected function getProfileFilterMetadata()
    {
        return $this->profileFilterModel->getSocialFilters(null);
    }

    protected function getUserFilterMetadata()
    {
        return $this->userFilterModel->getSocialFilters(null);
    }

}