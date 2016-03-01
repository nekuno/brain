<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Tests\API;

class UsersTest extends APITest
{
    public function testUsers()
    {
        $this->assertGetUserWithotCredentialsResponse();
        $this->assertCreateUserFormat();
        $this->assertLoginUserFormat();
        $this->assertGetUserFormat();
    }

    protected function assertGetUserWithotCredentialsResponse()
    {
        $response = $this->getUserA();
        $this->assertStatusCode($response, 401, "Get User without credentials");
    }

    protected function assertCreateUserFormat()
    {
        $response = $this->createUserA();
        $formattedResponse = $this->assertJsonResponse($response, 201, "Create UserA");
        $this->assertUserFormat($formattedResponse, "Bad User response on create a user");
    }

    protected function assertLoginUserFormat()
    {
        $response = $this->loginUserA();
        $this->assertStatusCode($response, 200, "Login UserA");
    }

    protected function assertGetUserFormat()
    {
        $response = $this->getUserA();
        $formattedResponse = $this->assertJsonResponse($response, 200, "Get UserA");
        $this->assertUserFormat($formattedResponse, "Bad UserA response");
    }

    protected function assertUserFormat($user)
    {
        $this->assertArrayHasKey('qnoow_id', $user, "User has not qnoow_id key");
        $this->assertArrayHasKey('username', $user, "User has not username key");
        $this->assertArrayHasKey('email', $user, "User has not email key");
        $this->assertEquals(1, $user['qnoow_id'], "qnoow_id is not 1");
        $this->assertEquals('JohnDoe', $user['username'], "username is not JohnDoe");
        $this->assertEquals('nekuno-johndoe@gmail.com', $user['email'], "email is not nekuno-johndoe@gmail.com");
    }
}