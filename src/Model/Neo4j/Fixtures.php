<?php
/**
 * Created by PhpStorm.
 * User: zaski
 * Date: 8/19/14
 * Time: 12:20 PM
 */

namespace Model\Neo4j;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;
use Model\User\UserStatusModel;

/**
 * Class Fixtures
 * @package Model\Neo4j
 */
class Fixtures
{

    const NUM_OF_USERS = 8;

    const NUM_OF_QUESTIONS = 33;

    /**
     * @var \Everyman\Neo4j\Client
     */
    protected $client;

    private $users = array();

    private $questions = array();

    /**
     * @param \Everyman\Neo4j\Client $client
     */
    public function __construct(Client $client)
    {

        $this->client = $client;
    }

    public function load()
    {

        $this->loadUsers(self::NUM_OF_USERS);

        $this->loadContent(19);//Contents are Links.
        $this->loadTags(20);

        $this->createLinkTagRelationship(1, 6);
        $this->createLinkTagRelationship(1, 7);

        $this->createLinkTagRelationship(2, 8);
        $this->createLinkTagRelationship(2, 1);

        $this->createLinkTagRelationship(3, 1);
        $this->createLinkTagRelationship(3, 2);
        $this->createLinkTagRelationship(3, 3);

        $this->createLinkTagRelationship(4, 1);
        $this->createLinkTagRelationship(4, 2);
        $this->createLinkTagRelationship(4, 3);
        $this->createLinkTagRelationship(4, 9);

        $this->createLinkTagRelationship(5, 1);
        $this->createLinkTagRelationship(5, 5);

        $this->createLinkTagRelationship(6, 2);

        $this->createLinkTagRelationship(7, 3);
        $this->createLinkTagRelationship(7, 4);

        $this->createLinkTagRelationship(9, 10);

        $this->createLinkTagRelationship(10, 11);
        $this->createLinkTagRelationship(10, 12);

        $this->createLinkTagRelationship(13, 13);
        $this->createLinkTagRelationship(13, 4);

        $this->createLinkTagRelationship(14, 3);
        $this->createLinkTagRelationship(14, 14);

        $this->createLinkTagRelationship(15, 3);

        $this->createLinkTagRelationship(16, 15);
        $this->createLinkTagRelationship(16, 16);
        $this->createLinkTagRelationship(16, 17);

        $this->createLinkTagRelationship(18, 18);
        $this->createLinkTagRelationship(18, 19);
        $this->createLinkTagRelationship(18, 20);

        //User-content relationships
        $this->createUserLikesLinkRelationship(1, 1);
        $this->createUserLikesLinkRelationship(1, 2);
        $this->createUserLikesLinkRelationship(1, 3);

        $this->createUserLikesLinkRelationship(2, 1);
        $this->createUserLikesLinkRelationship(2, 2);
        $this->createUserLikesLinkRelationship(2, 3);

        $this->createUserDislikesLinkRelationship(3, 4);
        $this->createUserDislikesLinkRelationship(3, 5);
        $this->createUserDislikesLinkRelationship(3, 6);

        $this->createUserDislikesLinkRelationship(4, 4);
        $this->createUserDislikesLinkRelationship(4, 5);
        $this->createUserDislikesLinkRelationship(4, 6);

        $this->createUserLikesLinkRelationship(5, 1);
        $this->createUserLikesLinkRelationship(5, 7);
        $this->createUserLikesLinkRelationship(5, 8);
        $this->createUserLikesLinkRelationship(5, 9);
        $this->createUserLikesLinkRelationship(5, 10);
        $this->createUserLikesLinkRelationship(5, 11);
        $this->createUserLikesLinkRelationship(5, 12);

        $this->createUserDislikesLinkRelationship(6, 13);
        $this->createUserDislikesLinkRelationship(6, 14);
        $this->createUserLikesLinkRelationship(6, 15);
        $this->createUserLikesLinkRelationship(6, 16);
        $this->createUserLikesLinkRelationship(6, 17);
        $this->createUserDislikesLinkRelationship(6, 18);
        $this->createUserDislikesLinkRelationship(6, 19);
        $this->createUserDislikesLinkRelationship(6, 11);
        $this->createUserLikesLinkRelationship(6, 12);

        //Questions
        for ($i = 1; $i <= self::NUM_OF_QUESTIONS; $i++) {
            $userId = rand(1, self::NUM_OF_USERS);
            $numOfAnswers = 3;
            $text = 'Question ' . $i;
            $this->loadQuestions($text, $numOfAnswers, $userId); //4 questions, 3 answers each
        }

        /**
         * User 3, answer to StoredQuestion 1 with Answer 1 and accepts as others answer [1,2]
         */
        $userId = 4;
        $storedQuestionsIndex = 0;
        $rating = 1;
        $questionId = $this->questions[$storedQuestionsIndex]['id'];
        $answerId = $this->questions[$storedQuestionsIndex]['answers'][0];
        $acceptsIds = array(
            $this->questions[$storedQuestionsIndex]['answers'][0],
        );
        $this->userAnswerQuestion($userId, $questionId, $answerId, $acceptsIds, $rating);

        $userId = 4;
        $storedQuestionsIndex = 1;
        $rating = 1;
        $questionId = $this->questions[$storedQuestionsIndex]['id'];
        $answerId = $this->questions[$storedQuestionsIndex]['answers'][0];
        $acceptsIds = array(
            $this->questions[$storedQuestionsIndex]['answers'][1]
        );
        $this->userAnswerQuestion($userId, $questionId, $answerId, $acceptsIds, $rating);

        $userId = 5;
        $storedQuestionsIndex = 1;
        $rating = 1;
        $questionId = $this->questions[$storedQuestionsIndex]['id'];
        $answerId = $this->questions[$storedQuestionsIndex]['answers'][0];
        $acceptsIds = array(
            $this->questions[$storedQuestionsIndex]['answers'][0],
        );
        $this->userAnswerQuestion($userId, $questionId, $answerId, $acceptsIds, $rating);

        $userId = 5;
        $storedQuestionsIndex = 3;
        $rating = 1;
        $questionId = $this->questions[$storedQuestionsIndex]['id'];
        $answerId = $this->questions[$storedQuestionsIndex]['answers'][1];
        $acceptsIds = array(
            $this->questions[$storedQuestionsIndex]['answers'][1],
        );
        $this->userAnswerQuestion($userId, $questionId, $answerId, $acceptsIds, $rating);

        $userId = 6;
        $storedQuestionsIndex = 1;
        $rating = 1;
        $questionId = $this->questions[$storedQuestionsIndex]['id'];
        $answerId = $this->questions[$storedQuestionsIndex]['answers'][0];
        $acceptsIds = array(
            $this->questions[$storedQuestionsIndex]['answers'][1],
        );
        $this->userAnswerQuestion($userId, $questionId, $answerId, $acceptsIds, $rating);

        $userId = 6;
        $storedQuestionsIndex = 2;
        $rating = 3;
        $questionId = $this->questions[$storedQuestionsIndex]['id'];
        $answerId = $this->questions[$storedQuestionsIndex]['answers'][1];
        $acceptsIds = array(
            $this->questions[$storedQuestionsIndex]['answers'][1],
            $this->questions[$storedQuestionsIndex]['answers'][2],
        );
        $this->userAnswerQuestion($userId, $questionId, $answerId, $acceptsIds, $rating);

        $userId = 7;
        $storedQuestionsIndex = 1;
        $rating = 3;
        $questionId = $this->questions[$storedQuestionsIndex]['id'];
        $answerId = $this->questions[$storedQuestionsIndex]['answers'][1];
        $acceptsIds = array(
            $this->questions[$storedQuestionsIndex]['answers'][0],
        );
        $this->userAnswerQuestion($userId, $questionId, $answerId, $acceptsIds, $rating);

        $userId = 7;
        $storedQuestionsIndex = 2;
        $rating = 3;
        $questionId = $this->questions[$storedQuestionsIndex]['id'];
        $answerId = $this->questions[$storedQuestionsIndex]['answers'][1];
        $acceptsIds = array(
            $this->questions[$storedQuestionsIndex]['answers'][0],
            $this->questions[$storedQuestionsIndex]['answers'][1],
        );
        $this->userAnswerQuestion($userId, $questionId, $answerId, $acceptsIds, $rating);

        $userId = 7;
        $storedQuestionsIndex = 3;
        $rating = 1;
        $questionId = $this->questions[$storedQuestionsIndex]['id'];
        $answerId = $this->questions[$storedQuestionsIndex]['answers'][1];
        $acceptsIds = array(
            $this->questions[$storedQuestionsIndex]['answers'][1],
        );
        $this->userAnswerQuestion($userId, $questionId, $answerId, $acceptsIds, $rating);
    }

    /**
     * @param $numberOfUsers
     * @throws \Exception
     */
    public function loadUsers($numberOfUsers)
    {

        $userToCheck = array();
        for ($i = 1; $i <= $numberOfUsers; $i++) {
            $userToCheck[] = $i;
        }

        $queryUserToCheck = implode(',', $userToCheck);
        $existingUsersQuery = " MATCH (u:User)"
            . " WHERE u.qnoow_id IN [" . $queryUserToCheck . "]"
            . " RETURN distinct u.qnoow_id;";

        $neo4jQuery = new Query(
            $this->client,
            $existingUsersQuery
        );
        $result = $neo4jQuery->getResultSet();

        $existingUsers = array();
        foreach ($result as $row) {
            $existingUsers[] = $row['qnoow_id'];
        }

        //Create queries in loop
        $userQuery = array();
        for ($i = 1; $i <= $numberOfUsers; $i++) {
            if (!in_array($i, $existingUsers)) {
                $userQuery[] = "CREATE (u:User {"
                    . " status: '" . UserStatusModel::USER_STATUS_INCOMPLETE . "',"
                    . " qnoow_id: " . $i . ","
                    . " username: 'user" . $i . "',"
                    . " email: 'testuser" . $i . "@test.test' })"
                    . " RETURN u;";
            }
        }

        //Execute queries in loop
        foreach ($userQuery as $query) {
            $neo4jQuery = new Query(
                $this->client,
                $query
            );

            try {
                $neo4jQuery->getResultSet();
            } catch (\Exception $e) {
                throw $e;
            }
        }

        return;

    }

    /**
     * @param $numberOfContents
     * @throws \Exception
     */
    public function loadContent($numberOfContents)
    {

        //Create queries in loop
        $contentQuery = array();
        for ($i = 1; $i <= $numberOfContents; $i++) {
            $contentQuery[] = "CREATE (l:Content:Link {"
                . " url: 'testLink" . $i . "',"
                . " description: 'test description " . $i . "',"
                . " processed: 0 })"
                . " RETURN l;";
        }

        //Execute queries in loop
        foreach ($contentQuery as $query) {
            $neo4jQuery = new Query(
                $this->client,
                $query
            );

            try {
                $neo4jQuery->getResultSet();
            } catch (\Exception $e) {
                throw $e;
            }
        }

        return;

    }

    /**
     * @param $numberOfTags
     * @throws \Exception
     */
    public function loadTags($numberOfTags)
    {

        //Create queries in loop
        $tagQuery = array();
        for ($i = 1; $i <= $numberOfTags; $i++) {
            $tagQuery[] = "CREATE (t:Tag { name: 'testTag" . $i . "' }) RETURN t;";
        }

        //Execute queries in loop
        foreach ($tagQuery as $query) {
            $neo4jQuery = new Query(
                $this->client,
                $query
            );

            try {
                $neo4jQuery->getResultSet();
            } catch (\Exception $e) {
                throw $e;
            }
        }

        return;

    }

    /**
     * @param $link
     * @param $tag
     * @throws \Exception
     */
    public function createLinkTagRelationship($link, $tag)
    {

        $relationshipQuery = "MATCH (l:Link {url: 'testLink" . $link . "'}), (t:Tag {name: 'testTag" . $tag . "'})"
            . " CREATE UNIQUE (l)-[r:TAGGED]->(t)"
            . " RETURN l, r, t ;";

        $neo4jQuery = new Query(
            $this->client,
            $relationshipQuery
        );

        try {
            $neo4jQuery->getResultSet();
        } catch (\Exception $e) {
            throw $e;
        }

        return;

    }

    /**
     * @param $user
     * @param $link
     * @throws \Exception
     */
    public function createUserLikesLinkRelationship($user, $link)
    {

        $relationshipQuery = "MATCH (l:Link {url: 'testLink" . $link . "'}), (u:User {qnoow_id: " . $user . "})"
            . " CREATE UNIQUE (l)<-[r:LIKES]-(u)"
            . " RETURN l, r, u ;";

        $neo4jQuery = new Query(
            $this->client,
            $relationshipQuery
        );

        try {
            $neo4jQuery->getResultSet();
        } catch (\Exception $e) {
            throw $e;
        }

        return;
    }

    /**
     * @param $user
     * @param $link
     * @throws \Exception
     */
    public function createUserDislikesLinkRelationship($user, $link)
    {

        $relationshipQuery = "MATCH (l:Link {url: 'testLink" . $link . "'}), (u:User {qnoow_id: " . $user . "})"
            . " CREATE UNIQUE (l)<-[r:DISLIKES]-(u)"
            . " RETURN l, r, u ;";

        $neo4jQuery = new Query(
            $this->client,
            $relationshipQuery
        );

        try {
            $neo4jQuery->getResultSet();
        } catch (\Exception $e) {
            throw $e;
        }

        return;
    }

    /**
     * @param $text
     * @param $numOfAnswers
     * @param int $userId
     * @return $this
     */
    public function loadQuestions($text, $numOfAnswers, $userId = 1)
    {

        // Check if exists?

        $answers = array();

        for ($i = 1; $i <= $numOfAnswers; $i++) {
            $answers[] = 'Answer ' . $i;
        }

        $data = array(
            'userId' => $userId,
            'text' => $text,
            'answers' => $answers
        );

        $template = "MATCH (u:User)"
            . " WHERE u.qnoow_id = {userId}"
            . " CREATE (q:Question)-[c:CREATED_BY]->(u)"
            . " SET q.text = {text}, q.timestamp = timestamp(), q.ranking = 0, c.timestamp = timestamp()"
            . " FOREACH (text in {answers}| CREATE (:Answer {text: text})-[:IS_ANSWER_OF]->(q))"
            . " WITH q"
            . " MATCH (q)<-[:IS_ANSWER_OF]-(a:Answer)"
            . " RETURN q AS question, collect(a) AS answers;";

        $query = new Query(
            $this->client,
            $template,
            $data
        );

        $result = $query->getResultSet();

        foreach ($result as $row) {
            $question = array();
            $question['createdBy'] = $data['userId'];
            $question['id'] = $row['question']->getId();
            foreach ($row['answers'] as $answer) {
                $question['answers'][] = $answer->getId();
            }
            $this->questions[] = $question;
        }

        return $this;
    }

    /**
     * @param $userId
     * @param $questionId
     * @param $answerId
     * @param array $acceptsIds
     * @param $rating
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    private function userAnswerQuestion($userId, $questionId, $answerId, array $acceptsIds, $rating)
    {

        $data = array(
            'userId' => (integer)$userId,
            'questionId' => (integer)$questionId,
            'answerId' => (integer)$answerId,
            'acceptedAnswers' => $acceptsIds,
            'rating' => $rating,
            'explanation' => '',
            'isPrivate' => false,
        );

        $template = "MATCH (user:User), (question:Question), (answer:Answer)"
            . " WHERE user.qnoow_id = {userId} AND id(question) = {questionId} AND id(answer) = {answerId}"
            . " CREATE UNIQUE (user)-[a:ANSWERS]->(answer)"
            . ", (user)-[r:RATES]->(question)"
            . " SET r.rating = {rating}, a.private = {isPrivate}"
            . ", a.answeredAt = timestamp(), a.explanation = {explanation}"
            . " WITH user, question, answer"
            . " OPTIONAL MATCH (pa:Answer)-[:IS_ANSWER_OF]->(question)"
            . " WHERE id(pa) IN {acceptedAnswers}"
            . " CREATE UNIQUE (user)-[:ACCEPTS]->(pa)"
            . " RETURN answer";

        $template .= ";";

        //Create the Neo4j query object
        $query = new Query(
            $this->client,
            $template,
            $data
        );

        return $query->getResultSet();
    }

}