<?php

namespace Tests\API\Proposal;

class ProposalTest extends ProposalAPITest
{
    public function testProposal()
    {
        $this->assertGetOwnEmpty();
        $this->assertCreate();
        $this->assertCreateWithAvailability();
        $this->assertEdit();
        $this->assertDelete();
        $this->assertGetOwn();
        $this->assertGetOther();
        $this->assertGetRecommendations();
        $this->assertGetMetadata();
    }

    protected function assertGetOwnEmpty()
    {
        $response = $this->getOwnProposals();
        $formattedResponse = $this->assertJsonResponse($response, 200, "Get Proposals");
        $this->assertEquals([], $formattedResponse);
    }

    protected function assertCreate()
    {
        $workProposalData = $this->getWorkProposalData();
        $workResponse = $this->createProposal($workProposalData);
        $formattedResponse = $this->assertJsonResponse($workResponse, 201, $workResponse->getContent());
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

    protected function assertCreateWithAvailability()
    {
        $availabilityProposalData = $this->getFullProposalData();
        $availabilityResponse = $this->createProposal($availabilityProposalData);
        $formattedResponse = $this->assertJsonResponse($availabilityResponse, 201, 'Create availability proposal');
        $this->assertProposalFormat($formattedResponse);
    }

    protected function assertEdit()
    {
        $response = $this->getOwnProposals();
        $formattedResponse = $this->assertJsonResponse($response, 200);
        $workProposals = array_filter(
            $formattedResponse,
            function ($proposal) {
                return $proposal['name'] == 'work';
            }
        );
        $workProposalId = reset($workProposals)['id'];

        $editData = $this->getWorkProposalData2();

        $response = $this->editProposal($workProposalId, $editData);
        $formattedResponse = $this->assertJsonResponse($response, 201);
        $this->assertProposalFormat($formattedResponse);
    }

    protected function assertDelete()
    {
        $response = $this->getOwnProposals();
        $formattedResponse = $this->assertJsonResponse($response, 200);

        $workProposals = array_filter(
            $formattedResponse,
            function ($proposal) {
                return $proposal['name'] == 'work';
            }
        );
        $proposalId = reset($workProposals)['id'];
        $data = array('proposalId' => $proposalId);

        $response = $this->deleteProposal($data);
        $formattedResponse = $this->assertJsonResponse($response, 201);
        $this->assertEquals([], $formattedResponse);
    }

    protected function assertGetOwn()
    {
        $response = $this->getOwnProposals();
        $formattedResponse = $this->assertJsonResponse($response, 200);
        foreach ($formattedResponse as $proposal) {
            $this->assertProposalFormat($proposal);
        }
    }

    protected function assertGetOther()
    {
        $response = $this->getOtherUser('johndoe', 2);
        $formattedResponse = $this->assertJsonResponse($response, 200, $response->getContent());
        foreach ($formattedResponse['proposals'] as $proposal) {
            $this->assertProposalFormat($proposal);
        }
    }

    protected function assertGetRecommendations()
    {
        $workProposalData = $this->getWorkProposalData();
        $this->createProposal($workProposalData, 2);

        $response = $this->getRecommendations(2);
        $formattedResponse = $this->assertJsonResponse($response, 200, $response->getContent());

        $this->assertEquals(10, count($formattedResponse), 'recommendation count');
        var_dump($formattedResponse);
        $this->assertProposalFormat($formattedResponse[1]);
        $this->assertProposalFormat($formattedResponse[3]);
        $this->assertProposalFormat($formattedResponse[5]);
        $this->assertProposalFormat($formattedResponse[7]);
        $this->assertProposalFormat($formattedResponse[9]);

        $this->assertUserRecommendationFormat($formattedResponse[0]);
        $this->assertUserRecommendationFormat($formattedResponse[2]);
        $this->assertUserRecommendationFormat($formattedResponse[4]);
        $this->assertUserRecommendationFormat($formattedResponse[6]);
        $this->assertUserRecommendationFormat($formattedResponse[8]);

    }

    protected function assertGetMetadata()
    {
        $response = $this->getProposalMetadata();
        $formattedResponse = $this->assertJsonResponse($response, 200, "Get Profile metadata");
        $this->assertMetadataFormat($formattedResponse);
    }

    protected function getWorkProposalData()
    {
        return array(
            'name' => 'work',
            'description' => 'my work proposal',
            'industry' => array('CS'),
            'profession' => array('web dev')
        );
    }

    protected function getWorkProposalData2()
    {
        return array(
            'name' => 'work',
            'description' => 'my edited work proposal',
            'industry' => array('Coffee drinking'),
            'profession' => array('web dev')
        );
    }

    protected function getSportProposalData()
    {
        return array(
            'name' => 'sport',
            'description' => 'my sport proposal',
            'sport' => array('football')
        );
    }

    protected function getVideogameProposalData()
    {
        return array(
            'name' => 'videogame',
            'description' => 'my videogame proposal',
            'videogame' => array('GTA')
        );
    }

    protected function getHobbyProposalData()
    {
        return array(
            'name' => 'hobby',
            'description' => 'my hobby proposal',
            'hobby' => array('Painting')
        );
    }

    protected function getShowProposalData()
    {
        return array(
            'name' => 'show',
            'description' => 'my show proposal',
            'show' => array('Theater')
        );
    }

    protected function getRestaurantProposalData()
    {
        return array(
            'name' => 'restaurant',
            'description' => 'my restaurant proposal',
            'restaurant' => array('Italian')
        );
    }

    protected function getPlanProposalData()
    {
        return array(
            'name' => 'plan',
            'description' => 'my plan proposal',
            'plan' => array('planning')
        );
    }

    protected function getFullProposalData()
    {
        return array(
            'name' => 'plan',
            'description' => 'my plan proposal',
            'plan' => array('planning'),
            'availability' => array(
                'dynamic' => array(
                    array(
                        'weekday' => 'friday',
                        'range' => array('Night')
                    ),
                    array(
                        'weekday' => 'saturday',
                        'range' => array('Morning', 'Evening', 'Night')
                    ),
                    array(
                        'weekday' => 'sunday',
                        'range' => array('Morning')
                    ),
                ),
                'static' => array(
                    array('day' => '2018-01-10', 'range' => array('Morning')),
                    array('day' => '2018-01-12', 'range' => array('Morning', 'Night')),
                )
            ),
            'participantLimit' => 5,
            'filters' => array(
                'userFilters' => array(
                    'descriptiveGender' => array('man'),
                    'birthday' => array(
                        'max' => 40,
                        'min' => 30,
                    ),
                    'language' => array(
                        array(
                            'tag' => array(
                                'name' => 'English'
                            ),
                            'choices' => array(
                                'full_professional',
                                'professional_working'
                            )
                        )
                    ),
                    'order' => 'similarity DESC'
                )
            )
        );
    }

    protected function assertProposalFormat($proposal)
    {
        $this->assertArrayHasKey('id', $proposal);
        $this->assertArrayHasKey('name', $proposal);
        $this->isType('string')->evaluate($proposal['name']);

        $this->assertArrayHasKey('fields', $proposal);
        $this->assertArrayOfType('array', $proposal['fields'], 'fields is an array of arrays');
        foreach ($proposal['fields'] as $field) {
            $this->assertArrayHasKey('name', $field);
            $this->assertArrayHasKey('value', $field);
            $this->assertArrayHasKey('type', $field);
        }
    }

    protected function assertUserRecommendationFormat($recommendation)
    {
        $this->assertArrayHasKey('id', $recommendation);
        $this->assertArrayHasKey('username', $recommendation);
        $this->assertArrayHasKey('slug', $recommendation);
        $this->assertArrayHasKey('photo', $recommendation);
        $this->isType('array')->evaluate($recommendation['photo']);
        $this->assertArrayHasKey('matching', $recommendation);
        $this->assertArrayHasKey('similarity', $recommendation);
        $this->assertArrayHasKey('age', $recommendation);
        $this->assertArrayHasKey('location', $recommendation);
        $this->isType('array')->evaluate($recommendation['location']);


    }

    protected function assertMetadataFormat($metadata)
    {
        $this->assertArrayOfType('array', $metadata, 'metadata is array of proposals');
        foreach ($metadata as $metadatum) {
            $this->assertArrayOfType('array', $metadatum, 'each proposal is array of fields');
            foreach ($metadatum as $field) {
                $this->isType('array')->evaluate($field);
                $this->assertArrayHasKey('type', $field);
            }
        }
    }
}