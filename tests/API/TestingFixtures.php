<?php

namespace Tests\API;

use Model\Date\DateManager;
use Model\Date\DayPeriodManager;
use Model\Link\Audio;
use Model\Link\Image;
use Model\Link\Link;
use Model\Link\LinkManager;
use Model\Link\Video;
use Model\Neo4j\Constraints;
use Model\Neo4j\GraphManager;
use Model\Neo4j\PrivacyOptions;
use Model\Neo4j\ProfileOptions;
use Model\Photo\ProfileOptionGalleryManager;
use Model\Question\QuestionCorrelationManager;
use Model\Invitation\InvitationManager;
use Psr\Log\LoggerInterface;
use Service\GroupService;
use Service\LinkService;
use Service\QuestionService;
use Service\RegisterService;

class TestingFixtures
{

    const NUM_OF_LINKS = 20;
    const NUM_OF_TAGS = 10;
    const NUM_OF_QUESTIONS = 10;
    const USER_A_OAUTH_TOKEN = 'TESTING_OAUTH_TOKEN_A';
    const USER_B_OAUTH_TOKEN = 'TESTING_OAUTH_TOKEN_B';
    const USER_C_OAUTH_TOKEN = 'TESTING_OAUTH_TOKEN_C';
    const USER_D_OAUTH_TOKEN = 'TESTING_OAUTH_TOKEN_D';
    const USER_A_RESOURCE_ID = '12345';
    const USER_B_RESOURCE_ID = '54321';
    const USER_C_RESOURCE_ID = '67890';
    const USER_D_RESOURCE_ID = '06789';

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
     * @var RegisterService
     */
    protected $registerService;

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
     * @var GroupService
     */
    protected $groupService;

    /**
     * @var InvitationManager
     */
    protected $im;

    /**
     * @var DateManager
     */
    protected $dateManager;

    /**
     * @var DayPeriodManager
     */
    protected $dayPeriodManager;

    /**
     * @var LinkService
     */
    protected $linkService;

    /**
     * @var ProfileOptionGalleryManager
     */
    protected $profileOptionGalleryManager;

    public function __construct(
        GraphManager $graphManager,
        Constraints $constraints,
        RegisterService $registerService,
        GroupService $groupService,
        InvitationManager $invitationManager,
        LinkManager $linkManager,
        QuestionService $questionService,
        QuestionCorrelationManager $questionCorrelationManager,
        DateManager $dateManager,
        DayPeriodManager $dayPeriodManager,
        LinkService $linkService,
        ProfileOptionGalleryManager $profileOptionGalleryManager,
        LoggerInterface $logger
    )
    {
        $this->gm = $graphManager;
        $this->constraints = $constraints;
        $this->registerService = $registerService;
        $this->groupService = $groupService;
        $this->im = $invitationManager;
        $this->lm = $linkManager;
        $this->questionService = $questionService;
        $this->correlationManager = $questionCorrelationManager;
        $this->dateManager = $dateManager;
        $this->dayPeriodManager = $dayPeriodManager;
        $this->linkService = $linkService;
        $this->profileOptionGalleryManager = $profileOptionGalleryManager;
        $this->logger = $logger;
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
        $this->loadPrivacyOptions();
        $this->loadInvitations();
        $this->loadUsers();
        $this->loadGroups();
        $createdLinks = $this->loadLinks();
        $this->loadTags();
        $this->loadQuestions();
        $this->loadLinkTags();
        $this->loadLikes($createdLinks);
        $this->loadDates();
        $this->calculateRegisterQuestions();
    }

    protected function clean()
    {
        $this->logger->notice('Cleaning database');

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(n)')
            ->detachDelete('n');

        $query = $qb->getQuery();
        $query->getResultSet();

        $this->constraints->load();
        $this->logger->notice('Constraints created');
    }

    protected function loadInvitations()
    {
        $this->logger->notice('Loading generic invitation');

        $invitationData = array(
            'orientationRequired' => false,
            'available' => 10000,
            'token' => $this->getUserInvitationTokenFixtures(),
        );

        $this->im->create($invitationData);

    }

    protected function loadUsers()
    {
        $this->logger->notice('Loading UserA');

        $invitationTokenFixtures = $this->getUserInvitationTokenFixtures();

        $userAFixtures = $this->getUserADataFixtures();
        $profileAFixtures = $this->getUserAProfileFixtures();
        $tokenAFixtures = $this->getUserATokenFixtures();
        $trackingDataAFixtures = $this->getUserATrackingDataFixtures();

        $this->registerService->register($userAFixtures, $profileAFixtures, $invitationTokenFixtures, $tokenAFixtures, $trackingDataAFixtures);

        $this->logger->notice('Loading UserB');

        $userBFixtures = $this->getUserBFixtures();
        $profileBFixtures = $this->getUserBProfileFixtures();
        $tokenBFixtures = $this->getUserBTokenFixtures();
        $trackingDataBFixtures = $this->getUserBTrackingDataFixtures();

        $this->registerService->register($userBFixtures, $profileBFixtures, $invitationTokenFixtures, $tokenBFixtures, $trackingDataBFixtures);

        $this->logger->notice('Loading UserC');

        $userCFixtures = $this->getUserCFixtures();
        $profileCFixtures = $this->getUserCProfileFixtures();
        $tokenCFixtures = $this->getUserCTokenFixtures();
        $trackingDataCFixtures = $this->getUserCTrackingDataFixtures();

        $this->registerService->register($userCFixtures, $profileCFixtures, $invitationTokenFixtures, $tokenCFixtures, $trackingDataCFixtures);

        $this->logger->notice('Loading UserD');

        $userCFixtures = $this->getUserDFixtures();
        $profileCFixtures = $this->getUserDProfileFixtures();
        $tokenCFixtures = $this->getUserDTokenFixtures();
        $trackingDataCFixtures = $this->getUserDTrackingDataFixtures();

        $this->registerService->register($userCFixtures, $profileCFixtures, $invitationTokenFixtures, $tokenCFixtures, $trackingDataCFixtures);
    }

    protected function loadGroups()
    {
        $this->logger->notice('Loading testing group');

        $group = $this->groupService->createGroup(
            array(
                'name' => 'testing group',
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

        $invitationData = array(
            'groupId' => $group->getId(),
            'orientationRequired' => false,
            'available' => 100,
        );
       $this->im->create($invitationData);
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
                continue;
            }

            $link->setLanguage('en');

            $createdLinks[$i] = $this->linkService->merge($link);
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

            $this->questionService->createQuestion($data);
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

        foreach ($createdLinks as $index => $link) {
            if ($index <= 12){
                $this->linkService->like(1, $link->getId());
            }
            if ($index < 15 && $index > 5) {
                $this->linkService->like(2, $link->getId());
            }
            if ($index >= 8)
            {
                $this->linkService->like(3, $link->getId());
            }
            if ($index >= 11)
            {
                $this->linkService->like(4, $link->getId());
            }
        }
    }

    private function loadProfileOptions()
    {
        $profileOptions = new ProfileOptions($this->gm, $this->profileOptionGalleryManager);

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

    //TODO: Move to DateService
    private function loadDates()
    {
        $currentYear = date('Y');
        $endDate = ($currentYear + 1) . '-12-31';
        $this->dateManager->merge($endDate);
        $this->dayPeriodManager->createAll();
    }

    private function calculateRegisterQuestions()
    {
        $this->logger->notice('Calculating uncorrelated questions');
        $result = $this->correlationManager->getUncorrelatedQuestions();
        $count = 0;
        foreach ($result as $mode => $questionsByMode)
        {
            $this->correlationManager->setDivisiveQuestions($questionsByMode['questions'], $mode);
            $count += count($questionsByMode['questions']);
        }
        $this->logger->notice(sprintf('Obtained and saved %d questions', $count));

    }

    protected function getUserInvitationTokenFixtures()
    {
        return 'join';
    }

    protected function getUserADataFixtures()
    {
        return array(
            'username' => 'JohnDoe',
            'email' => 'nekuno-johndoe@gmail.com',
        );
    }

    protected function getUserAProfileFixtures()
    {
        return array(
            "birthday" => "1970-01-01",
            "location" => array(
                "locality" => "Madrid",
                "address" => "Madrid",
                "country" => "Spain",
                "longitude" => -3.7037902,
                "latitude" => 40.4167754
            ),
            "gender" => "male",
            "orientation" => array("heterosexual"),
            "interfaceLanguage" => "es",
            "mode" => "explore"
        );
    }

    protected function getUserATokenFixtures()
    {
        return array(
            'resourceOwner' => 'facebook',
            'oauthToken' => self::USER_A_OAUTH_TOKEN,
            'resourceId' => self::USER_A_RESOURCE_ID,
            'expireTime' => strtotime("+1 week"),
            'refreshToken' => null
        );
    }

    protected function getUserATrackingDataFixtures()
    {
        return '{}';
    }

    protected function getUserBFixtures()
    {
        return array(
            'username' => 'JaneDoe',
            'email' => 'nekuno-janedoe@gmail.com',
        );
    }

    protected function getUserBProfileFixtures()
    {
        return array(
            "birthday" => "1970-01-01",
            "location" => array(
                "locality" => "Madrid",
                "address" => "Madrid",
                "country" => "Spain",
                "longitude" => -3.7037902,
                "latitude" => 40.4167754
            ),
            "gender" => "male",
            "orientation" => array("heterosexual"),
            "interfaceLanguage" => "es",
            "mode" => "explore"
        );
    }

    protected function getUserBTokenFixtures()
    {
        return array(
            'resourceOwner' => 'facebook',
            'oauthToken' => self::USER_B_OAUTH_TOKEN,
            'resourceId' => self::USER_B_RESOURCE_ID,
            'expireTime' => strtotime("+1 week"),
            'refreshToken' => null
        );
    }

    protected function getUserBTrackingDataFixtures()
    {
        return '{}';
    }

    protected function getUserCFixtures()
    {
        return array(
            'username' => 'CarrieFisher',
            'email' => 'nekuno-carriefisher@gmail.com',
        );
    }

    protected function getUserCProfileFixtures()
    {
        return array(
            "birthday" => "1970-01-01",
            "location" => array(
                "locality" => "Madrid",
                "address" => "Madrid",
                "country" => "Spain",
                "longitude" => -3.7037902,
                "latitude" => 40.4167754
            ),
            "gender" => "male",
            "orientation" => array("heterosexual"),
            "interfaceLanguage" => "es",
            "mode" => "explore"
        );
    }

    protected function getUserCTokenFixtures()
    {
        return array(
            'resourceOwner' => 'facebook',
            'oauthToken' => self::USER_C_OAUTH_TOKEN,
            'resourceId' => self::USER_C_RESOURCE_ID,
            'expireTime' => strtotime("+1 week"),
            'refreshToken' => null
        );
    }

    protected function getUserCTrackingDataFixtures()
    {
        return '{}';
    }

    protected function getUserDFixtures()
    {
        return array(
            'username' => 'DavidTennant',
            'email' => 'david-tennant@gmail.com',
        );
    }

    protected function getUserDProfileFixtures()
    {
        return array(
            "birthday" => "1970-01-01",
            "location" => array(
                "locality" => "Madrid",
                "address" => "Madrid",
                "country" => "Spain",
                "longitude" => -3.7037902,
                "latitude" => 40.4167754
            ),
            "gender" => "male",
            "orientation" => array("heterosexual"),
            "interfaceLanguage" => "es",
            "mode" => "explore"
        );
    }

    protected function getUserDTokenFixtures()
    {
        return array(
            'resourceOwner' => 'facebook',
            'oauthToken' => self::USER_D_OAUTH_TOKEN,
            'resourceId' => self::USER_D_RESOURCE_ID,
            'expireTime' => strtotime("+1 week"),
            'refreshToken' => null
        );
    }

    protected function getUserDTrackingDataFixtures()
    {
        return '{}';
    }

    protected function getGroupFixtures()
    {
        return array(
            'name' => 'testing group',
            'html' => $this->getHTMLFixture(),
            'date' => 1447012842,
            'location' => array(
                'address' => "Madrid",
                'latitude' => 40.416178,
                'longitude' => -3.703579,
                'locality' => "Madrid",
                'country' => "Spain",
            )
        );
    }

    private function getHTMLFixture()
    {
        return 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut malesuada risus elit, et vestibulum leo convallis ut. Aenean eu purus nulla. Nunc consectetur, lectus sed sagittis lacinia, lacus quam vestibulum sapien, sit amet condimentum enim odio quis mi. Morbi rutrum nulla sed ipsum convallis, eu ultricies sapien mollis. Aenean dui dui, bibendum id dui sit amet, interdum posuere massa. Sed sed lorem ac nunc aliquet posuere. Donec vitae nunc ut ante faucibus ornare eget at risus. Aenean sodales neque aliquam felis dapibus, ut ultrices tortor mattis. Fusce iaculis mi sem. Aliquam convallis dignissim augue, sit amet vehicula enim hendrerit eu. Cras pretium velit nisi, ac egestas nibh gravida vitae. Praesent consectetur posuere ex. Etiam eleifend orci ut porta imperdiet. Mauris finibus cursus ipsum, a facilisis orci sodales laoreet. Quisque pellentesque nulla vitae nulla tempus, vel porta tortor vehicula.

In hac habitasse platea dictumst. Sed fringilla ipsum vel feugiat efficitur. Mauris ut dui metus. Nam pellentesque laoreet justo vitae scelerisque. Nunc at sodales justo. Etiam mattis imperdiet posuere. In lacinia, tellus vitae semper ornare, lorem tellus vulputate mauris, in blandit urna elit vel sem. Morbi rhoncus mi libero, non rhoncus augue dictum eget. Vestibulum volutpat tincidunt dolor sit amet finibus. Nulla velit risus, ultrices in ex nec, fringilla pharetra leo. Nullam tristique molestie fringilla. Aenean sem ex, vehicula vel orci vel, porttitor imperdiet eros. Suspendisse eget orci aliquam, vehicula nibh ut, pellentesque sem. Ut varius risus tincidunt eros dapibus pellentesque. Integer interdum sem lacus, eget pretium dui dictum eget.

Donec eget elementum turpis. Sed auctor dui purus, vitae luctus leo posuere et. Cras varius sit amet turpis eu placerat. In pellentesque est a justo sagittis, ultrices accumsan purus bibendum. Aenean in quam vel libero ultrices fermentum at sit amet ante. Duis a dui at mauris porta placerat. Sed id augue nisl. Pellentesque aliquet ultrices dignissim. Nulla sed lacus libero.

Maecenas ac sem a quam aliquam scelerisque eu ut ligula. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Morbi eu enim sed libero rhoncus vulputate. Etiam posuere, ex ut rutrum aliquet, leo augue semper sapien, ac porta nulla nisi vitae orci. Nulla in purus commodo, bibendum erat convallis, vulputate mi. Donec ante justo, ullamcorper id urna ultrices, tristique ullamcorper dolor. Vestibulum magna eros, volutpat vitae porta eget, condimentum in orci. Sed elementum molestie orci. Nulla at massa egestas, dapibus eros ut, elementum risus. Nam eleifend mollis odio, a luctus libero condimentum in. Pellentesque fringilla, metus eget porttitor rutrum, lectus ipsum suscipit orci, non eleifend dolor nulla ut augue. Integer id velit congue, lobortis libero nec, tempus tortor. Nullam vestibulum finibus ex quis fermentum.

Duis venenatis porta arcu sed luctus. Quisque eu mi sit amet tellus porttitor vulputate eget eu lectus. Nam dolor leo, congue sed scelerisque in, gravida sed tellus. Pellentesque ultricies congue enim, sit amet suscipit elit sagittis nec. Quisque porttitor, ipsum sed molestie vulputate, lacus orci feugiat nunc, non dapibus mi velit non mauris. Maecenas dolor diam, commodo ut sollicitudin eget, vestibulum ac dui. Sed eget odio pulvinar, aliquet lectus luctus, pulvinar urna. Nullam sed rhoncus nunc. Duis convallis interdum odio, eu blandit ipsum. Duis sollicitudin et erat a posuere. Nullam varius aliquam sapien, et consequat erat sagittis quis. Vivamus varius, sem at finibus efficitur, nisl tortor lacinia massa, non molestie felis ante id nisl. Quisque feugiat suscipit metus congue accumsan.';
    }
}