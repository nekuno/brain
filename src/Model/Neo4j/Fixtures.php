<?php

namespace Model\Neo4j;

use Model\LinkModel;
use Model\Questionnaire\QuestionModel;
use Model\User\AnswerModel;
use Model\UserModel;

class Fixtures
{

    const NUM_OF_USERS = 20;
    const NUM_OF_LINKS = 2000;
    const NUM_OF_TAGS = 200;
    const NUM_OF_QUESTIONS = 200;

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

    /**
     * @var AnswerModel
     */
    protected $am;

    /**
     * @var array
     */
    protected $questions = array();

    public function __construct(GraphManager $gm, UserModel $um, LinkModel $lm, QuestionModel $qm, AnswerModel $am)
    {
        $this->gm = $gm;
        $this->um = $um;
        $this->lm = $lm;
        $this->qm = $qm;
        $this->am = $am;
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
        $this->loadAnswers();
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
            for ($j = 1; $j <= 3; $j++) {
                $answers[] = 'Answer ' . $j . ' to Question ' . $i;
            }

            $this->questions[$i] = $this->qm->create(
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

        $tag = 1;
        foreach (range(1, self::NUM_OF_LINKS) as $link) {
            foreach (range($tag, $tag + 3) as $tag) {
                if ($tag > self::NUM_OF_TAGS) {
                    $tag = 1;
                    break;
                }
                $this->lm->addTag(array('url' => 'https://www.nekuno.com/link' . $link), array('name' => 'tag ' . $tag));
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

    protected function loadAnswers()
    {

        $answers = array(
            array(
                'user' => 1,
                'answer' => 1,
                'questionFrom' => 1,
                'questionTo' => 20,
            ),
            array(
                'user' => 1,
                'answer' => 1,
                'questionFrom' => 21,
                'questionTo' => 31,
            ),
            array(
                'user' => 2,
                'answer' => 1,
                'questionFrom' => 1,
                'questionTo' => 20,
            ),
            array(
                'user' => 2,
                'answer' => 1,
                'questionFrom' => 31,
                'questionTo' => 41,
            ),
            // 18 common questions with same answer
            array(
                'user' => 3,
                'answer' => 1,
                'questionFrom' => 1,
                'questionTo' => 18,
            ),
            array(
                'user' => 4,
                'answer' => 1,
                'questionFrom' => 1,
                'questionTo' => 18,
            ),
            // 52 common questions
            array(
                'user' => 3,
                'answer' => 1,
                'questionFrom' => 19,
                'questionTo' => 52,
            ),
            array(
                'user' => 4,
                'answer' => 2,
                'questionFrom' => 19,
                'questionTo' => 52,
            ),
            // 120 and 78 questions in total
            array(
                'user' => 3,
                'answer' => 1,
                'questionFrom' => 53,
                'questionTo' => 120,
            ),
            array(
                'user' => 4,
                'answer' => 2,
                'questionFrom' => 121,
                'questionTo' => 127,
            ),
        );

        foreach ($answers as $answer) {

            foreach (range($answer['questionFrom'], $answer['questionTo']) as $i) {

                $answerIds = array_keys($this->questions[$i]['answers']);
                $questionId = $this->questions[$i]['id'];
                $answerId = $answerIds[$answer['answer'] - 1];
                $this->am->create(
                    array(
                        'userId' => $answer['user'],
                        'questionId' => $questionId,
                        'answerId' => $answerId,
                        'acceptedAnswers' => array($answerId),
                        'isPrivate' => false,
                        'rating' => 3,
                        'explanation' => '',
                    )
                );
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

}