<?php

namespace Model\Thread;

use Model\Group\Group;
use Model\Location\Location;
use Model\Profile\Profile;
use Model\Profile\ProfileManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Translation\Translator;

class ThreadDataManager
{
    protected $profileModel;
    protected $translator;

    /**
     * ThreadDataManager constructor.
     * @param $profileModel
     * @param $translator
     */
    public function __construct(ProfileManager $profileModel, Translator $translator)
    {
        $this->profileModel = $profileModel;
        $this->translator = $translator;
    }

    /**
     * @param $userId
     * @param string $scenario
     * @return array
     */
    public function getDefaultThreads($userId, $scenario = ThreadManager::SCENARIO_DEFAULT)
    {
        try {
            $profile = $this->profileModel->getById($userId);
        } catch (NotFoundHttpException $e) {
            return array();
        }

        if (!$profile->get('birthday')) {
            $profile->set('birthday', '1970-01-01');
        }

        $locale = $profile->get('interfaceLanguage') ?: 'es';

        $this->translator->setLocale($locale);

        $location = $profile->get('location') ? $profile->get('location')->toArray(): $this->getDefaultLocation() ;

        $birthdayRange = $this->getAgeRangeFromProfile($profile);

        $genderDesired = $this->getDesiredFromProfile($profile);
        $nounDesired = $this->translator->trans('threads.default.' . $genderDesired);

        //specific order to be created from bottom to top
        $threads = array(
            ThreadManager::SCENARIO_DEFAULT => array(
                array(
                    'name' => $this->translator->trans('threads.default.twitter_channels'),
                    'category' => ThreadManager::LABEL_THREAD_CONTENT,
                    'filters' => array(
                        'contentFilters' => array(
                            'type' => array('Creator', 'LinkTwitter'),
                        ),
                    ),
                    'default' => true,
                ),
                array(
                    'name' => str_replace('%location%', $location['locality'], $this->translator->trans('threads.default.best_of_location')),
                    'category' => ThreadManager::LABEL_THREAD_CONTENT,
                    'filters' => array(
                        'contentFilters' => array(
                            'tags' => array($location['locality']),
                        ),
                    ),
                    'default' => true,
                ),
                array(
                    'name' => $this->translator->trans('threads.default.youtube_videos'),
                    'category' => ThreadManager::LABEL_THREAD_CONTENT,
                    'filters' => array(
                        'contentFilters' => array(
                            'type' => array('Video')
                        ),
                    ),
                    'default' => true,
                ),
                array(
                    'name' => $this->translator->trans('threads.default.spotify_music'),
                    'category' => ThreadManager::LABEL_THREAD_CONTENT,
                    'filters' => array(
                        'contentFilters' => array(
                            'type' => array('Audio')
                        ),
                    ),
                    'default' => true,
                ),
                array(
                    'name' => $this->translator->trans('threads.default.images'),
                    'category' => ThreadManager::LABEL_THREAD_CONTENT,
                    'filters' => array(
                        'contentFilters' => array(
                            'type' => array('Image')
                        ),
                    ),
                    'default' => true,
                ),
                array(
                    'name' => str_replace(
                        array('%desired%', '%location%'),
                        array($nounDesired, $location['locality']),
                        $this->translator->trans('threads.default.desired_from_location')
                    ),
                    'category' => ThreadManager::LABEL_THREAD_USERS,
                    'filters' => array(
                        'userFilters' => array(
                            'birthday' => array(
                                'min' => $birthdayRange['min'],
                                'max' => $birthdayRange['max'],
                            ),
                            'location' => array(
                                'distance' => 50,
                                'location' => $location
                            ),
                            'descriptiveGender' => array($genderDesired !== 'people' ? $genderDesired : null),
                        ),
                        'order' => 'content',
                    ),
                    'default' => true,
                ),
            ),
            ThreadManager::SCENARIO_DEFAULT_LITE => array(
                array(
                    'name' => str_replace(
                        array('%desired%', '%location%'),
                        array($nounDesired, $location['locality']),
                        $this->translator->trans('threads.default.desired_from_location')
                    ),
                    'category' => ThreadManager::LABEL_THREAD_USERS,
                    'filters' => array(
                        'userFilters' => array(
                            'birthday' => array(
                                'min' => 18,
                                'max' => 80,
                            ),
                            'descriptiveGender' => array($genderDesired !== 'people' ? $genderDesired : null),
                        ),
                        'order' => 'content',
                    ),
                    'default' => true,
                ),
            ),
            ThreadManager::SCENARIO_NONE => array(),
        );

        if (!isset($threads[$scenario])) {
            return array();
        }

        $this->fixDescriptiveGenderFilter($threads[$scenario]);

        return $threads[$scenario];
    }

    private function fixDescriptiveGenderFilter(&$threads)
    {
        foreach ($threads as &$thread) {
            if (isset($thread['filters']['userFilters']) && isset($thread['filters']['userFilters']['descriptiveGender']) && $thread['filters']['userFilters']['descriptiveGender'] == array(null)) {
                unset($thread['filters']['userFilters']['descriptiveGender']);
            }
        }
    }

    private function getDesiredFromProfile(Profile $profile)
    {
        //QS-1001: Changed for now
//        if (!isset($profile['orientation']) || !isset($profile['gender'])) {
//            return 'people';
//        }
//
//        if ($profile['orientation'] == 'heterosexual') {
//            return $profile['gender'] === 'male' ? 'female' : 'male';
//        }
//
//        if ($profile['orientation'] == 'homosexual') {
//            return $profile['gender'] === 'male' ? 'male' : 'female';
//        }
//
//        if ($profile['orientation'] == 'bisexual') {
//            return 'people';
//        }

        return 'people';
    }

    private function getAgeRangeFromProfile(Profile $profile)
    {
        $birthday = $profile->get('birthday');
        $ageRangeMax = new \DateInterval('P5Y');
        $ageRangeMin = new \DateInterval('P5Y');
        $ageRangeMin->invert = 1;
        $rawAgeMin = (new \DateTime($birthday))->add($ageRangeMax)->diff(new \DateTime())->y;
        $rawAgeMax = (new \DateTime($birthday))->add($ageRangeMin)->diff(new \DateTime())->y;

        return array(
            'max' => $rawAgeMax <= 99 ? ($rawAgeMax >= 14 ? $rawAgeMax : 14) : 99,
            'min' => $rawAgeMin <= 99 ? ($rawAgeMin >= 14 ? $rawAgeMin : 14) : 99,
        );
    }

    protected function getDefaultLocation()
    {
        $location = new Location();
        $location->setLatitude(40.4167754);
        $location->setLongitude(-3.7037902);
        $location->setAddress('Madrid');
        $location->setLocality('Madrid');
        $location->setCountry('Spain');

        return $location->toArray();
    }

    public function getGroupThreadData(Group $group, $userId)
    {
        try {
            $locale = $this->profileModel->getInterfaceLocale($userId);
            $this->translator->setLocale($locale);
        } catch (NotFoundHttpException $e) {
            return array();
        }

        return array(
            'name' => str_replace('%group%', $group->getName(), $this->translator->trans('threads.default.people_from_group')),
            'category' => ThreadManager::LABEL_THREAD_USERS,
            'filters' => array(
                'userFilters' => array(
                    'groups' => array($group->getId()),
                )
            ),
            'default' => false,
        );
    }
}