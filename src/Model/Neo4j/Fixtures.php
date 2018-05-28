<?php

namespace Model\Neo4j;

use ApiConsumer\LinkProcessor\Processor\SpotifyProcessor\SpotifyTrackProcessor;
use ApiConsumer\LinkProcessor\Processor\SteamProcessor\SteamGameProcessor;
use ApiConsumer\LinkProcessor\Processor\YoutubeProcessor\YoutubeVideoProcessor;
use Model\Link\Audio;
use Model\Link\Game;
use Model\Link\Image;
use Model\Link\Link;
use Model\Link\LinkManager;
use Model\Link\Video;
use Model\Question\Admin\AnswerAdmin;
use Model\Question\Admin\QuestionAdmin;
use Model\Question\QuestionCorrelationManager;
use Model\User\User;
use Model\Question\AnswerManager;
use Model\Profile\ProfileManager;
use Model\Rate\RateManager;
use Model\User\UserManager;
use Model\EnterpriseUser\EnterpriseUserManager;
use Model\Group\GroupManager;
use Model\Invitation\InvitationManager;
use Model\Privacy\PrivacyManager;
use Psr\Log\LoggerInterface;
use Service\GroupService;
use Service\QuestionService;

class Fixtures
{

    const NUM_OF_USERS = 50;
    const NUM_OF_ENTERPRISE_USERS = 10;
    const NUM_OF_GROUPS = 15;
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
     * @var Constraints
     */
    protected $constraints;

    /**
     * @var UserManager
     */
    protected $um;

    /**
     * @var LinkManager
     */
    protected $lm;

    /**
     * @var QuestionService
     */
    protected $questionService;

    /**
     * @var QuestionCorrelationManager
     */
    protected $correlationManager;

    /**
     * @var AnswerManager
     */
    protected $am;

    /**
     * @var ProfileManager
     */
    protected $pm;

    /**
     * @var  PrivacyManager
     * */
    protected $prim;

    /**
     * @var EnterpriseUserManager
     */
    protected $eu;

    /**
     * @var GroupManager
     */
    protected $gpm;

    /**
     * @var GroupService
     */
    protected $groupService;

    /**
     * @var InvitationManager
     */
    protected $im;

    /**
     * @var array
     */
    protected $scenario = array();

    /**
     * @var QuestionAdmin[]
     */
    protected $questions = array();

    /**
     * @var RateManager
     */
    protected $rm;

    public function __construct(GraphManager $graphManager, Constraints $constraints, UserManager $userManager, EnterpriseUserManager $enterpriseUserManager, GroupManager $groupManager,
                                GroupService $groupService, InvitationManager $invitationManager, LinkManager $linkManager, QuestionService $questionService, QuestionCorrelationManager $questionCorrelationManager,
                                AnswerManager $answerManager, ProfileManager $profileManager, PrivacyManager $privacyManager, RateManager $rateManager)
    {
        $this->gm = $graphManager;
        $this->constraints = $constraints;
        $this->um = $userManager;
        $this->eu = $enterpriseUserManager;
        $this->gpm = $groupManager;
        $this->groupService = $groupService;
        $this->im = $invitationManager;
        $this->lm = $linkManager;
        $this->questionService = $questionService;
        $this->correlationManager = $questionCorrelationManager;
        $this->am = $answerManager;
        $this->pm = $profileManager;
        $this->prim = $privacyManager;
        $this->rm = $rateManager;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {

        $this->logger = $logger;
    }

    public function load($scenario = 1)
    {
        $this->scenario = $scenario;
        $this->clean();
        $this->loadProfileOptions();
        $this->loadPrivacyOptions();
        $this->loadUsers();
        $this->loadEnterpriseUsers();
        $this->loadInvitations();
        $this->loadGroups();
        $createdLinks = $this->loadLinks();
        $this->loadTags();
        $this->loadQuestions();
        $this->loadLinkTags();
        $this->loadLikes($createdLinks);
        $this->loadAnswers();
        $this->loadPrivacy();
        $this->calculateStatus();
        $this->calculateRegisterQuestions();
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

        $this->constraints->load();
        $this->logger->notice('Constraints created');
    }

    protected function loadUsers()
    {

        $this->logger->notice(sprintf('Loading %d users', self::NUM_OF_USERS));

        for ($i = 1; $i <= self::NUM_OF_USERS; $i++) {

            $this->um->create(
                array(
                    'username' => 'user' . $i,
                    'email' => 'user' . $i . '@nekuno.com',
                )
            );
            $profileData = array(
                'birthday' => '1970-01-01',
                'gender' => 'male',
                'orientation' => array('heterosexual'),
                'interfaceLanguage' => 'es',
                'location' => array(
                    'latitude' => 40.4167754,
                    'longitude' => -3.7037902,
                    'address' => 'Madrid',
                    'locality' => 'Madrid',
                    'country' => 'Spain'
                ),
                'mode' => 'contact',
                'objective' => array('human-contact')
            );
            $this->pm->create($i, $profileData);
        }
    }

    protected function loadEnterpriseUsers()
    {
        $this->logger->notice(sprintf('Loading %d enterprise users', self::NUM_OF_ENTERPRISE_USERS));

        for ($i = 1; $i <= self::NUM_OF_ENTERPRISE_USERS; $i++) {

            $this->eu->create(
                array(
                    'admin_id' => $i,
                    'username' => 'user' . $i,
                    'email' => 'user' . $i . '@nekuno.com',
                )
            );
        }
    }

    protected function loadInvitations()
    {
        $this->logger->notice('Loading generic invitation');

        $invitationData = array(
            'orientationRequired' => false,
            'available' => 10000,
            'token' => 'join',
        );

        $this->im->create($invitationData);

    }

    protected function loadGroups()
    {
        $this->logger->notice(sprintf('Loading %d enterprise groups and invitations', self::NUM_OF_ENTERPRISE_USERS));

        for ($i = 1; $i <= self::NUM_OF_ENTERPRISE_USERS; $i++) {

            $group = $this->groupService->createGroup(
                array(
                    'name' => 'group ' . $i,
                    'html' => $this->getHTMLFixture(),
                    'date' => 1447012842,
                    'location' => array(
                        'address' => "Madrid",
                        'latitude' => 40.416178,
                        'longitude' => -3.703579,
                        'locality' => "Madrid",
                        'country' => "Spain",
                    )
                )
            );
            $this->gpm->setCreatedByEnterpriseUser($group->getId(), $i);

            $invitationData = array(
                'groupId' => $group->getId(),
                'orientationRequired' => false,
                'available' => 100,
            );
            $invitation = $this->im->create($invitationData);

            foreach ($this->um->getAll() as $index => $user) {
                /* @var $user User */
                if ($index > 25) {
                    break;
                }
                $this->im->consume($invitation['invitation']['token'], $user->getId());
                $this->groupService->addUser($group->getId(), $user->getId());
            }
        }

        $this->logger->notice(sprintf('Loading %d groups', self::NUM_OF_GROUPS - self::NUM_OF_ENTERPRISE_USERS));

        for ($i = self::NUM_OF_ENTERPRISE_USERS + 1; $i <= self::NUM_OF_GROUPS - self::NUM_OF_ENTERPRISE_USERS; $i++) {
            $this->groupService->createGroup(
                array(
                    'name' => 'group ' . $i,
                    'html' => $this->getHTMLFixture(),
                    'date' => 1447012842,
                    'location' => array(
                        'address' => "Madrid",
                        'latitude' => 40.416178,
                        'longitude' => -3.703579,
                        'locality' => "Madrid",
                        'country' => "Spain",
                    )
                )
            );
        }
    }

    /**
     * @return Link[]
     * @throws \Exception
     */
    protected function loadLinks()
    {
        $this->logger->notice(sprintf('Loading %d links', self::NUM_OF_LINKS));

        $createdLinks = array();

        for ($i = 1; $i <= self::NUM_OF_LINKS; $i++) {

            if ($i <= 50) {
                $link = new Video();
                $link->addAdditionalLabels(YoutubeVideoProcessor::YOUTUBE_LABEL);
                $link->setUrl('https://www.youtube.com/watch?v=OPf0YbXqDm0' . '?' . $i);
                $link->setTitle('Mark Ronson - Uptown Funk ft. Bruno Mars - YouTube');
                $link->setDescription('Mark Ronson - Uptown Funk ft. Bruno Mars - YouTube');
                $link->setEmbedId('OPf0YbXqDm0');
                $link->setEmbedType('youtube');
                $link->addTag(array('name' => 'Video Tag 1'));
                $link->addTag(array('name' => 'Video Tag 2'));
                $link->addTag(array('name' => 'Video Tag 3'));
            } elseif ($i <= 150) {
                $link = new Audio();
                $link->addAdditionalLabels(SpotifyTrackProcessor::SPOTIFY_LABEL);
                $link->setUrl('https://open.spotify.com/album/3vLaOYCNCzngDf8QdBg2V1/32OlwWuMpZ6b0aN2RZOeMS' . '?' . $i);
                $link->setTitle('Uptown Funk');
                $link->setDescription('Uptown Special : Mark Ronson, Bruno Mars');
                $link->setEmbedId('spotify:track:32OlwWuMpZ6b0aN2RZOeMS');
                $link->setEmbedType('spotify');
                $link->addTag(array('name' => 'Uptown Funk', 'additionalLabels' => array('Song'), 'additionalFields' => array('spotifyId' => '32OlwWuMpZ6b0aN2RZOeMS', 'isrc' => 'GBARL1401524')));
                $link->addTag(array('name' => 'Bruno Mars', 'additionalLabels' => array('Artist'), 'additionalFields' => array('spotifyId' => '0du5cEVh5yTK9QJze8zA0C')));
                $link->addTag(array('name' => 'Mark Ronson', 'additionalLabels' => array('Artist'), 'additionalFields' => array('spotifyId' => '3hv9jJF3adDNsBSIQDqcjp')));
                $link->addTag(array('name' => 'Uptown Special', 'additionalLabels' => array('Album'), 'additionalFields' => array('spotifyId' => '3vLaOYCNCzngDf8QdBg2V1')));
            } elseif ($i <= 350) {
                $link = new Image();
                $link->setTitle('Title ' . $i);
                $link->setDescription('Description ' . $i);
                $link->setUrl('https://www.nekuno.com/link' . $i);
                $link->addTag(array('name' => 'Image Tag 7'));
                $link->addTag(array('name' => 'Image Tag 8'));
                $link->addTag(array('name' => 'Image Tag 9'));
            } else {
                $link = new Game();
                $link->addAdditionalLabels(SteamGameProcessor::STEAM_LABEL);
                $link->setTitle('Steam game ' . $i);
                $link->setDescription('Description ' . $i);
                $link->setUrl('https://www.nekuno.com/link' . $i);
                $link->addTag(array('name' => 'Game tag 10'));
                $link->addTag(array('name' => 'Game tag 11'));
            }

            $link->setLanguage('en');

            $createdLinks[$i] = $this->lm->mergeLink($link);

        }

        return $createdLinks;
    }

    protected function loadTags()
    {

        $this->logger->notice(sprintf('Loading %d tags', self::NUM_OF_TAGS));

        for ($i = 1; $i <= self::NUM_OF_TAGS; $i++) {

            $this->lm->mergeTag(
                array('name' => 'tag ' . $i,)
            );

            // This second call should be ignored and do not duplicate tags
            $this->lm->mergeTag(
                array('name' => 'tag ' . $i,)
            );
        }
    }

    protected function loadQuestions()
    {
        $this->logger->notice('Creating question categories');

        $this->questionService->createQuestionCategories();

        $this->logger->notice(sprintf('Loading %d questions', self::NUM_OF_QUESTIONS));

        $halfQuestions = (int)round(self::NUM_OF_QUESTIONS / 2);
        for ($i = 1; $i <= self::NUM_OF_QUESTIONS; $i++) {

            $questionText = $i < $halfQuestions ? 'Question ' . $i : 'Question ' . $i . '. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed vestibulum augue dolor, non malesuada tellus suscipit quis.';
            $data = array(
                'textEs' => $questionText,
                'textEn' => $questionText
            );

            $answers = array();
            for ($j = 1; $j <= 3; $j++) {
                $answers [] = 'Answer ' . $j . ' to ' . $questionText;
            }
            foreach ($answers as $index => $answer) {
                foreach (array('En', 'Es') as $locale) {
                    $key = 'answer' . ($index + 1) . $locale;
                    $data[$key] = $answer;
                }
            }

            $categories = $this->correlationManager->getAllModes();

            $data['categories'] = $categories;

            $this->questions[$i] = $this->questionService->createQuestion($data);
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

    /**
     * @param Link[] $createdLinks
     */
    protected function loadLikes(array $createdLinks)
    {
        $this->logger->notice('Loading likes');

        $likes = $this->scenario['likes'];

        foreach ($createdLinks as $link) {
            $this->rm->userRateLink(1, $link->getId());
        }

        foreach ($likes as $like) {
            foreach (range($like['linkFrom'], $like['linkTo']) as $i) {
                $this->rm->userRateLink($like['user'], $createdLinks[$i]->getId());
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
                /** @var AnswerAdmin[] $questionAnswers */
                $questionAnswers = $this->questions[$i]->getAnswers();
                foreach ($questionAnswers as $questionAnswer) {
                    $answerIds[] = $questionAnswer->toArray()['answerId'];
                }
                $questionId = $this->questions[$i]->getQuestionId();
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

    protected function loadPrivacy()
    {

        if (isset($this->scenario['privacy'])) {

            $this->logger->notice('Loading privacy');

            $privacy = $this->scenario['privacy'];

            foreach ($privacy as $userPrivacy) {
                $userId = $userPrivacy['user'];
                unset($userPrivacy['user']);

                $this->prim->create($userId, $userPrivacy);
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

    private function loadPrivacyOptions()
    {
        $privacyOptions = new PrivacyOptions($this->gm);

        $logger = $this->logger;
        $privacyOptions->setLogger($logger);

        try {
            $result = $privacyOptions->load();
        } catch (\Exception $e) {
            $logger->notice(
                'Error loading neo4j privacy options with message: ' . $e->getMessage()
            );

            return;
        }

        $logger->notice(sprintf('%d new privacy options processed.', $result->getTotal()));
        $logger->notice(sprintf('%d new privacy options updated.', $result->getUpdated()));
        $logger->notice(sprintf('%d new privacy options created.', $result->getCreated()));
    }

    private function getHTMLFixture()
    {
        return 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut malesuada risus elit, et vestibulum leo convallis ut. Aenean eu purus nulla. Nunc consectetur, lectus sed sagittis lacinia, lacus quam vestibulum sapien, sit amet condimentum enim odio quis mi. Morbi rutrum nulla sed ipsum convallis, eu ultricies sapien mollis. Aenean dui dui, bibendum id dui sit amet, interdum posuere massa. Sed sed lorem ac nunc aliquet posuere. Donec vitae nunc ut ante faucibus ornare eget at risus. Aenean sodales neque aliquam felis dapibus, ut ultrices tortor mattis. Fusce iaculis mi sem. Aliquam convallis dignissim augue, sit amet vehicula enim hendrerit eu. Cras pretium velit nisi, ac egestas nibh gravida vitae. Praesent consectetur posuere ex. Etiam eleifend orci ut porta imperdiet. Mauris finibus cursus ipsum, a facilisis orci sodales laoreet. Quisque pellentesque nulla vitae nulla tempus, vel porta tortor vehicula.

In hac habitasse platea dictumst. Sed fringilla ipsum vel feugiat efficitur. Mauris ut dui metus. Nam pellentesque laoreet justo vitae scelerisque. Nunc at sodales justo. Etiam mattis imperdiet posuere. In lacinia, tellus vitae semper ornare, lorem tellus vulputate mauris, in blandit urna elit vel sem. Morbi rhoncus mi libero, non rhoncus augue dictum eget. Vestibulum volutpat tincidunt dolor sit amet finibus. Nulla velit risus, ultrices in ex nec, fringilla pharetra leo. Nullam tristique molestie fringilla. Aenean sem ex, vehicula vel orci vel, porttitor imperdiet eros. Suspendisse eget orci aliquam, vehicula nibh ut, pellentesque sem. Ut varius risus tincidunt eros dapibus pellentesque. Integer interdum sem lacus, eget pretium dui dictum eget.

Donec eget elementum turpis. Sed auctor dui purus, vitae luctus leo posuere et. Cras varius sit amet turpis eu placerat. In pellentesque est a justo sagittis, ultrices accumsan purus bibendum. Aenean in quam vel libero ultrices fermentum at sit amet ante. Duis a dui at mauris porta placerat. Sed id augue nisl. Pellentesque aliquet ultrices dignissim. Nulla sed lacus libero.

Maecenas ac sem a quam aliquam scelerisque eu ut ligula. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Morbi eu enim sed libero rhoncus vulputate. Etiam posuere, ex ut rutrum aliquet, leo augue semper sapien, ac porta nulla nisi vitae orci. Nulla in purus commodo, bibendum erat convallis, vulputate mi. Donec ante justo, ullamcorper id urna ultrices, tristique ullamcorper dolor. Vestibulum magna eros, volutpat vitae porta eget, condimentum in orci. Sed elementum molestie orci. Nulla at massa egestas, dapibus eros ut, elementum risus. Nam eleifend mollis odio, a luctus libero condimentum in. Pellentesque fringilla, metus eget porttitor rutrum, lectus ipsum suscipit orci, non eleifend dolor nulla ut augue. Integer id velit congue, lobortis libero nec, tempus tortor. Nullam vestibulum finibus ex quis fermentum.

Duis venenatis porta arcu sed luctus. Quisque eu mi sit amet tellus porttitor vulputate eget eu lectus. Nam dolor leo, congue sed scelerisque in, gravida sed tellus. Pellentesque ultricies congue enim, sit amet suscipit elit sagittis nec. Quisque porttitor, ipsum sed molestie vulputate, lacus orci feugiat nunc, non dapibus mi velit non mauris. Maecenas dolor diam, commodo ut sollicitudin eget, vestibulum ac dui. Sed eget odio pulvinar, aliquet lectus luctus, pulvinar urna. Nullam sed rhoncus nunc. Duis convallis interdum odio, eu blandit ipsum. Duis sollicitudin et erat a posuere. Nullam varius aliquam sapien, et consequat erat sagittis quis. Vivamus varius, sem at finibus efficitur, nisl tortor lacinia massa, non molestie felis ante id nisl. Quisque feugiat suscipit metus congue accumsan.';
    }

    private function calculateRegisterQuestions()
    {
        $this->logger->notice('Calculating uncorrelated questions');
        $result = $this->correlationManager->getUncorrelatedQuestions();
        $count = 0;
        foreach ($result as $mode => $questionsByMode) {
            $this->correlationManager->setDivisiveQuestions($questionsByMode['questions'], $mode);
            $count += count($questionsByMode['questions']);
        }
        $this->logger->notice(sprintf('Obtained and saved %d questions', $count));

    }

}