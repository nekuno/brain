<?php

namespace Model\User;


class UserStatsModel {

    protected $numberOfContentLikes;

    protected $numberOfVideoLikes;

    protected $numberOfAudioLikes;

    protected $numberOfImageLikes;

    protected $numberOfReceivedLikes;

    protected $numberOfUserLikes;

    protected $numberOfQuestionsAnswered;

    function __construct($numberOfContentLikes, $numberOfVideoLikes, $numberOfAudioLikes, $numberOfImageLikes, $numberOfReceivedLikes, $numberOfUserLikes, $numberOfQuestionsAnswered)
    {
        $this->numberOfContentLikes = $numberOfContentLikes;
        $this->numberOfVideoLikes = $numberOfVideoLikes;
        $this->numberOfAudioLikes = $numberOfAudioLikes;
        $this->numberOfImageLikes = $numberOfImageLikes;
        $this->numberOfReceivedLikes = $numberOfReceivedLikes;
        $this->numberOfUserLikes = $numberOfUserLikes;
        $this->numberOfQuestionsAnswered = $numberOfQuestionsAnswered;
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
    public function getNumberOfQuestionsAnswered()
    {
        return $this->numberOfQuestionsAnswered;
    }

    public function toArray(){
        return array('numberOfContentLikes' => $this->numberOfContentLikes,
                     'numberOfVideoLikes' => $this->numberOfVideoLikes,
                     'numberOfAudioLikes' => $this->numberOfAudioLikes,
                     'numberOfImageLikes' => $this->numberOfImageLikes,
                     'numberOfReceivedLikes' => $this->numberOfReceivedLikes,
                     'numberOfUserLikes' => $this->numberOfUserLikes,
                     'numberOfQuestionsAnswered' => $this->numberOfQuestionsAnswered,
        );
    }

}