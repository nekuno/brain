<?php

namespace Tests\API\Users;

use Tests\API\TestingFixtures;

class UsersTest extends UsersAPITest
{
    const USER_C_OAUTH_TOKEN = 'TESTING_OAUTH_TOKEN_C';
    const USER_C_RESOURCE_ID = '34567';

    public function testUsers()
    {
        $this->assertGetUserWithoutCredentialsResponse();
        $this->assertGetUnusedUsernameAvailableResponse();
        $this->assertGetExistingUsernameAvailableResponse();
        $this->assertLoginUserResponse();
        $this->assertGetOwnUserResponse();
        $this->assertGetOwnStatusResponse();
        $this->assertGetOtherUserResponse();
        $this->assertEditOwnUserResponse();
        $this->assertValidationErrorsResponse();
        $this->assertCreateUsersResponse();
        $this->assertDeleteUserFromAdmin();
    }

    public function testErrors()
    {
        $this->assertRegistrationErrorResponse();
    }

    protected function assertGetUserWithoutCredentialsResponse()
    {
        $response = $this->getOtherUser('janedoe', null);
        $this->assertStatusCode($response, 401, "Get User without credentials");
    }

    protected function assertGetUnusedUsernameAvailableResponse()
    {
        $response = $this->getUserAvailable('NotExistingUsername');
        $this->assertStatusCode($response, 200, "Bad response on get unused available username NotExistingUsername");
    }

    protected function assertCreateUsersResponse()
    {
        $userData = $this->getUserARegisterFixtures();
        $response = $this->createUser($userData);
        $formattedResponse = $this->assertJsonResponse($response, 422, "Create already existing user");
        $this->assertUserValidationErrorFormat($formattedResponse);

        $userData = $this->getUserBRegisterFixtures();
        $response = $this->createUser($userData);
        $formattedResponse = $this->assertJsonResponse($response, 422, "Create already existing user");
        $this->assertUserValidationErrorFormat($formattedResponse);

        $userData = $this->getUserCRegisterFixtures();
        $response = $this->createUser($userData);
        $formattedResponse = $this->assertJsonResponse($response, 201, "Create UserC");
        $this->assertUserCFormat($formattedResponse);
    }

    protected function assertRegistrationErrorResponse()
    {
        $userData = $this->getBadTokenUserRegisterFixtures();
        $response = $this->createUser($userData);
        $formattedResponse = $this->assertJsonResponse($response, 422, "Create user with non existant invitation");
        $this->assertUserValidationErrorFormat($formattedResponse);

        $multipleData = $this->getIncompleteOAuthUserRegisterFixtures();
        foreach ($multipleData as $userData){
            $response = $this->createUser($userData);
            $formattedResponse = $this->assertJsonResponse($response, 422, "Create user without oauth field, existent fields:" . json_encode(array_keys($userData['oauth'])));
            $this->assertUserValidationErrorFormat($formattedResponse);
        }

        $userData = $this->getNotProfileUserRegisterFixtures();
        $response = $this->createUser($userData);
        $formattedResponse = $this->assertJsonResponse($response, 422, "Create user without profile");
        $this->assertUserValidationErrorFormat($formattedResponse);

        $multipleData = $this->getBadProfileUserRegisterFixtures();
        foreach ($multipleData as $userData){
            $response = $this->createUser($userData);
            $formattedResponse = $this->assertJsonResponse($response, 422, "Create user with wrong profile:" . json_encode(array_keys($userData['profile'])));
            $this->assertUserValidationErrorFormat($formattedResponse);
        }
    }

    protected function assertGetExistingUsernameAvailableResponse()
    {
        $response = $this->getUserAvailable('JohnDoe');
        $this->assertStatusCode($response, 422, "Bad response on get existing available username JohnDoe");
    }

    protected function assertLoginUserResponse()
    {
        $response = $this->loginUser($this->getUserAFixtures());
        $this->assertStatusCode($response, 200, "Login UserA");
    }

    protected function assertGetOwnUserResponse()
    {
        $response = $this->getOwnUser();
        $formattedResponse = $this->assertJsonResponse($response, 200, "Get own user");
        $this->assertUserAFormat($formattedResponse);
    }

    protected function assertGetOwnStatusResponse()
    {
        $response = $this->getOwnUserStatus();
        $formattedResponse = $this->assertJsonResponse($response, 200, "Get own user status");
        $this->assertArrayOfType('array', $formattedResponse, 'Own user status is array of array');
        $socialNetworksConnected = 1;
        $this->assertEquals($socialNetworksConnected, count($formattedResponse));
    }

    protected function assertGetOtherUserResponse()
    {
        $response = $this->getOtherUser('janedoe');
        $formattedResponse = $this->assertJsonResponse($response, 200, "Get User B");
        $this->assertUserBFormat($formattedResponse);
    }

    protected function assertEditOwnUserResponse()
    {
        $userData = $this->getEditedUserAFixtures();
        $response = $this->editOwnUser($userData);
        $formattedResponse = $this->assertJsonResponse($response, 200, "Edit UserA");
        $this->assertEditedUserAFormat($formattedResponse);

        $userData = $this->getUserAEditionFixtures();
        $response = $this->editOwnUser($userData);
        $formattedResponse = $this->assertJsonResponse($response, 200, "Edit UserA");
        $this->assertEditedOriginalUserAFormat($formattedResponse);
    }

    protected function assertValidationErrorsResponse()
    {
        $userData = $this->getUserWithStatusFixtures();
        $response = $this->createUser($userData);
        $formattedResponse = $this->assertJsonResponse($response, 422, "Edit user with status error");
        $this->assertUserValidationErrorFormat($formattedResponse);

        $userData = $this->getUserWithSaltFixtures();
        $response = $this->createUser($userData);
        $formattedResponse = $this->assertJsonResponse($response, 422, "Edit user with salt error");
        $this->assertUserValidationErrorFormat($formattedResponse);

        $userData = $this->getUserWithNumericUsername();
        $response = $this->createUser($userData);
        $formattedResponse = $this->assertJsonResponse($response, 422, "Edit user with username error");
        $this->assertUserValidationErrorFormat($formattedResponse);
    }

    protected function assertDeleteUserFromAdmin()
    {
        $existent = $this->getOwnUser();
        $formattedCreated = $this->assertJsonResponse($existent, 200, "Get user for deletion");
        $userId = $formattedCreated['qnoow_id'];

        $response = $this->deleteUserFromAdmin($userId);
        $formattedResponse = $this->assertJsonResponse($response, 201, "Delete UserA");
        $this->assertUserAFormat($formattedResponse);

        $deleted = $this->getOwnUser();
        $this->assertJsonResponse($deleted, 401, "UserA is correctly deleted");
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
        $this->assertEquals(2, $user['qnoow_id'], "qnoow_id is not 2");
        $this->assertEquals('JaneDoe', $user['username'], "username is not JaneDoe");
    }

    protected function assertUserCFormat($user)
    {
        $this->assertArrayHasKey('qnoow_id', $user, "User has not qnoow_id key");
        $this->assertArrayHasKey('username', $user, "User has not username key");
        $this->assertEquals(3, $user['qnoow_id'], "qnoow_id is not 3");
        $this->assertEquals('Tom', $user['username'], "username is not Tom");
    }

    protected function assertEditedUserAFormat($response)
    {
        $this->assertArrayHasKey('user', $response, "User response has not user key");
        $this->assertArrayHasKey('jwt', $response, "User response has not jwt key");
        $user = $response['user'];
        $this->assertArrayHasKey('qnoow_id', $user, "User has not qnoow_id key");
        $this->assertArrayHasKey('username', $user, "User has not username key");
        $this->assertArrayHasKey('email', $user, "User has not email key");
        $this->assertEquals(1, $user['qnoow_id'], "qnoow_id is not 1");
        $this->assertEquals('JohnDoe', $user['username'], "username is not JohnDoe");
        $this->assertEquals('nekuno-johndoe_updated@gmail.com', $user['email'], "email is not nekuno-johndoe_updated@gmail.com");
    }

    protected function assertEditedOriginalUserAFormat($response)
    {
        $this->assertArrayHasKey('user', $response, "User response has not user key");
        $this->assertArrayHasKey('jwt', $response, "User response has not jwt key");
        $user = $response['user'];
        $this->assertArrayHasKey('qnoow_id', $user, "User has not qnoow_id key");
        $this->assertArrayHasKey('username', $user, "User has not username key");
        $this->assertArrayHasKey('email', $user, "User has not email key");
        $this->assertEquals(1, $user['qnoow_id'], "qnoow_id is not 1");
        $this->assertEquals('JohnDoe', $user['username'], "username is not JohnDoe");
        $this->assertEquals('nekuno-johndoe@gmail.com', $user['email'], "email is not nekuno-johndoe@gmail.com");
    }

    protected function assertUserValidationErrorFormat($exception)
    {
        $this->assertValidationErrorFormat($exception);
        $this->assertArrayHasKey('registration', $exception['validationErrors'], "User validation error does not have invalid key \"registration\"'");
        $this->assertEquals(array('Error registering user'), $exception['validationErrors']['registration'], "registration error is not \"Error registering user\"");
    }

    protected function getUserAFixtures()
    {
        return array(
            'resourceOwner' => 'facebook',
            'oauthToken' => TestingFixtures::USER_A_OAUTH_TOKEN,
        );
    }

    protected function getUserARegisterFixtures()
    {
        return array(
            'user' => array(
                'username' => 'JohnDoe',
                'email' => 'nekuno-johndoe@gmail.com',
            ),
            'profile' => array(),
            'token' => 'join',
            'oauth' => array(
                'resourceOwner' => 'facebook',
                'oauthToken' => TestingFixtures::USER_A_OAUTH_TOKEN,
                'resourceId' => TestingFixtures::USER_A_RESOURCE_ID,
                'expireTime' => strtotime("+1 week"),
                'refreshToken' => null
            ),
            'trackingData' => '',
        );
    }

    protected function getUserBRegisterFixtures()
    {
        return array(
            'user' => array(
                'username' => 'JaneDoe',
                'email' => 'nekuno-janedoe@gmail.com',
            ),
            'profile' => array(),
            'token' => 'join',
            'oauth' => array(
                'resourceOwner' => 'facebook',
                'oauthToken' => TestingFixtures::USER_B_OAUTH_TOKEN,
                'resourceId' => TestingFixtures::USER_B_RESOURCE_ID,
                'expireTime' => strtotime("+1 week"),
                'refreshToken' => null
            ),
            'trackingData' => '',
        );
    }

    protected function getUserCRegisterFixtures()
    {
        return array(
            'user' => array(
                'username' => 'Tom',
                'email' => 'nekuno-tom@gmail.com',
            ),
            'profile' => array(),
            'token' => 'join',
            'oauth' => array(
                'resourceOwner' => 'facebook',
                'oauthToken' => self::USER_C_OAUTH_TOKEN,
                'resourceId' => self::USER_C_RESOURCE_ID,
                'expireTime' => strtotime("+1 week"),
                'refreshToken' => null
            ),
            'trackingData' => '',
        );
    }

    private function getEditedUserAFixtures()
    {
        return array(
            'username' => 'JohnDoe',
            'email' => 'nekuno-johndoe_updated@gmail.com',
        );
    }

    private function getUserWithStatusFixtures()
    {
        return array(
            'user' => array(
                'username' => 'JohnDoe',
                'email' => 'nekuno-johndoe_updated@gmail.com',
                'status' => 'complete'
            ),
            'profile' => array(),
            'token' => 'join',
            'oauth' => array(
                'resourceOwner' => 'facebook',
                'oauthToken' => '12345',
                'resourceId' => '12345',
                'expireTime' => strtotime("+1 week"),
                'refreshToken' => '123456'
            )
        );
    }

    private function getUserWithSaltFixtures()
    {
        return array(
            'user' => array(
                'username' => 'JohnDoe',
                'email' => 'nekuno-johndoe_updated@gmail.com',
                'salt' => 'foo'
            ),
            'profile' => array(),
            'token' => 'join',
            'oauth' => array(
                'resourceOwner' => 'facebook',
                'oauthToken' => '12345',
                'resourceId' => '12345',
                'expireTime' => strtotime("+1 week"),
                'refreshToken' => '123456'
            )
        );
    }

    private function getUserWithNumericUsername()
    {
        return array(
            'user' => array(
                'username' => 1,
                'email' => 'nekuno-johndoe_updated@gmail.com',
            ),
            'profile' => array(),
            'token' => 'join',
            'oauth' => array(
                'resourceOwner' => 'facebook',
                'oauthToken' => '12345',
                'resourceId' => '12345',
                'expireTime' => strtotime("+1 week"),
                'refreshToken' => '123456'
            )
        );
    }

    protected function getBadTokenUserRegisterFixtures()
    {
        $user = $this->getUserARegisterFixtures();
        $user['token'] = 'nonExistantToken';

        return $user;
    }

    protected function getNotProfileUserRegisterFixtures()
    {
        $user = $this->getUserARegisterFixtures();
        unset($user['profile']);

        return $user;
    }

    protected function getIncompleteOAuthUserRegisterFixtures()
    {
        $fixtures = array();
        foreach (array('resourceOwner', 'oauthToken', 'resourceId') as $field) {
            $user = $this->getUserARegisterFixtures();
            unset($user['oauth'][$field]);

            $fixtures[] = $user;
        }

        return $fixtures;
    }

    protected function getBadProfileUserRegisterFixtures()
    {
        $fixtures = array();
        $wrongData = array(
            array('birthday' => '20-01-2015'),
            array('birthday' => '01-01-2017'),
            array('birthday' => '01-01-2099'),
            array('height' => '100'),
            array('height' => 20),
            array('height' => 500),
            array('gender' => 'nonExistantGender'),
            array('descriptiveGender' => 'nonExistantGender'),
            array('religion' => array('choice' => 'agnosticism')),
            array('religion' => array('detail' => 'important')),
            array('religion' => array('choice' => 'agnosticism', 'detail' => 'wrongDetail')),
            array('religion' => array('choice' => 'wrongReligion', 'detail' => 'important'))
        );

        foreach ($wrongData as $wrongField) {
            $user = $this->getUserARegisterFixtures();
            $user['profile'] += $wrongField;
            $fixtures[] = $user;
        }

        return $fixtures;
    }

    protected function getUserAEditionFixtures()
    {
        return array(
            'username' => 'JohnDoe',
            'email' => 'nekuno-johndoe@gmail.com',
        );
    }
}