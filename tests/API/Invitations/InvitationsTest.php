<?php

namespace Tests\API\Profile;

class InvitationsTest extends InvitationsAPITest
{
    public function testInvitations()
    {
        $this->assertCreateInvitationWithUser();
        $this->assertGetInvitations();
    }

    protected function assertCreateWithoutUser()
    {
        $invitationData = $this->getInvitationData();
        $response = $this->createInvitation($invitationData, 999);
        $this->assertStatusCode($response, 401, 'Status code on creating invitation data without user');
    }

    protected function assertCreateInvitationWithUser()
    {
        $invitationData = $this->getInvitationData();
        $response = $this->createInvitation($invitationData);
        $this->assertStatusCode($response, 201, 'Status code on correct create invitation data with user');

        $response = $this->createInvitation($invitationData, 999);
        $this->assertStatusCode($response, 401, 'Trying to create invitation with fake userId');
    }

    protected function assertGetInvitations()
    {
        $response = $this->getInvitations();
        $content = $this->assertJsonResponse($response, 200, 'Getting invitations from a user');

        $this->assertArrayOfType('array', $content, 'Invitation response is array of arrays');
        $firstInvitation = $content[0];
        $this->assertArrayOfType('array', $content, 'Each invitation is array of arrays');
        $this->assertArrayHasKey('invitation', $firstInvitation, 'Invitation data inside invitation key');
        $firstInvitationData = $firstInvitation['invitation'];
        $this->isType('array')->evaluate($firstInvitationData, 'Invitation data is an array');
        $this->assertArrayHasKey('token', $firstInvitationData, 'Invitation data has token');
        $this->assertArrayHasKey('available', $firstInvitationData, 'Invitation data has available');
        $this->assertArrayHasKey('consumed', $firstInvitationData, 'Invitation data has consumed');
        $this->assertArrayHasKey('createdAt', $firstInvitationData, 'Invitation data has createdAt');
        $this->assertArrayHasKey('email', $firstInvitationData, 'Invitation data has email');
        $this->assertArrayHasKey('expiresAt', $firstInvitationData, 'Invitation data has expiresAt');
        $this->assertArrayHasKey('htmlText', $firstInvitationData, 'Invitation data has htmlText');
        $this->assertArrayHasKey('slogan', $firstInvitationData, 'Invitation data has slogan');
        $this->assertArrayHasKey('image_url', $firstInvitationData, 'Invitation data has image_url');
        $this->assertArrayHasKey('image_path', $firstInvitationData, 'Invitation data has image_path');
        $this->assertArrayHasKey('orientationRequired', $firstInvitationData, 'Invitation data has orientationRequired');
        $this->assertArrayHasKey('invitationId', $firstInvitationData, 'Invitation data has invitationId');
    }

    protected function getInvitationData()
    {
        return array(
            'orientationRequired' => false,
            'available' => 10000,
            'token' => 'test',
        );
    }

    protected function getInvitationDataWithUser()
    {
        return array(
            'orientationRequired' => false,
            'available' => 10000,
            'token' => 'test',
        );
    }
}