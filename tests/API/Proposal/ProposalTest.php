<?php

namespace Tests\API\Proposal;

class ProposalTest extends ProposalAPITest
{
    public function testProposal()
    {
        $this->assertGetOwnProposalsEmpty();
        $this->assertCreateProposals();
        $this->assertEditProposal();
//        $this->assertDeleteProposal();
        $this->assertGetOwnProposals();
    }

    protected function assertGetOwnProposalsEmpty()
    {
        $response = $this->getOwnProposals();
        $formattedResponse = $this->assertJsonResponse($response, 200, "Get Proposals");
        $this->assertEquals([], $formattedResponse);
    }

    protected function assertCreateProposals()
    {
        $workProposalData = $this->getWorkProposalData();
        $workResponse = $this->createProposal($workProposalData);
        $formattedResponse = $this->assertJsonResponse($workResponse, 201, 'Create work proposal');
        $this->assertProposalFormat($formattedResponse);

        $sportProposalData = $this->getSportProposalData();
        $sportResponse = $this->createProposal($sportProposalData);
        $formattedResponse = $this->assertJsonResponse($sportResponse, 201, 'Create sport proposal');
        $this->assertProposalFormat($formattedResponse);

        $videogameProposalData = $this->getVideogameProposalData();
        $videogameResponse = $this->createProposal($videogameProposalData);
        $formattedResponse = $this->assertJsonResponse($videogameResponse, 201, 'Create videogame proposal');
        $this->assertProposalFormat($formattedResponse);

        $hobbyProposalData = $this->getHobbyProposalData();
        $hobbyResponse = $this->createProposal($hobbyProposalData);
        $formattedResponse = $this->assertJsonResponse($hobbyResponse, 201, 'Create hobby proposal');
        $this->assertProposalFormat($formattedResponse);

        $showProposalData = $this->getShowProposalData();
        $showResponse = $this->createProposal($showProposalData);
        $formattedResponse = $this->assertJsonResponse($showResponse, 201, 'Create show proposal');
        $this->assertProposalFormat($formattedResponse);

        $restaurantProposalData = $this->getRestaurantProposalData();
        $restaurantResponse = $this->createProposal($restaurantProposalData);
        $formattedResponse = $this->assertJsonResponse($restaurantResponse, 201, 'Create restaurant proposal');
        $this->assertProposalFormat($formattedResponse);

        $planProposalData = $this->getPlanProposalData();
        $planResponse = $this->createProposal($planProposalData);
        $formattedResponse = $this->assertJsonResponse($planResponse, 201, 'Create plan proposal');
        $this->assertProposalFormat($formattedResponse);
    }

    protected function assertEditProposal()
    {
        $response = $this->getOwnProposals();
        $formattedResponse = $this->assertJsonResponse($response, 200);
        $workProposals = array_filter($formattedResponse ,function($proposal) {return $proposal['name'] == 'work';});
        $workProposalId = $workProposals[0]['id'];

        $editData = $this->getWorkProposalData2();
        $editData['proposalId'] = $workProposalId;

        $response = $this->editProposal($editData);
        $formattedResponse = $this->assertJsonResponse($response, 201);
        $this->assertProposalFormat($formattedResponse);
    }

    protected function assertGetOwnProposals()
    {
        $response = $this->getOwnProposals();
        $formattedResponse = $this->assertJsonResponse($response, 200);
        foreach ($formattedResponse as $proposal)
        {
            $this->assertProposalFormat($proposal);
        }
    }

    protected function assertDeleteProposal()
    {
        $response = $this->getOwnProposals();
        $formattedResponse = $this->assertJsonResponse($response, 200);
        $workProposals = array_filter($formattedResponse ,function($proposal) {return $proposal['name'] == 'work';});
        $proposalId = $workProposals[0]['id'];
        $data = array('proposalId' => $proposalId);
        $response = $this->deleteProposal($data);
        $formattedResponse = $this->assertJsonResponse($response, 201);
        $this->assertEquals([], $formattedResponse);
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

    protected function assertProposalFormat($proposal)
    {
        $this->assertArrayHasKey('id', $proposal);
        $this->isType('int')->evaluate($proposal['id']);
        $this->assertArrayHasKey('name', $proposal);
        $this->isType('string')->evaluate($proposal['name']);

        $this->assertArrayHasKey('fields', $proposal);
        $this->assertArrayOfType('array', $proposal['fields'], 'fields is an array of arrays');
        foreach ($proposal['fields'] as $field)
        {
            $this->assertArrayHasKey('name', $field);
            $this->assertArrayHasKey('value', $field);
            $this->assertArrayHasKey('type', $field);
        }
    }


}