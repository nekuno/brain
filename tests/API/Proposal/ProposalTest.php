<?php

namespace Tests\API\Proposal;

class ProposalTest extends ProposalAPITest
{
    public function testProposal()
    {
        $this->assertGetOwnProposalsEmpty();
        $this->assertCreateProposals();
//        $this->assertEditProposal();
//        $this->assertDeleteProposal();
//        $this->assertGetOwnProposals();
    }

    protected function assertGetOwnProposalsEmpty()
    {
        $response = $this->getOwnProposals(self::OTHER_USER_SLUG);
        var_dump($response->getContent());
        $formattedResponse = $this->assertJsonResponse($response, 200, "Get Proposals");
        $this->assertEquals([], $formattedResponse);
    }

    protected function assertCreateProposals()
    {
        $workProposalData = $this->getWorkProposalData();
        $workResponse = $this->createProposal($workProposalData);
        $formattedResponse = $this->assertJsonResponse($workResponse, 201, 'Create work proposal');
    }

    protected function assertEditProposals()
    {
        $response = $this->getOwnProposals(self::OTHER_USER_SLUG);
    }

    protected function getWorkProposalData()
    {
        return array(
            'name' => 'work',
            'description' => 'my work proposal',
            'industry' => 'CS',
            'profession' => 'web dev'
        );
    }

    protected function getWorkProposalData2()
    {
        return array(
            'name' => 'work',
            'description' => 'my edited work proposal',
            'industry' => 'Coffee drinking',
            'profession' => 'web dev'
        );
    }

    protected function getSportProposalData()
    {
        return array(
            'name' => 'sport',
            'description' => 'my sport proposal',
            'sport' => 'football'
        );
    }

    protected function getVideogameProposalData()
    {
        return array(
            'name' => 'videogame',
            'description' => 'my videogame proposal',
            'videogame' => 'GTA'
        );
    }

    protected function getHobbyProposalData()
    {
        return array(
            'name' => 'hobby',
            'description' => 'my hobby proposal',
            'hobby' => 'Painting'
        );
    }

    protected function getShowProposalData()
    {
        return array(
            'name' => 'show',
            'description' => 'my show proposal',
            'show' => 'Theater'
        );
    }

    protected function getRestaurantProposalData()
    {
        return array(
            'name' => 'restaurant',
            'description' => 'my restaurant proposal',
            'restaurant' => 'Italian'
        );
    }

    protected function getPlanProposalData()
    {
        return array(
            'name' => 'plan',
            'description' => 'my plan proposal',
            'plan' => 'planning'
        );
    }


}