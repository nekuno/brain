<?php

namespace Tests\API\Proposal;

use Tests\API\APITest;

abstract class ProposalAPITest extends APITest
{
    protected function getOwnProposals($loggedInUserId = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/proposals', 'GET', array(), $loggedInUserId);
    }

    protected function createProposal($data, $loggedInUserId = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/proposals', 'POST', $data, $loggedInUserId);
    }

    protected function editProposal($data, $loggedInUserId = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/proposals', 'PUT', $data, $loggedInUserId);
    }

    protected function deleteProposal($data, $loggedInUserId = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/proposals', 'DELETE', $data, $loggedInUserId);
    }

//    protected function getProfileMetadata($loggedInUserId = self::OWN_USER_ID)
//    {
//        return $this->getResponseByRouteWithCredentials('/profile/metadata', 'GET', array(), $loggedInUserId);
//    }
//
//    protected function getProfileFilters($loggedInUserId = self::OWN_USER_ID)
//    {
//        return $this->getResponseByRouteWithCredentials('/profile/filters', 'GET', array(), $loggedInUserId);
//    }
//
//    protected function getProfileTags($type, $loggedInUserId = self::OWN_USER_ID)
//    {
//        return $this->getResponseByRouteWithCredentials('/profile/tags/' . $type, 'GET', array(), $loggedInUserId);
//    }
}