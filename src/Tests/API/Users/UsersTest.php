<?php

namespace Tests\API\Users;

class UsersTest extends UsersAPITest
{
    public function testUsers()
    {
        $this->assertGetUserWithoutCredentialsResponse();
        $this->assertGetUnusedUsernameAvailableResponse();
        $this->assertValidateUserResponse();
        $this->assertCreateUsersResponse();
        $this->assertGetExistingUsernameAvailableResponse();
        $this->assertLoginUserResponse();
        $this->assertGetOwnUserResponse();
        $this->assertGetOtherUserResponse();
        $this->assertEditOwnUserResponse();
        $this->assertValidationErrorsResponse();
    }

    protected function assertGetUserWithoutCredentialsResponse()
    {
        $response = $this->getOtherUser(2);
        $this->assertStatusCode($response, 401, "Get User without credentials");
    }

    protected function assertGetUnusedUsernameAvailableResponse()
    {
        $response = $this->getUserAvailable('JohnDoe');
        $this->assertStatusCode($response, 200, "Bad response on get unused available username JohnDoe");
    }

    protected function assertValidateUserResponse()
    {
        $userData = $this->getUserAFixtures();
        $response = $this->validateUserA($userData);
        $this->assertStatusCode($response, 200, "Bad response on validate user A");
    }

    protected function assertCreateUsersResponse()
    {
        $userData = $this->getUserAFixtures();
        $response = $this->createUser($userData);
        $formattedResponse = $this->assertJsonResponse($response, 201, "Create UserA");
        $this->assertUserAFormat($formattedResponse, "Bad User response on create user A");

        $userData = $this->getUserBFixtures();
        $response = $this->createUser($userData);
        $formattedResponse = $this->assertJsonResponse($response, 201, "Create UserB");
        $this->assertUserBFormat($formattedResponse, "Bad User response on create user B");
    }

    protected function assertGetExistingUsernameAvailableResponse()
    {
        $response = $this->getUserAvailable('JohnDoe');
        $this->assertStatusCode($response, 404, "Bad response on get existing available username JohnDoe");
    }

    protected function assertLoginUserResponse()
    {
        $userData = $this->getUserAFixtures();
        $response = $this->loginUser($userData);
        $this->assertStatusCode($response, 200, "Login UserA");
    }

    protected function assertGetOwnUserResponse()
    {
        $response = $this->getOwnUser();
        $formattedResponse = $this->assertJsonResponse($response, 200, "Get own user");
        $this->assertUserAFormat($formattedResponse, "Bad own user response");
    }

    protected function assertGetOtherUserResponse()
    {
        $response = $this->getOtherUser(2);
        $formattedResponse = $this->assertJsonResponse($response, 200, "Get User B");
        $this->assertUserBFormat($formattedResponse, "Bad user B response");
    }

    protected function assertEditOwnUserResponse()
    {
        $userData = $this->getEditedUserAFixtures();
        $response = $this->editOwnUser($userData);
        $formattedResponse = $this->assertJsonResponse($response, 200, "Edit UserA");
        $this->assertEditedUserAFormat($formattedResponse, "Bad User response on edit user A");

        $userData = $this->getUserAFixtures();
        $response = $this->editOwnUser($userData);
        $formattedResponse = $this->assertJsonResponse($response, 200, "Edit UserA");
        $this->assertUserAFormat($formattedResponse, "Bad User response on edit user A");
    }

    protected function assertValidationErrorsResponse()
    {
        $userData = $this->getUserWithPasswordFixtures();
        $response = $this->createUser($userData);
        $formattedResponse = $this->assertJsonResponse($response, 422, "Edit user with password error");
        $this->assertPasswordValidationErrorFormat($formattedResponse);

        $userData = $this->getUserWithStatusFixtures();
        $response = $this->createUser($userData);
        $formattedResponse = $this->assertJsonResponse($response, 422, "Edit user with status error");
        $this->assertStatusValidationErrorFormat($formattedResponse);

        $userData = $this->getUserWithSaltFixtures();
        $response = $this->createUser($userData);
        $formattedResponse = $this->assertJsonResponse($response, 422, "Edit user with salt error");
        $this->assertSaltValidationErrorFormat($formattedResponse);

        $userData = $this->getUserWithNumericUsername();
        $response = $this->createUser($userData);
        $formattedResponse = $this->assertJsonResponse($response, 422, "Edit user with username error");
        $this->assertUsernameValidationErrorFormat($formattedResponse);
    }

    protected function assertUserAFormat($user)
    {
        $this->assertArrayHasKey('qnoow_id', $user, "User has not qnoow_id key");
        $this->assertArrayHasKey('username', $user, "User has not username key");
        $this->assertArrayHasKey('email', $user, "User has not email key");
        $this->assertArrayNotHasKey('plainPassword', $user, "User has plainPassword key");
        $this->assertEquals(1, $user['qnoow_id'], "qnoow_id is not 1");
        $this->assertEquals('JohnDoe', $user['username'], "username is not JohnDoe");
        $this->assertEquals('nekuno-johndoe@gmail.com', $user['email'], "email is not nekuno-johndoe@gmail.com");
    }

    protected function assertUserBFormat($user)
    {
        $this->assertArrayHasKey('qnoow_id', $user, "User has not qnoow_id key");
        $this->assertArrayHasKey('username', $user, "User has not username key");
        $this->assertArrayHasKey('email', $user, "User has not email key");
        $this->assertArrayNotHasKey('plainPassword', $user, "User has plainPassword key");
        $this->assertEquals(2, $user['qnoow_id'], "qnoow_id is not 2");
        $this->assertEquals('JaneDoe', $user['username'], "username is not JaneDoe");
        $this->assertEquals('nekuno-janedoe@gmail.com', $user['email'], "email is not nekuno-janedoe@gmail.com");
    }

    protected function assertEditedUserAFormat($user)
    {
        $this->assertArrayHasKey('qnoow_id', $user, "User has not qnoow_id key");
        $this->assertArrayHasKey('username', $user, "User has not username key");
        $this->assertArrayHasKey('email', $user, "User has not email key");
        $this->assertArrayNotHasKey('plainPassword', $user, "User has plainPassword key");
        $this->assertEquals(1, $user['qnoow_id'], "qnoow_id is not 1");
        $this->assertEquals('JohnDoe', $user['username'], "username is not JohnDoe");
        $this->assertEquals('nekuno-johndoe_updated@gmail.com', $user['email'], "email is not nekuno-johndoe_updated@gmail.com");
    }

    protected function assertPasswordValidationErrorFormat($exception)
    {
        $this->assertValidationErrorFormat($exception);
        $this->assertArrayHasKey('password', $exception['validationErrors'], "User validation error does not have invalid key \"password\"'");
        $this->assertEquals('Invalid key "password"', $exception['validationErrors']['password'][0], "password key is not Invalid key \"password\"");
    }

    protected function assertStatusValidationErrorFormat($exception)
    {
        $this->assertValidationErrorFormat($exception);
        $this->assertArrayHasKey('status', $exception['validationErrors'], "User validation error does not have invalid key \"status\"'");
        $this->assertEquals('Invalid key "status"', $exception['validationErrors']['status'][0], "status key is not Invalid key \"status\"");
    }

    protected function assertSaltValidationErrorFormat($exception)
    {
        $this->assertValidationErrorFormat($exception);
        $this->assertArrayHasKey('salt', $exception['validationErrors'], "User validation error does not have invalid key \"salt\"'");
        $this->assertEquals('Invalid key "salt"', $exception['validationErrors']['salt'][0], "salt key is not Invalid key \"salt\"");
    }

    protected function assertUsernameValidationErrorFormat($exception)
    {
        $this->assertValidationErrorFormat($exception);
        $this->assertArrayHasKey('username', $exception['validationErrors'], "User validation error does not have invalid key \"username\"'");
        $this->assertEquals('"username" must be an string', $exception['validationErrors']['username'][0], "username key is not \"username\" must be an string");
    }

    private function getEditedUserAFixtures()
    {
        return array(
            'username' => 'JohnDoe',
            'email' => 'nekuno-johndoe_updated@gmail.com',
            'plainPassword' => 'test_updated'
        );
    }

    private function getUserWithPasswordFixtures()
    {
        return array(
            'username' => 'JohnDoe',
            'email' => 'nekuno-johndoe_updated@gmail.com',
            'plainPassword' => 'test_updated',
            'password' => 'test'
        );
    }

    private function getUserWithStatusFixtures()
    {
        return array(
            'username' => 'JohnDoe',
            'email' => 'nekuno-johndoe_updated@gmail.com',
            'plainPassword' => 'test_updated',
            'status' => 'complete'
        );
    }

    private function getUserWithSaltFixtures()
    {
        return array(
            'username' => 'JohnDoe',
            'email' => 'nekuno-johndoe_updated@gmail.com',
            'plainPassword' => 'test_updated',
            'salt' => 'foo'
        );
    }

    private function getUserWithNumericUsername()
    {
        return array(
            'username' => 1,
            'email' => 'nekuno-johndoe_updated@gmail.com',
            'plainPassword' => 'test_updated',
        );
    }
}