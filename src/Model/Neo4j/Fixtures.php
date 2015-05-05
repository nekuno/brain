<?php

namespace Model\Neo4j;

use Everyman\Neo4j\Client;
use Model\LinkModel;
use Model\Questionnaire\QuestionModel;
use Model\User\AnswerModel;
use Model\User\ProfileModel;
use Model\UserModel;
use Psr\Log\LoggerInterface;
use Silex\Application;

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
     * @var ProfileModel
     */
    protected $pm;

    /**
     * @var array
     */
    protected $scenario = array();

    /**
     * @var array
     */
    protected $questions = array();

    public function __construct(Application $app, $scenario)
    {
        $this->gm = $app['neo4j.graph_manager'];
        $this->um = $app['users.model'];
        $this->lm = $app['links.model'];
        $this->qm = $app['questionnaire.questions.model'];
        $this->am = $app['users.answers.model'];
        $this->pm = $app['users.profile.model'];
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
        $this->loadProfileOptions();
        $this->loadUsers();
        $this->loadLinks();
        $this->loadTags();
        $this->loadQuestions();
        $this->loadLinkTags();
        $this->loadLikes();
        $this->loadAnswers();
        $this->calculateStatus();
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
            $profileData = array(
                'birthday' => '1970-01-01',
                'gender' => 'male',
                'orientation' => 'heterosexual',
                'interfaceLanguage' => 'es',
                'location' => array(
                    'latitude' => 40.4,
                    'longitude' => 3.683,
                    'address' => 'Madrid',
                    'locality' => 'Madrid',
                    'country' => 'Spain'
                )
            );
            $this->pm->create($i, $profileData);
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
                $link['url'] = 'https://www.youtube.com/watch?v=OPf0YbXqDm0' . '?' . $i;
                $link['title'] = 'Mark Ronson - Uptown Funk ft. Bruno Mars - YouTube';
                $link['description'] = 'Mark Ronson - Uptown Funk ft. Bruno Mars - YouTube';
                $link['additionalLabels'] = array('Video');
                $link['additionalFields'] = array('embed_type' => 'youtube', 'embed_id' => 'OPf0YbXqDm0');
                $link['tags'] = array(
                    array('name' => 'Video Tag 1'),
                    array('name' => 'Video Tag 2'),
                    array('name' => 'Video Tag 3'),
                );
            } elseif ($i <= 150) {
                $link['url'] = 'https://open.spotify.com/album/3vLaOYCNCzngDf8QdBg2V1/32OlwWuMpZ6b0aN2RZOeMS' . '?' . $i;
                $link['title'] = 'Uptown Funk';
                $link['description'] = 'Uptown Special : Mark Ronson, Bruno Mars';
                $link['additionalLabels'] = array('Audio');
                $link['additionalFields'] = array('embed_type' => 'spotify', 'embed_id' => 'spotify:track:32OlwWuMpZ6b0aN2RZOeMS');
                $link['tags'] = array(
                    array('name' => 'Uptown Funk', 'additionalLabels' => array('Song'), 'additionalFields' => array('spotifyId' => '32OlwWuMpZ6b0aN2RZOeMS', 'isrc' => 'GBARL1401524')),
                    array('name' => 'Bruno Mars', 'additionalLabels' => array('Artist'), 'additionalFields' => array('spotifyId' => '0du5cEVh5yTK9QJze8zA0C')),
                    array('name' => 'Mark Ronson', 'additionalLabels' => array('Artist'), 'additionalFields' => array('spotifyId' => '3hv9jJF3adDNsBSIQDqcjp')),
                    array('name' => 'Uptown Special', 'additionalLabels' => array('Album'), 'additionalFields' => array('spotifyId' => '3vLaOYCNCzngDf8QdBg2V1')),
                );
            } elseif ($i <= 350) {
                $link['additionalLabels'] = array('Image');
                $link['tags'] = array(
                    array('name' => 'Image Tag 7'),
                    array('name' => 'Image Tag 8'),
                    array('name' => 'Image Tag 9'),
                );
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

        $halfQuestions = (int)round(self::NUM_OF_QUESTIONS / 2);
        for ($i = 1; $i <= self::NUM_OF_QUESTIONS; $i++) {

            $answers = array();

            for ($j = 1; $j <= 3; $j++) {
                $answers[] = $i < $halfQuestions ?
                    array('text' => 'Answer ' . $j . ' to Question ' . $i) :
                    array('text' => 'Answer ' . $j . ' to Question ' . $i . '. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed vestibulum augue dolor, non malesuada tellus suscipit quis.');
            }

            $questionText = $i < $halfQuestions ? 'Question ' . $i : 'Question ' . $i . '. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed vestibulum augue dolor, non malesuada tellus suscipit quis.';
            $question = $this->qm->create(
                array(
                    'locale' => 'en',
                    'text' => $questionText,
                    'userId' => 1,
                    'answers' => $answers,
                )
            );

            $answers = $question['answers'];
            $j = 1;
            foreach ($answers as $answer) {
                $answers[] = $i < $halfQuestions ?
                    array('answerId' => $answer['answerId'], 'text' => 'Respuesta ' . $j . ' a la pregunta ' . $i) :
                    array('answerId' => $answer['answerId'], 'text' => 'Respuesta ' . $j . ' a la pregunta ' . $i . '. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed vestibulum augue dolor, non malesuada tellus suscipit quis.');
                $j++;
            }

            $questionText = $i < $halfQuestions ? 'Pregunta ' . $i : 'Pregunta ' . $i . '. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed vestibulum augue dolor, non malesuada tellus suscipit quis.';
            $this->qm->update(
                array(
                    'questionId' => $question['questionId'],
                    'locale' => 'es',
                    'text' => $questionText,
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

                $answerIds = array();
                foreach ($this->questions[$i]['answers'] as $questionAnswer) {
                    $answerIds[] = $questionAnswer['answerId'];
                }
                $questionId = $this->questions[$i]['questionId'];
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

    protected function calculateStatus()
    {
        $this->logger->notice(sprintf('Calculating status for %d users', self::NUM_OF_USERS));

        for ($i = 1; $i <= self::NUM_OF_USERS; $i++) {

            $status = $this->um->calculateStatus($i);
            $this->logger->notice(sprintf('Calculating user "%s" new status: "%s"', $i, $status->getStatus()));
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

    private function loadProfileOptions()
    {
        $profileOptions = new ProfileOptions($this->gm);

        $logger = $this->logger;
        $profileOptions->setLogger($logger);

        try {
            $result = $profileOptions->load();
        } catch (\Exception $e) {
            $logger->notice(
                'Error loading neo4j profile options with message: ' . $e->getMessage()
            );

            return;
        }

        $logger->notice(sprintf('%d new profile options processed.', $result->getTotal()));
        $logger->notice(sprintf('%d new profile options updated.', $result->getUpdated()));
        $logger->notice(sprintf('%d new profile options created.', $result->getCreated()));
    }

}