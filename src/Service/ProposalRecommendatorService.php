<?php

namespace Service;

use Model\Availability\AvailabilityManager;
use Model\Filters\FilterUsersManager;
use Model\Profile\ProfileManager;
use Model\Proposal\Proposal;
use Model\Proposal\ProposalManager;
use Model\Recommendation\Proposal\CandidateInterestedRecommendator;
use Model\Recommendation\Proposal\CandidateRecommendator;
use Model\Recommendation\Proposal\CandidateUninterestedFreeRecommendator;
use Model\Recommendation\Proposal\CandidateUninterestedRecommendator;
use Model\Recommendation\Proposal\ProposalFreeRecommendator;
use Model\Recommendation\Proposal\ProposalRecommendator;
use Model\Recommendation\UserRecommendation;
use Model\Recommendation\UserRecommendationBuilder;
use Model\User\User;
use Model\User\UserManager;
use Paginator\ProposalRecommendationsPaginator;
use Symfony\Component\HttpFoundation\Request;

class ProposalRecommendatorService
{
    protected $paginator;
    protected $filterUsersManager;
    protected $availabilityManager;
    protected $proposalManager;
    protected $candidateUninterestedRecommendator;
    protected $candidateUninterestedFreeRecommendator;
    protected $candidateInterestedRecommendator;
    protected $candidateRecommendator;
    protected $proposalRecommendator;
    protected $proposalFreeRecommendator;
    protected $profileManager;
    protected $userManager;
    protected $userRecommendationBuilder;

    /**
     * ProposalRecommendatorService constructor.
     * @param ProposalRecommendationsPaginator $paginator
     * @param CandidateUninterestedRecommendator $candidateUninterestedRecommendator
     * @param CandidateUninterestedFreeRecommendator $candidateUninterestedFreeRecommendator
     * @param CandidateInterestedRecommendator $candidateInterestedRecommendator
     * @param CandidateRecommendator $candidateRecommendator
     * @param ProposalRecommendator $proposalRecommendator
     * @param ProposalFreeRecommendator $proposalRecommendationFreeRecommendator
     * @param FilterUsersManager $filterUsersManager
     * @param AvailabilityManager $availabilityManager
     * @param ProposalManager $proposalManager
     * @param ProfileManager $profileManager
     * @param UserManager $userManager
     * @param UserRecommendationBuilder $userRecommendationBuilder
     */
    public function __construct(
        ProposalRecommendationsPaginator $paginator,
        CandidateUninterestedRecommendator $candidateUninterestedRecommendator,
        CandidateUninterestedFreeRecommendator $candidateUninterestedFreeRecommendator,
        CandidateInterestedRecommendator $candidateInterestedRecommendator,
        CandidateRecommendator $candidateRecommendator,
        ProposalRecommendator $proposalRecommendator,
        ProposalFreeRecommendator $proposalRecommendationFreeRecommendator,
        FilterUsersManager $filterUsersManager,
        AvailabilityManager $availabilityManager,
        ProposalManager $proposalManager,
        ProfileManager $profileManager,
        UserManager $userManager,
        UserRecommendationBuilder $userRecommendationBuilder
    ) {
        $this->paginator = $paginator;
        $this->candidateUninterestedRecommendator = $candidateUninterestedRecommendator;
        $this->candidateUninterestedFreeRecommendator = $candidateUninterestedFreeRecommendator;
        $this->candidateInterestedRecommendator = $candidateInterestedRecommendator;
        $this->candidateRecommendator = $candidateRecommendator;
        $this->proposalRecommendator = $proposalRecommendator;
        $this->proposalFreeRecommendator = $proposalRecommendationFreeRecommendator;
        $this->filterUsersManager = $filterUsersManager;
        $this->availabilityManager = $availabilityManager;
        $this->proposalManager = $proposalManager;
        $this->profileManager = $profileManager;
        $this->userManager = $userManager;
        $this->userRecommendationBuilder = $userRecommendationBuilder;
    }

    public function getRecommendations(User $user, Request $request)
    {
        $candidateRecommendations = $this->getCandidateRecommendations($user, $request);
        $proposalRecommendations = $this->getProposalRecommendations($user, $request);

        $recommendations = $this->mixRecommendations($candidateRecommendations, $proposalRecommendations);

        return $recommendations;
    }

    protected function getCandidateRecommendations(User $user, Request $request)
    {
        $proposalIds = $this->proposalManager->getIdsByUser($user);

        $candidateRecommendations = array();
        foreach ($proposalIds as $proposalId) {
            $filters = $this->getFiltersByProposalId($proposalId);
            $filters['userId'] = $user->getId();
            $filters = $this->setIncludeCandidatesSkipped($filters, $request);
            $filters['excluded'] = $request->get('excluded', array());

            $candidatesResult = $this->paginator->paginate($filters, $this->candidateRecommendator, $request);

            $thisCandidateRecommendations = $this->userRecommendationBuilder->buildCandidates($candidatesResult['items']);

            foreach ($thisCandidateRecommendations as $index => $candidateRecommendation)
            {
                $userId = $candidateRecommendation->getId();
                $locale = $this->profileManager->getInterfaceLocale($userId);

                $candidateDatum = $candidatesResult['items'][$index];
                $proposalId = $candidateDatum['proposalId'];

                $proposal = $this->proposalManager->getById($proposalId, $locale);

                $candidateRecommendation->setProposal($proposal);
            }

            $candidateRecommendations = array_merge($candidateRecommendations, $thisCandidateRecommendations);
        }

        return $candidateRecommendations;
    }

    /**
     * @param $proposalId
     * @return array
     */
    protected function getFiltersByProposalId($proposalId)
    {
        $filters = $this->filterUsersManager->getFilterUsersByProposalId($proposalId);
        $filters = $filters->jsonSerialize();
        $filters['proposalId'] = $proposalId;

        return $filters;
    }

    protected function setIncludeCandidatesSkipped(array $filters, Request $request)
    {
        $candidatesNotSeenYet = $this->candidateRecommendator->countTotal($filters);
        $limit = $request->get('limit') ?: 20;
        $includeSkipped = $candidatesNotSeenYet < $limit;
        $filters['includeSkipped'] = $includeSkipped;

        return $filters;
    }

    protected function getInterestedCandidates($filters, $request)
    {
        return $this->paginator->paginate($filters, $this->candidateInterestedRecommendator, $request);
    }

    protected function getUninterestedCandidates($filters, $request)
    {
        $availability = $this->getAvailabilityByProposal($filters['proposalId']);

        if (null == $availability) {
            $model = $this->candidateUninterestedFreeRecommendator;
        } else {
            $model = $this->candidateInterestedRecommendator;
        }

        return $this->paginator->paginate($filters, $model, $request);
    }

    protected function getAvailabilityByProposal($proposalId)
    {
        $defaultLocale = 'en';
        $proposal = $this->proposalManager->getById($proposalId, $defaultLocale);
        $availability = $this->availabilityManager->getByProposal($proposal);

        return $availability;
    }

    protected function getProposalRecommendations(User $user, Request $request)
    {
        $filters = $request->query->get('filters');
        $filters['userId'] = $user->getId();
        $filters = $this->setIncludeProposalsSkipped($filters, $request);
        $availability = $this->availabilityManager->getByUser($user);

        if (null == $availability) {
            $model = $this->proposalFreeRecommendator;
        } else {
            $model = $this->proposalRecommendator;
        }

        $proposalPagination = $this->paginator->paginate($filters, $model, $request);
        $proposalRecommendations = $this->buildProposalRecommendations($proposalPagination['items'], $user);

        return $proposalRecommendations;
    }

    protected function setIncludeProposalsSkipped(array $filters, Request $request)
    {
        $proposalsNotSeenYet = $this->proposalFreeRecommendator->countTotal($filters);
        $limit = $request->get('limit') ?: 20;
        $includeSkipped = $proposalsNotSeenYet < $limit;
        $filters['includeSkipped'] = $includeSkipped;

        return $filters;
    }

    protected function buildProposalRecommendations(array $proposalData, User $user)
    {
        $locale = $this->profileManager->getInterfaceLocale($user->getId());

        $proposalRecommendations = array();
        foreach ($proposalData as $proposalDatum)
        {
            $proposalId = $proposalDatum['proposalId'];
            $proposal = $this->proposalManager->getById($proposalId, $locale);

            $userId = $proposalDatum['ownerId'];
            $ownUserId = $user->getId();
            $userRecommendationData = $this->userManager->getAsRecommendation($userId, $ownUserId);
            $owners = $this->userRecommendationBuilder->buildUserRecommendations($userRecommendationData);
            $owner = reset($owners);

            $proposalRecommendations[] = ['proposal' => $proposal, 'owner' => $owner];
        }

        $proposalRecommendations = array_slice($proposalRecommendations, 0, 10);

        return $proposalRecommendations;
    }

    /**
     * @param UserRecommendation[] $candidateRecommendations
     * @param Proposal[] $proposalRecommendations
     * @return array
     */
    protected function mixRecommendations(array $candidateRecommendations, array $proposalRecommendations)
    {
        $recommendations = array();
        for ($i = 0; $i < 10; $i++) {
            if (isset($candidateRecommendations[$i])) {
                $recommendations[] = $candidateRecommendations[$i];
            }
            if (isset($proposalRecommendations[$i])) {
                $recommendations[] = $proposalRecommendations[$i];
            }
        }

        return $recommendations;
    }

    protected function mixCandidates($interested, $unInterested)
    {
        $interestedDesired = 3;
        $unInterestedDesired = 7;

        $interested = array_slice($interested, 0, $interestedDesired);
        $unInterested = array_slice($unInterested, 0, $unInterestedDesired);

        $candidates = array_merge($interested, $unInterested);

        shuffle($candidates);

        return $candidates;
    }
}