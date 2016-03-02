<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Tests\API;

class UsersTest extends APITest
{
    public function testUsers()
    {
        $this->assertGetUserWithoutCredentialsResponse();
        $this->assertGetUnusedUsernameAvailable();
        $this->assertValidateUsersFormat();
        $this->assertCreateUsersFormat();
        $this->assertGetExistingUsernameAvailable();
        $this->assertLoginUserFormat();
        $this->assertGetOwnUserFormat();
        $this->assertGetOtherUserFormat();
        $this->assertEditOwnUserFormat();
    }

    protected function assertGetUserWithoutCredentialsResponse()
    {
        $response = $this->getUserB();
        $this->assertStatusCode($response, 401, "Get User without credentials");
    }

    protected function assertGetUnusedUsernameAvailable()
    {
        $response = $this->getUserAvailable();
        $this->assertStatusCode($response, 200, "Bad response on get unused available username JohnDoe");
    }

    protected function assertValidateUsersFormat()
    {
        $response = $this->validateUserA();
        $this->assertStatusCode($response, 200, "Bad response on validate user A");
    }

    protected function assertCreateUsersFormat()
    {
        $response = $this->createUserA();
        $formattedResponse = $this->assertJsonResponse($response, 201, "Create UserA");
        $this->assertUserAFormat($formattedResponse, "Bad User response on create user A");

        $response = $this->createUserB();
        $formattedResponse = $this->assertJsonResponse($response, 201, "Create UserB");
        $this->assertUserBFormat($formattedResponse, "Bad User response on create user B");
    }

    protected function assertGetExistingUsernameAvailable()
    {
        $response = $this->getUserAvailable();
        $this->assertStatusCode($response, 404, "Bad response on get existing available username JohnDoe");
    }

    protected function assertLoginUserFormat()
    {
        $response = $this->loginUserA();
        $this->assertStatusCode($response, 200, "Login UserA");
    }

    protected function assertGetOwnUserFormat()
    {
        $response = $this->getOwnUser();
        $formattedResponse = $this->assertJsonResponse($response, 200, "Get own user");
        $this->assertUserAFormat($formattedResponse, "Bad own user response");
    }

    protected function assertGetOtherUserFormat()
    {
        $response = $this->getUserB();
        $formattedResponse = $this->assertJsonResponse($response, 200, "Get User B");
        $this->assertUserBFormat($formattedResponse, "Bad user B response");
    }

    protected function assertEditOwnUserFormat()
    {
        $response = $this->editOwnUser();
        $formattedResponse = $this->assertJsonResponse($response, 200, "Edit UserA");
        $this->assertEditedUserAFormat($formattedResponse, "Bad User response on edit user A");

        $response = $this->resetOwnUser();
        $formattedResponse = $this->assertJsonResponse($response, 200, "Edit UserA");
        $this->assertUserAFormat($formattedResponse, "Bad User response on edit user A");
    }

    protected function assertUserAFormat($user)
    {
        $this->assertArrayHasKey('qnoow_id', $user, "User has not qnoow_id key");
        $this->assertArrayHasKey('username', $user, "User has not username key");
        $this->assertArrayHasKey('email', $user, "User has not email key");
        $this->assertEquals(1, $user['qnoow_id'], "qnoow_id is not 1");
        $this->assertEquals('JohnDoe', $user['username'], "username is not JohnDoe");
        $this->assertEquals('nekuno-johndoe@gmail.com', $user['email'], "email is not nekuno-johndoe@gmail.com");
    }

    protected function assertUserBFormat($user)
    {
        $this->assertArrayHasKey('qnoow_id', $user, "User has not qnoow_id key");
        $this->assertArrayHasKey('username', $user, "User has not username key");
        $this->assertArrayHasKey('email', $user, "User has not email key");
        $this->assertEquals(2, $user['qnoow_id'], "qnoow_id is not 2");
        $this->assertEquals('JaneDoe', $user['username'], "username is not JaneDoe");
        $this->assertEquals('nekuno-janedoe@gmail.com', $user['email'], "email is not nekuno-janedoe@gmail.com");
    }

    protected function assertEditedUserAFormat($user)
    {
        $this->assertArrayHasKey('qnoow_id', $user, "User has not qnoow_id key");
        $this->assertArrayHasKey('username', $user, "User has not username key");
        $this->assertArrayHasKey('email', $user, "User has not email key");
        $this->assertEquals(1, $user['qnoow_id'], "qnoow_id is not 1");
        $this->assertEquals('JohnDoe', $user['username'], "username is not JohnDoe");
        $this->assertEquals('nekuno-johndoe_updated@gmail.com', $user['email'], "email is not nekuno-johndoe_updated@gmail.com");
    }
}