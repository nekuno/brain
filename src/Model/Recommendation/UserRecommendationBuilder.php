<?php

namespace Model\Recommendation;

use Everyman\Neo4j\Query\ResultSet;
use Everyman\Neo4j\Query\Row;
use Model\Location\LocationManager;
use Model\Photo\PhotoManager;
use Model\Profile\ProfileManager;
use Model\Recommendation\Proposal\ProposalCandidateRecommendation;

class UserRecommendationBuilder
{
    protected $photoManager;
    protected $profileManager;
    protected $locationManager;

    /**
     * UserRecommendationBuilder constructor.
     * @param $photoManager
     * @param $profileManager
     */
    public function __construct(PhotoManager $photoManager, ProfileManager $profileManager, LocationManager $locationManager)
    {
        $this->photoManager = $photoManager;
        $this->profileManager = $profileManager;
        $this->locationManager = $locationManager;
    }

    //TODO: Refactor to use protected buildBasicData
    /**
     * @param ResultSet $result
     * @return UserRecommendation[]
     */
    public function buildUserRecommendations(ResultSet $result)
    {
        $response = array();
        /** @var Row $row */
        foreach ($result as $row) {

            $age = $this->getAgeFromBirthday($row);

            $photo = $this->photoManager->createProfilePhoto();
            $photo->setPath($row->offsetGet('photo'));
            $photo->setUserId($row->offsetGet('id'));

            $user = new UserRecommendation();
            $user->setId($row->offsetGet('id'));
            $user->setUsername($row->offsetGet('username'));
            $user->setSlug($row->offsetGet('slug'));
            $user->setPhoto($photo);
            $user->setMatching($row->offsetGet('matching_questions'));
            $user->setSimilarity($row->offsetGet('similarity'));
            $user->setAge($age);
            $user->setLike($row->offsetGet('like'));

            $profile = $this->profileManager->build($row);
            $user->setProfile($profile);
            if (!empty($profile->get('location'))) {
                $user->setLocation($profile->get('location'));
            }

            $response[] = $user;
        }

        return $response;
    }

    /**
     * @param array $data
     * @return ProposalCandidateRecommendation[]
     */
    public function buildCandidates(array $data)
    {
        $response = array();
        /** @var Row $row */
        foreach ($data as $row) {

            $age = $this->getAgeFromBirthday($row);

            $photo = $this->photoManager->createProfilePhoto();
            $photo->setPath($row['photo']);
            $photo->setUserId($row['id']);

            $candidate = new ProposalCandidateRecommendation();
            $candidate->setId($row['id']);
            $candidate->setUsername($row['username']);
            $candidate->setSlug($row['slug']);
            $candidate->setPhoto($photo);
            $candidate->setMatching($row['matching_questions']);
            $candidate->setSimilarity($row['similarity']);
            $candidate->setInterested($row['interested']);
            $candidate->setAge($age);

            $location = $this->locationManager->buildFromNode($row['location']);
            $candidate->setLocation($location);

            $response[] = $candidate;
        }

        return $response;
    }

    /**
     * @param array | \ArrayAccess $row
     * @return int|null
     */
    protected function getAgeFromBirthday($row)
    {
        $age = null;
        if ($row['birthday']) {
            $date = new \DateTime($row['birthday']);
            $now = new \DateTime();
            $interval = $now->diff($date);
            $age = $interval->y;
        }

        return $age;
    }
}