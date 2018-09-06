<?php

namespace Service\Recommendator;

use Model\Availability\AvailabilityManager;
use Model\Filters\FilterUsersManager;
use Model\Proposal\ProposalManager;
use Model\Recommendation\CandidateInterestedPaginatedManager;
use Model\Recommendation\CandidateUninterestedFreePaginatedManager;
use Model\Recommendation\CandidateUninterestedPaginatedManager;
use Model\Recommendation\ProposalRecommendationFreePaginatedManager;
use Model\Recommendation\ProposalRecommendationPaginatedManager;
use Model\User\User;
use Paginator\ProposalRecommendationsPaginator;
use Symfony\Component\HttpFoundation\Request;

class ProposalRecommendatorService
{
    protected $paginator;
    protected $filterUsersManager;
    protected $availabilityManager;
    protected $proposalManager;
    protected $candidateUninterestedPaginatedManager;
    protected $candidateUninterestedFreePaginatedManager;
    protected $candidateInterestedPaginatedManager;
    protected $proposalPaginatedManager;
    protected $proposalRecommendationFreePaginatedManager;

    /**
     * ProposalRecommendatorService constructor.
     * @param ProposalRecommendationsPaginator $paginator
     * @param CandidateUninterestedPaginatedManager $candidateUninterestedPaginatedManager
     * @param CandidateUninterestedFreePaginatedManager $candidateUninterestedFreePaginatedManager
     * @param CandidateInterestedPaginatedManager $candidateInterestedPaginatedManager
     * @param ProposalRecommendationPaginatedManager $proposalPaginatedManager
     * @param ProposalRecommendationFreePaginatedManager $proposalRecommendationFreePaginatedManager
     * @param FilterUsersManager $filterUsersManager
     * @param AvailabilityManager $availabilityManager
     * @param ProposalManager $proposalManager
     */
    public function __construct(
        ProposalRecommendationsPaginator $paginator,
        CandidateUninterestedPaginatedManager $candidateUninterestedPaginatedManager,
        CandidateUninterestedFreePaginatedManager $candidateUninterestedFreePaginatedManager,
        CandidateInterestedPaginatedManager $candidateInterestedPaginatedManager,
        ProposalRecommendationPaginatedManager $proposalPaginatedManager,
        ProposalRecommendationFreePaginatedManager $proposalRecommendationFreePaginatedManager,
        FilterUsersManager $filterUsersManager,
        AvailabilityManager $availabilityManager,
        ProposalManager $proposalManager
    ) {
        $this->paginator = $paginator;
        $this->candidateUninterestedPaginatedManager = $candidateUninterestedPaginatedManager;
        $this->candidateUninterestedFreePaginatedManager = $candidateUninterestedFreePaginatedManager;
        $this->candidateInterestedPaginatedManager = $candidateInterestedPaginatedManager;
        $this->proposalPaginatedManager = $proposalPaginatedManager;
        $this->proposalRecommendationFreePaginatedManager = $proposalRecommendationFreePaginatedManager;
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
            $filters = $this->filterUsersManager->getFilterUsersByProposalId($proposalId);
            $filters = $filters->jsonSerialize();
            $filters['proposalId'] = $proposalId;

            $interestedCandidates = $this->getInterestedCandidates($filters, $request);
            $uninterestedCandidates = $this->getUninterestedCandidates($filters, $request);

            $candidates = $this->mixCandidates($interestedCandidates['items'], $uninterestedCandidates['items']);

            $candidateRecommendations[] = $candidates;
        }

        return $candidateRecommendations;
    }

    protected function getInterestedCandidates($filters, $request)
    {
        return $this->paginator->paginate($filters, $this->candidateInterestedPaginatedManager, $request);
    }

    protected function getUninterestedCandidates($filters, $request)
    {
        $defaultLocale = 'en';
        $proposalId = $filters['proposalId'];
        $proposal = $this->proposalManager->getById($proposalId, $defaultLocale);
        $availability = $this->availabilityManager->getByProposal($proposal);

        if (null == $availability) {
            $model = $this->candidateUninterestedFreePaginatedManager;
        } else {
            $model = $this->candidateInterestedPaginatedManager;
        }

        return $this->paginator->paginate($filters, $model, $request);
    }

    protected function getProposalRecommendations(User $user, Request $request)
    {
        $filters = $request->query->get('filters');
        $filters['userId'] = $user->getId();

        $proposalRecommendations = $this->paginator->paginate($filters, $this->proposalPaginatedManager, $request);
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