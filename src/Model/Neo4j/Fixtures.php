<?php

namespace Model\Neo4j;

use Everyman\Neo4j\Cypher\Query;
use Model\LinkModel;
use Model\Questionnaire\QuestionModel;
use Model\UserModel;

class Fixtures
{

    const NUM_OF_USERS = 20;
    const NUM_OF_LINKS = 2000;
    const NUM_OF_TAGS = 20;
    const NUM_OF_QUESTIONS = 60;

    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var UserModel
     */
    protected $um;

    /**
     * @var LinkModel
     */
    protected $lm;

    /**
     * @var QuestionModel
     */
    protected $qm;

    public function __construct(GraphManager $gm, UserModel $um, LinkModel $lm, QuestionModel $qm)
    {
        $this->gm = $gm;
        $this->um = $um;
        $this->lm = $lm;
        $this->qm = $qm;
    }

    public function load()
    {

        $this->clean();
        $this->loadUsers();
        $this->loadLinks();
        $this->loadTags();
        $this->loadQuestions();

//        $this->createLinkTagRelationship(1, 6);
//        $this->createLinkTagRelationship(1, 7);
//
//        $this->createLinkTagRelationship(2, 8);
//        $this->createLinkTagRelationship(2, 1);
//
//        $this->createLinkTagRelationship(3, 1);
//        $this->createLinkTagRelationship(3, 2);
//        $this->createLinkTagRelationship(3, 3);
//
//        $this->createLinkTagRelationship(4, 1);
//        $this->createLinkTagRelationship(4, 2);
//        $this->createLinkTagRelationship(4, 3);
//        $this->createLinkTagRelationship(4, 9);
//
//        $this->createLinkTagRelationship(5, 1);
//        $this->createLinkTagRelationship(5, 5);
//
//        $this->createLinkTagRelationship(6, 2);
//
//        $this->createLinkTagRelationship(7, 3);
//        $this->createLinkTagRelationship(7, 4);
//
//        $this->createLinkTagRelationship(9, 10);
//
//        $this->createLinkTagRelationship(10, 11);
//        $this->createLinkTagRelationship(10, 12);
//
//        $this->createLinkTagRelationship(13, 13);
//        $this->createLinkTagRelationship(13, 4);
//
//        $this->createLinkTagRelationship(14, 3);
//        $this->createLinkTagRelationship(14, 14);
//
//        $this->createLinkTagRelationship(15, 3);
//
//        $this->createLinkTagRelationship(16, 15);
//        $this->createLinkTagRelationship(16, 16);
//        $this->createLinkTagRelationship(16, 17);
//
//        $this->createLinkTagRelationship(18, 18);
//        $this->createLinkTagRelationship(18, 19);
//        $this->createLinkTagRelationship(18, 20);
//
//        //User-content relationships
//        $this->createUserLikesLinkRelationship(1, 1);
//        $this->createUserLikesLinkRelationship(1, 2);
//        $this->createUserLikesLinkRelationship(1, 3);
//        $this->createUserLikesLinkRelationship(1, 4);
//        $this->createUserLikesLinkRelationship(1, 5);
//        $this->createUserLikesLinkRelationship(1, 6);
//        $this->createUserLikesLinkRelationship(1, 7);
//
//        $this->createUserLikesLinkRelationship(2, 1);
//        $this->createUserLikesLinkRelationship(2, 2);
//        $this->createUserLikesLinkRelationship(2, 3);
//        $this->createUserLikesLinkRelationship(2, 4);
//        $this->createUserLikesLinkRelationship(2, 5);
//
//        $this->createUserDislikesLinkRelationship(3, 4);
//        $this->createUserDislikesLinkRelationship(3, 5);
//        $this->createUserLikesLinkRelationship(3, 6);
//        $this->createUserLikesLinkRelationship(3, 7);
//
//        $this->createUserDislikesLinkRelationship(4, 4);
//        $this->createUserDislikesLinkRelationship(4, 5);
//        $this->createUserLikesLinkRelationship(4, 6);
//        $this->createUserLikesLinkRelationship(4, 7);
//
//        $this->createUserLikesLinkRelationship(5, 1);
//        $this->createUserLikesLinkRelationship(5, 7);
//        $this->createUserLikesLinkRelationship(5, 8);
//        $this->createUserLikesLinkRelationship(5, 9);
//        $this->createUserLikesLinkRelationship(5, 10);
//        $this->createUserLikesLinkRelationship(5, 11);
//        $this->createUserLikesLinkRelationship(5, 12);
//
//        $this->createUserDislikesLinkRelationship(6, 13);
//        $this->createUserDislikesLinkRelationship(6, 14);
//        $this->createUserLikesLinkRelationship(6, 15);
//        $this->createUserLikesLinkRelationship(6, 16);
//        $this->createUserLikesLinkRelationship(6, 17);
//        $this->createUserDislikesLinkRelationship(6, 18);
//        $this->createUserDislikesLinkRelationship(6, 19);
//        $this->createUserDislikesLinkRelationship(6, 11);
//        $this->createUserLikesLinkRelationship(6, 12);

        /**
         * User 3, answer to StoredQuestion 1 with Answer 1 and accepts as others answer [1,2]
         */
//        $userId = 4;
//        $storedQuestionsIndex = 0;
//        $rating = 1;
//        $questionId = $this->questions[$storedQuestionsIndex]['id'];
//        $answerId = $this->questions[$storedQuestionsIndex]['answers'][0];
//        $acceptsIds = array(
//            $this->questions[$storedQuestionsIndex]['answers'][0],
//        );
//        $this->userAnswerQuestion($userId, $questionId, $answerId, $acceptsIds, $rating);
//
//        $userId = 4;
//        $storedQuestionsIndex = 1;
//        $rating = 1;
//        $questionId = $this->questions[$storedQuestionsIndex]['id'];
//        $answerId = $this->questions[$storedQuestionsIndex]['answers'][0];
//        $acceptsIds = array(
//            $this->questions[$storedQuestionsIndex]['answers'][1]
//        );
//        $this->userAnswerQuestion($userId, $questionId, $answerId, $acceptsIds, $rating);
//
//        $userId = 5;
//        $storedQuestionsIndex = 1;
//        $rating = 1;
//        $questionId = $this->questions[$storedQuestionsIndex]['id'];
//        $answerId = $this->questions[$storedQuestionsIndex]['answers'][0];
//        $acceptsIds = array(
//            $this->questions[$storedQuestionsIndex]['answers'][0],
//        );
//        $this->userAnswerQuestion($userId, $questionId, $answerId, $acceptsIds, $rating);
//
//        $userId = 5;
//        $storedQuestionsIndex = 3;
//        $rating = 1;
//        $questionId = $this->questions[$storedQuestionsIndex]['id'];
//        $answerId = $this->questions[$storedQuestionsIndex]['answers'][1];
//        $acceptsIds = array(
//            $this->questions[$storedQuestionsIndex]['answers'][1],
//        );
//        $this->userAnswerQuestion($userId, $questionId, $answerId, $acceptsIds, $rating);
//
//        $userId = 6;
//        $storedQuestionsIndex = 1;
//        $rating = 1;
//        $questionId = $this->questions[$storedQuestionsIndex]['id'];
//        $answerId = $this->questions[$storedQuestionsIndex]['answers'][0];
//        $acceptsIds = array(
//            $this->questions[$storedQuestionsIndex]['answers'][1],
//        );
//        $this->userAnswerQuestion($userId, $questionId, $answerId, $acceptsIds, $rating);
//
//        $userId = 6;
//        $storedQuestionsIndex = 2;
//        $rating = 3;
//        $questionId = $this->questions[$storedQuestionsIndex]['id'];
//        $answerId = $this->questions[$storedQuestionsIndex]['answers'][1];
//        $acceptsIds = array(
//            $this->questions[$storedQuestionsIndex]['answers'][1],
//            $this->questions[$storedQuestionsIndex]['answers'][2],
//        );
//        $this->userAnswerQuestion($userId, $questionId, $answerId, $acceptsIds, $rating);
//
//        $userId = 7;
//        $storedQuestionsIndex = 1;
//        $rating = 3;
//        $questionId = $this->questions[$storedQuestionsIndex]['id'];
//        $answerId = $this->questions[$storedQuestionsIndex]['answers'][1];
//        $acceptsIds = array(
//            $this->questions[$storedQuestionsIndex]['answers'][0],
//        );
//        $this->userAnswerQuestion($userId, $questionId, $answerId, $acceptsIds, $rating);
//
//        $userId = 7;
//        $storedQuestionsIndex = 2;
//        $rating = 3;
//        $questionId = $this->questions[$storedQuestionsIndex]['id'];
//        $answerId = $this->questions[$storedQuestionsIndex]['answers'][1];
//        $acceptsIds = array(
//            $this->questions[$storedQuestionsIndex]['answers'][0],
//            $this->questions[$storedQuestionsIndex]['answers'][1],
//        );
//        $this->userAnswerQuestion($userId, $questionId, $answerId, $acceptsIds, $rating);
//
//        $userId = 7;
//        $storedQuestionsIndex = 3;
//        $rating = 1;
//        $questionId = $this->questions[$storedQuestionsIndex]['id'];
//        $answerId = $this->questions[$storedQuestionsIndex]['answers'][1];
//        $acceptsIds = array(
//            $this->questions[$storedQuestionsIndex]['answers'][1],
//        );
//        $this->userAnswerQuestion($userId, $questionId, $answerId, $acceptsIds, $rating);
//
//        for ($i = 41; $i <= self::NUM_OF_QUESTIONS; $i++) {
//            $key = $i - 1;
//            $questionId = $this->questions[$key]['id'];
//            $answerId = $this->questions[$key]['answers'][1];
//            $acceptsIds = array(
//                $this->questions[$key]['answers'][1],
//            );
//            $rating = 1;
//            $this->userAnswerQuestion(1, $questionId, $answerId, $acceptsIds, $rating);
//            $this->userAnswerQuestion(2, $questionId, $answerId, $acceptsIds, $rating);
//        }

    }

    protected function clean()
    {

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(n)')
            ->optionalMatch('(n)-[r]-()')
            ->delete('n, r');

        $query = $qb->getQuery();
        $query->getResultSet();
    }

    protected function loadUsers()
    {

        for ($i = 1; $i <= self::NUM_OF_USERS; $i++) {

            $this->um->create(
                array(
                    'id' => $i,
                    'username' => 'user' . $i,
                    'email' => 'user' . $i . '@nekuno.com',
                )
            );
        }
    }

    protected function loadLinks()
    {

        for ($i = 1; $i <= self::NUM_OF_LINKS; $i++) {

            $this->lm->addLink(
                array(
                    'userId' => 1,
                    'title' => 'Title ' . $i,
                    'description' => 'Description ' . $i,
                    'url' => 'https://www.nekuno.com/link' . $i,
                    'language' => 'en',
                )
            );
        }
    }

    protected function loadTags()
    {

        for ($i = 1; $i <= self::NUM_OF_TAGS; $i++) {

            $this->lm->createTag(
                array('name' => 'tag ' . $i,)
            );

            // This second call should be ignored and do not duplicate tags
            $this->lm->createTag(
                array('name' => 'tag ' . $i,)
            );
        }
    }

    protected function loadQuestions()
    {
        for ($i = 1; $i <= self::NUM_OF_QUESTIONS; $i++) {

            $answers = array();
            for ($j = 1; $j <= 4; $j++) {
                $answers[] = 'Answer ' . $j . ' to Question ' . $i;
            }

            $this->qm->create(
                array(
                    'locale' => 'en',
                    'text' => 'Question ' . $i,
                    'userId' => 1,
                    'answers' => $answers,
                )
            );
        }
    }

    /**
     * @param $link
     * @param $tag
     * @throws \Exception
     */
    protected function createLinkTagRelationship($link, $tag)
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
    protected function createUserLikesLinkRelationship($user, $link)
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
    protected function createUserDislikesLinkRelationship($user, $link)
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
     * @param $userId
     * @param $questionId
     * @param $answerId
     * @param array $acceptsIds
     * @param $rating
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    protected function userAnswerQuestion($userId, $questionId, $answerId, array $acceptsIds, $rating)
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