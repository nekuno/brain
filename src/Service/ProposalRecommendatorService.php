<?php

namespace Service;

use Model\Availability\AvailabilityManager;
use Model\Filters\FilterUsersManager;
use Model\Proposal\ProposalManager;
use Model\Recommendation\Proposal\CandidateInterestedRecommendator;
use Model\Recommendation\Proposal\CandidateUninterestedFreeRecommendator;
use Model\Recommendation\Proposal\CandidateUninterestedRecommendator;
use Model\Recommendation\Proposal\ProposalFreeRecommendator;
use Model\Recommendation\Proposal\ProposalRecommendator;
use Model\User\User;
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
    protected $proposalRecommendator;
    protected $proposalFreeRecommendator;

    /**
     * ProposalRecommendatorService constructor.
     * @param ProposalRecommendationsPaginator $paginator
     * @param CandidateUninterestedRecommendator $candidateUninterestedRecommendator
     * @param CandidateUninterestedFreeRecommendator $candidateUninterestedFreeRecommendator
     * @param CandidateInterestedRecommendator $candidateInterestedRecommendator
     * @param ProposalRecommendator $proposalRecommendator
     * @param ProposalFreeRecommendator $proposalRecommendationFreeRecommendator
     * @param FilterUsersManager $filterUsersManager
     * @param AvailabilityManager $availabilityManager
     * @param ProposalManager $proposalManager
     */
    public function __construct(
        ProposalRecommendationsPaginator $paginator,
        CandidateUninterestedRecommendator $candidateUninterestedRecommendator,
        CandidateUninterestedFreeRecommendator $candidateUninterestedFreeRecommendator,
        CandidateInterestedRecommendator $candidateInterestedRecommendator,
        ProposalRecommendator $proposalRecommendator,
        ProposalFreeRecommendator $proposalRecommendationFreeRecommendator,
        FilterUsersManager $filterUsersManager,
        AvailabilityManager $availabilityManager,
        ProposalManager $proposalManager
    ) {
        $this->paginator = $paginator;
        $this->candidateUninterestedRecommendator = $candidateUninterestedRecommendator;
        $this->candidateUninterestedFreeRecommendator = $candidateUninterestedFreeRecommendator;
        $this->candidateInterestedRecommendator = $candidateInterestedRecommendator;
        $this->proposalRecommendator = $proposalRecommendator;
        $this->proposalFreeRecommendator = $proposalRecommendationFreeRecommendator;
        $this->filterUsersManager = $filterUsersManager;
        $this->availabilityManager = $availabilityManager;
        $this->proposalManager = $proposalManager;
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

            $interestedCandidates = $this->getInterestedCandidates($filters, $request);
            $uninterestedCandidates = $this->getUninterestedCandidates($filters, $request);

            $candidates = $this->mixCandidates($interestedCandidates['items'], $uninterestedCandidates['items']);

            $candidateRecommendations[] = $candidates;
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

        $proposalRecommendations = $this->paginator->paginate($filters, $this->proposalRecommendator, $request);
        $proposalRecommendations = array_slice($proposalRecommendations['items'], 10);

        return $proposalRecommendations;
    }

    protected function mixRecommendations($candidateRecommendations, $proposalRecommendations)
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
        $totalDesired = $interestedDesired + $unInterestedDesired;

        $length = min(count($interested), $interestedDesired);

        $candidates = array();
        for ($i = 0; $i < $length; $i++) {
            $partial = [$interested[$i], $unInterested[$i]];
            shuffle($partial);

            $candidates = array_merge($candidates, $partial);
        }

        if (count($unInterested) > $length) {
            $extra = array_slice($unInterested, $length);
            $candidates = array_merge($candidates, $extra);
        }

        $candidates = array_slice($candidates, $totalDesired);

        return $candidates;
    }
}