<?php

namespace Service\Recommendator;

use Model\Recommendation\ProposalCandidatePaginatedManager;
use Model\Recommendation\ProposalRecommendationPaginatedManager;
use Model\User\User;
use Paginator\Paginator;
use Symfony\Component\HttpFoundation\Request;

class ProposalRecommendatorService
{
    protected $paginator;
    protected $proposalCandidatePaginatedManager;
    protected $proposalPaginatedManager;

    /**
     * ProposalRecommendatorService constructor.
     * @param $paginator
     * @param $proposalCandidatePaginatedManager
     * @param $proposalPaginatedManager
     */
    public function __construct(Paginator $paginator, ProposalCandidatePaginatedManager $proposalCandidatePaginatedManager, ProposalRecommendationPaginatedManager $proposalPaginatedManager)
    {
        $this->paginator = $paginator;
        $this->proposalCandidatePaginatedManager = $proposalCandidatePaginatedManager;
        $this->proposalPaginatedManager = $proposalPaginatedManager;
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
        $filters = $request->query->get('filters');
        $filters['userId'] = $user->getId();

        $candidateRecommendations = $this->paginator->paginate($filters, $this->proposalCandidatePaginatedManager, $request);

        return $candidateRecommendations;
    }

    protected function getProposalRecommendations(User $user, Request $request)
    {
        $filters = $request->query->get('filters');
        $filters['userId'] = $user->getId();

        $candidateRecommendations = $this->paginator->paginate($filters, $this->proposalPaginatedManager, $request);

        return $candidateRecommendations;
    }

    protected function mixRecommendations($candidateRecommendations, $proposalRecommendations)
    {
        $recommendations = array();
        for ($i = 0; $i< 5; $i++){
            $recommendations[] = $candidateRecommendations[$i];
            $recommendations[] = $proposalRecommendations[$i];
        }

        return $recommendations;
    }
}