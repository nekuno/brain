<?php

namespace Model\Recommendation;

class UserRecommendation extends AbstractUserRecommendation
{
    protected $like;
    protected $profile;
    protected $topLinks = array();

    /**
     * @return mixed
     */
    public function getLike()
    {
        return $this->like;
    }

    /**
     * @param mixed $like
     */
    public function setLike($like)
    {
        $this->like = $like;
    }

    /**
     * @return mixed $profile
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * @param mixed $profile
     */
    public function setProfile($profile)
    {
        $this->profile = $profile;
    }

    /**
     * @return array
     */
    public function getTopLinks()
    {
        return $this->topLinks;
    }

    /**
     * @param array $topLinks
     */
    public function setTopLinks($topLinks)
    {
        $this->topLinks = $topLinks;
    }

}