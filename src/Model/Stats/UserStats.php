<?php

namespace Model\Stats;


class UserStats
{

    protected $numberOfContentLikes;

    protected $numberOfVideoLikes;

    protected $numberOfAudioLikes;

    protected $numberOfImageLikes;

    protected $numberOfReceivedLikes;

    protected $numberOfUserLikes;

    protected $groupsBelonged;

    protected $numberOfQuestionsAnswered;

    protected $available_invitations;

    /**
     * @return mixed
     */
    public function getNumberOfContentLikes()
    {
        return $this->numberOfContentLikes;
    }

    /**
     * @return mixed
     */
    public function getNumberOfVideoLikes()
    {
        return $this->numberOfVideoLikes;
    }

    /**
     * @return mixed
     */
    public function getNumberOfAudioLikes()
    {
        return $this->numberOfAudioLikes;
    }

    /**
     * @return mixed
     */
    public function getNumberOfImageLikes()
    {
        return $this->numberOfImageLikes;
    }

    /**
     * @return mixed
     */
    public function getNumberOfReceivedLikes()
    {
        return $this->numberOfReceivedLikes;
    }

    /**
     * @return mixed
     */
    public function getNumberOfUserLikes()
    {
        return $this->numberOfUserLikes;
    }

    /**
     * @return mixed
     */
    public function getGroupsBelonged()
    {
        return $this->groupsBelonged;
    }

    /**
     * @return mixed
     */
    public function getNumberOfQuestionsAnswered()
    {
        return $this->numberOfQuestionsAnswered;
    }

    /**
     * @return integer
     */
    public function getAvailableInvitations()
    {
        return $this->available_invitations;
    }

    /**
     * @param mixed $numberOfContentLikes
     */
    public function setNumberOfContentLikes($numberOfContentLikes)
    {
        $this->numberOfContentLikes = $numberOfContentLikes;
    }

    /**
     * @param mixed $numberOfVideoLikes
     */
    public function setNumberOfVideoLikes($numberOfVideoLikes)
    {
        $this->numberOfVideoLikes = $numberOfVideoLikes;
    }

    /**
     * @param mixed $numberOfAudioLikes
     */
    public function setNumberOfAudioLikes($numberOfAudioLikes)
    {
        $this->numberOfAudioLikes = $numberOfAudioLikes;
    }

    /**
     * @param mixed $numberOfImageLikes
     */
    public function setNumberOfImageLikes($numberOfImageLikes)
    {
        $this->numberOfImageLikes = $numberOfImageLikes;
    }

    /**
     * @param mixed $numberOfReceivedLikes
     */
    public function setNumberOfReceivedLikes($numberOfReceivedLikes)
    {
        $this->numberOfReceivedLikes = $numberOfReceivedLikes;
    }

    /**
     * @param mixed $numberOfUserLikes
     */
    public function setNumberOfUserLikes($numberOfUserLikes)
    {
        $this->numberOfUserLikes = $numberOfUserLikes;
    }

    /**
     * @param mixed $groupsBelonged
     */
    public function setGroupsBelonged($groupsBelonged)
    {
        $this->groupsBelonged = $groupsBelonged;
    }

    /**
     * @param mixed $numberOfQuestionsAnswered
     */
    public function setNumberOfQuestionsAnswered($numberOfQuestionsAnswered)
    {
        $this->numberOfQuestionsAnswered = $numberOfQuestionsAnswered;
    }

    /**
     * @param mixed $available_invitations
     */
    public function setAvailableInvitations($available_invitations)
    {
        $this->available_invitations = $available_invitations;
    }

    public function toArray(){
        return array('numberOfContentLikes' => $this->numberOfContentLikes,
                     'numberOfVideoLikes' => $this->numberOfVideoLikes,
                     'numberOfAudioLikes' => $this->numberOfAudioLikes,
                     'numberOfImageLikes' => $this->numberOfImageLikes,
                     'numberOfReceivedLikes' => $this->numberOfReceivedLikes,
                     'numberOfUserLikes' => $this->numberOfUserLikes,
                     'groupsBelonged' => $this->groupsBelonged,
                     'numberOfQuestionsAnswered' => $this->numberOfQuestionsAnswered,
                     'available_invitations' => $this->available_invitations,
        );
    }

}