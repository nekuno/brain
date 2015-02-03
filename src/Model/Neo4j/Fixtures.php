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
        $this->loadLinkTags();
        $this->loadLikes();

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

    protected function loadLinkTags()
    {
        $tags = array(
            array(
                'link' => 1,
                'tagFrom' => 1,
                'tagTo' => 10,
            ),
            array(
                'link' => 2,
                'tagFrom' => 5,
                'tagTo' => 15,
            ),
            array(
                'link' => 3,
                'tagFrom' => 10,
                'tagTo' => 20,
            ),
        );

        foreach ($tags as $tag) {
            foreach (range($tag['tagFrom'], $tag['tagTo']) as $i) {
                $this->lm->addTag(array('url' => 'https://www.nekuno.com/link' . $tag['link']), array('name' => 'tag ' . $i));
            }
        }
    }

    protected function loadLikes()
    {
        $likes = array(
            array(
                'user' => 1,
                'linkFrom' => 1,
                'linkTo' => 1000,
            ),
            array(
                'user' => 2,
                'linkFrom' => 1,
                'linkTo' => 1000,
            ),
            array(
                'user' => 3,
                'linkFrom' => 1,
                'linkTo' => 100,
            ),
            array(
                'user' => 4,
                'linkFrom' => 50,
                'linkTo' => 150,
            ),
            array(
                'user' => 5,
                'linkFrom' => 1,
                'linkTo' => 15,
            ),
            array(
                'user' => 6,
                'linkFrom' => 10,
                'linkTo' => 25,
            ),
            array(
                'user' => 7,
                'linkFrom' => 1101,
                'linkTo' => 1115,
            ),
            array(
                'user' => 8,
                'linkFrom' => 1110,
                'linkTo' => 1125,
            ),
            array(
                'user' => 9,
                'linkFrom' => 1501,
                'linkTo' => 1511,
            ),
            array(
                'user' => 10,
                'linkFrom' => 1507,
                'linkTo' => 1515,
            ),
        );

        foreach ($likes as $like) {
            foreach (range($like['linkFrom'], $like['linkTo']) as $i) {
                $this->createUserLikesLinkRelationship($like['user'], $i);
            }
        }
    }

    protected function createUserLikesLinkRelationship($user, $link)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(l:Link {url: { url } })', '(u:User {qnoow_id: { qnoow_id } })')
            ->setParameter('url', 'https://www.nekuno.com/link' . $link)
            ->setParameter('qnoow_id', $user)
            ->createUnique('(l)<-[r:LIKES]-(u)')
            ->returns('l', 'u');

        $query = $qb->getQuery();
        $query->getResultSet();

    }

    protected function createUserDisLikesLinkRelationship($user, $link)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(l:Link {url: { url } })', '(u:User {qnoow_id: { qnoow_id } })')
            ->setParameter('url', 'https://www.nekuno.com/link' . $link)
            ->setParameter('qnoow_id', $user)
            ->createUnique('(l)<-[r:DISLIKES]-(u)')
            ->returns('l', 'u');

        $query = $qb->getQuery();
        $query->getResultSet();

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