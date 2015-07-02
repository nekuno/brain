<?php

namespace Model\User;


class UserStatsModel
{

    protected $numberOfContentLikes;

    protected $numberOfVideoLikes;

    protected $numberOfAudioLikes;

    protected $numberOfImageLikes;

    protected $numberOfReceivedLikes;

    protected $numberOfUserLikes;

    protected $groupsBelonged;

    protected $numberOfQuestionsAnswered;

    protected $twitterFetched;

    protected $twitterProcessed;

    protected $facebookFetched;

    protected $facebookProcessed;

    protected $googleFetched;

    protected $googleProcessed;

    protected $spotifyFetched;

    protected $spotifyProcessed;

    protected $available_invitations;

    function __construct($numberOfContentLikes,
                         $numberOfVideoLikes,
                         $numberOfAudioLikes,
                         $numberOfImageLikes,
                         $numberOfReceivedLikes,
                         $numberOfUserLikes,
                         $groupsBelonged,
                         $numberOfQuestionsAnswered,
                         $twitterFetched,
                         $twitterProcessed,
                         $facebookFetched,
                         $facebookProcessed,
                         $googleFetched,
                         $googleProcessed,
                         $spotifyFetched,
                         $spotifyProcessed,
                         $available_invitations)
    {
        $this->numberOfContentLikes = $numberOfContentLikes;
        $this->numberOfVideoLikes = $numberOfVideoLikes;
        $this->numberOfAudioLikes = $numberOfAudioLikes;
        $this->numberOfImageLikes = $numberOfImageLikes;
        $this->numberOfReceivedLikes = $numberOfReceivedLikes;
        $this->numberOfUserLikes = $numberOfUserLikes;
        $this->groupsBelonged = $groupsBelonged;
        $this->numberOfQuestionsAnswered = $numberOfQuestionsAnswered;
        $this->twitterFetched = $twitterFetched;
        $this->twitterProcessed = $twitterProcessed;
        $this->facebookFetched = $facebookFetched;
        $this->facebookProcessed = $facebookProcessed;
        $this->googleFetched = $googleFetched;
        $this->googleProcessed = $googleProcessed;
        $this->spotifyFetched = $spotifyFetched;
        $this->spotifyProcessed = $spotifyProcessed;
        $this->available_invitations = $available_invitations;
    }

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
     * @return boolean
     */
    public function getTwitterFetched()
    {
        return $this->twitterFetched;
    }

    /**
     * @return boolean
     */
    public function getTwitterProcessed()
    {
        return $this->twitterProcessed;
    }

    /**
     * @return boolean
     */
    public function getFacebookFetched()
    {
        return $this->facebookFetched;
    }

    /**
     * @return boolean
     */
    public function getFacebookProcessed()
    {
        return $this->facebookProcessed;
    }

    /**
     * @return boolean
     */
    public function getGoogleFetched()
    {
        return $this->googleFetched;
    }

    /**
     * @return boolean
     */
    public function getGoogleProcessed()
    {
        return $this->googleProcessed;
    }

    /**
     * @return boolean
     */
    public function getSpotifyFetched()
    {
        return $this->spotifyFetched;
    }

    /**
     * @return boolean
     */
    public function getSpotifyProcessed()
    {
        return $this->spotifyProcessed;
    }

    /**
     * @return integer
     */
    public function getAvailableInvitations()
    {
        return $this->available_invitations;
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
                     'twitterFetched' => $this->twitterFetched,
                     'twitterProcessed' => $this->twitterProcessed,
                     'facebookFetched' => $this->facebookFetched,
                     'facebookProcessed' => $this->facebookProcessed,
                     'googleFetched' => $this->googleFetched,
                     'googleProcessed' => $this->googleProcessed,
                     'spotifyFetched' => $this->spotifyFetched,
                     'spotifyProcessed' => $this->spotifyProcessed,
                     'available_invitations' => $this->available_invitations,
        );
    }

}