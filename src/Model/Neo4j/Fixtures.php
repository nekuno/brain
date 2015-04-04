<?php

namespace Model\Neo4j;

use Model\LinkModel;
use Model\Questionnaire\QuestionModel;
use Model\User\AnswerModel;
use Model\UserModel;
use Psr\Log\LoggerInterface;

class Fixtures
{

    const NUM_OF_USERS = 50;
    const NUM_OF_LINKS = 2000;
    const NUM_OF_TAGS = 200;
    const NUM_OF_QUESTIONS = 200;

    /**
     * @var LoggerInterface
     */
    protected $logger;

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
    protected $scenario = array();

    /**
     * @var array
     */
    protected $questions = array();

    public function __construct(GraphManager $gm, UserModel $um, LinkModel $lm, QuestionModel $qm, AnswerModel $am, $scenario)
    {
        $this->gm = $gm;
        $this->um = $um;
        $this->lm = $lm;
        $this->qm = $qm;
        $this->am = $am;
        $this->scenario = $scenario;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {

        $this->logger = $logger;
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

        $this->logger->notice('Cleaning database');

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(n)')
            ->optionalMatch('(n)-[r]-()')
            ->delete('n, r');

        $query = $qb->getQuery();
        $query->getResultSet();

        $constraints = $this->gm->createQuery('CREATE CONSTRAINT ON (u:User) ASSERT u.qnoow_id IS UNIQUE');
        $constraints->getResultSet();
    }

    protected function loadUsers()
    {

        $this->logger->notice(sprintf('Loading %d users', self::NUM_OF_USERS));

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

        $this->logger->notice(sprintf('Loading %d links', self::NUM_OF_LINKS));

        for ($i = 1; $i <= self::NUM_OF_LINKS; $i++) {

            $link = array(
                'userId' => 1,
                'title' => 'Title ' . $i,
                'description' => 'Description ' . $i,
                'url' => 'https://www.nekuno.com/link' . $i,
                'language' => 'en',
            );

            if ($i <= 50) {
                $link['additionalLabels'] = array('Video');
            } elseif ($i <= 150) {
                $link['additionalLabels'] = array('Audio');
            } elseif ($i <= 350) {
                $link['additionalLabels'] = array('Image');
            }

            $this->lm->addLink($link);

        }
    }

    protected function loadTags()
    {

        $this->logger->notice(sprintf('Loading %d tags', self::NUM_OF_TAGS));

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
        $this->logger->notice(sprintf('Loading %d questions', self::NUM_OF_QUESTIONS));

        for ($i = 1; $i <= self::NUM_OF_QUESTIONS; $i++) {

            $answers = array();
            for ($j = 1; $j <= 3; $j++) {
                $answers[] = 'Answer ' . $j . ' to Question ' . $i;
            }

            $question = $this->qm->create(
                array(
                    'locale' => 'en',
                    'text' => 'Question ' . $i,
                    'userId' => 1,
                    'answers' => $answers,
                )
            );

            $answers = $question['answers'];
            $j = 1;
            foreach ($answers as $id => $text) {
                $answers[$id] = 'Respuesta ' . $j . ' a la pregunta ' . $i;
                $j++;
            }

            $this->qm->update(
                array(
                    'id' => $question['id'],
                    'locale' => 'es',
                    'text' => 'Pregunta ' . $i,
                    'answers' => $answers,
                )
            );

            $this->questions[$i] = $question;

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

        $this->logger->notice('Loading likes');

        $likes = $this->scenario['likes'];

        foreach ($likes as $like) {
            foreach (range($like['linkFrom'], $like['linkTo']) as $i) {
                $this->createUserLikesLinkRelationship($like['user'], $i);
            }
        }
    }

    protected function loadAnswers()
    {
        $this->logger->notice('Loading answers');

        $answers = $this->scenario['answers'];

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
                        'locale' => 'en',
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