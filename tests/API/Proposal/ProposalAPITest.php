<?php

namespace Tests\API\Proposal;

use Tests\API\APITest;

abstract class ProposalAPITest extends APITest
{
    protected function getOwnProposals($loggedInUserId = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/proposals', 'GET', array(), $loggedInUserId);
    }

    protected function getOtherUser($slug, $loggedInUser = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/users/' . $slug, 'GET', array(), $loggedInUser);
    }

    protected function createProposal($data, $loggedInUserId = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/proposals', 'POST', $data, $loggedInUserId);
    }

    protected function editProposal($proposalId, $data, $loggedInUserId = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/proposals/'.$proposalId, 'PUT', $data, $loggedInUserId);
    }

    protected function deleteProposal($proposalId, $loggedInUserId = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/proposals/'.$proposalId, 'DELETE', array(), $loggedInUserId);
    }

    protected function getRecommendations($loggedInUserId = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/proposals/recommendations', 'GET', array(), $loggedInUserId);
    }

    protected function getProposalMetadata($loggedInUserId = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/proposals/metadata', 'GET', array(), $loggedInUserId);
    }

}