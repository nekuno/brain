<?php

namespace Model\User\Matching;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;

class MatchingModel
{
    const PREFERRED_MATCHING_CONTENT='content';
    const PREFERRED_MATCHING_ANSWERS='answers';

    /**
     * @var average for the Normal Distribution. Average number of tags between any 2 users.
     */
    protected $ave_content;

    /**
     * @var standard deviation of the Normal Distribution. Based on number of tags between users
     */
    protected $stdev_content;

    /**
     * @var average for the Normal Distribution. Average number of answers answered by any user that are accepted by any other user
     */
    protected $ave_questions;

    /**
     * @var standard deviation for the Normal Distribution. Based on the number of answers in common between users.
     */
    protected $stdev_questions;

    /**
     * @var \Everyman\Neo4j\Client
     */
    protected $client;

    /**
     * @var \Model\User\ContentPaginatedModel
     */
    protected $contentPaginatedModel;

    /**
     * @var \Model\User\AnswerModel
     */
    protected $answerModel;

    /**
     * @param \Everyman\Neo4j\Client $client
     * @param \Model\User\ContentPaginatedModel $contentPaginatedModel
     * @param \Model\User\AnswerModel $answerModel
     */
    public function __construct(Client $client, ContentPaginatedModel $contentPaginatedModel, AnswerModel $answerModel)
    {
        $this->client = $client;
        $this->contentPaginatedModel = $contentPaginatedModel;
        $this->answerModel = $answerModel;
    }

    public function getPreferredMatchingType($id)
    {
        $numberOfSharedContent = $this->contentPaginatedModel->countTotal(array('id' => $id));
        $numberOfAnsweredQuestions = $this->answerModel->countTotal(array('id' => $id));

        if ($numberOfSharedContent > (2 * $numberOfAnsweredQuestions)) {
            return self::PREFERRED_MATCHING_CONTENT;
        } else {
            return self::PREFERRED_MATCHING_ANSWERS;
        }
    }

    
} 