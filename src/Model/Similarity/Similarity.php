<?php

namespace Model\Similarity;

class Similarity implements \JsonSerializable
{
    protected $questions = 0;
    protected $questionsUpdated = 0;
    protected $interests = 0;
    protected $interestsUpdated = 0;
    protected $skills = 0;
    protected $skillsUpdated = 0;
    protected $similarity = 0;
    protected $similarityUpdated = 0;

    /**
     * @return mixed
     */
    public function getQuestions()
    {
        return $this->questions;
    }

    /**
     * @param mixed $questions
     */
    public function setQuestions($questions)
    {
        $this->questions = $questions;
    }

    /**
     * @return mixed
     */
    public function getQuestionsUpdated()
    {
        return $this->questionsUpdated;
    }

    /**
     * @param mixed $questionsUpdated
     */
    public function setQuestionsUpdated($questionsUpdated)
    {
        $this->questionsUpdated = $questionsUpdated;
    }

    /**
     * @return mixed
     */
    public function getInterests()
    {
        return $this->interests;
    }

    /**
     * @param mixed $interests
     */
    public function setInterests($interests)
    {
        $this->interests = $interests;
    }

    /**
     * @return mixed
     */
    public function getInterestsUpdated()
    {
        return $this->interestsUpdated;
    }

    /**
     * @param mixed $interestsUpdated
     */
    public function setInterestsUpdated($interestsUpdated)
    {
        $this->interestsUpdated = $interestsUpdated;
    }

    /**
     * @return mixed
     */
    public function getSkills()
    {
        return $this->skills;
    }

    /**
     * @param mixed $skills
     */
    public function setSkills($skills)
    {
        $this->skills = $skills;
    }

    /**
     * @return mixed
     */
    public function getSkillsUpdated()
    {
        return $this->skillsUpdated;
    }

    /**
     * @param mixed $skillsUpdated
     */
    public function setSkillsUpdated($skillsUpdated)
    {
        $this->skillsUpdated = $skillsUpdated;
    }

    /**
     * @return mixed
     */
    public function getSimilarity()
    {
        return $this->similarity;
    }

    /**
     * @param mixed $similarity
     */
    public function setSimilarity($similarity)
    {
        $this->similarity = $similarity;
    }

    /**
     * @return mixed
     */
    public function getSimilarityUpdated()
    {
        return $this->similarityUpdated;
    }

    /**
     * @param mixed $similarityUpdated
     */
    public function setSimilarityUpdated($similarityUpdated)
    {
        $this->similarityUpdated = $similarityUpdated;
    }

    public function jsonSerialize()
    {
        return array(
            'questions' => $this->questions,
            'interests' => $this->interests,
            'skills' => $this->skills,
            'similarity' => $this->similarity,
            'questionsUpdated' => $this->questionsUpdated,
            'interestsUpdated' => $this->interestsUpdated,
            'skillsUpdated' => $this->skillsUpdated,
            'similarityUpdated' => $this->similarityUpdated,
        );
    }

}