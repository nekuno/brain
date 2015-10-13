<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Tests\API;

class UsersTest extends APITest
{
    /**
     * @dataProvider testUsers
     */
    public function testUsers()
    {
        $this->assertGetUsersEmpty();
        $this->assertCreateUserFormat();
        $this->assertGetUsersFormat();
        $this->assertGetUserFormat();
        $this->assertDeleteUserResponse();
        $this->assertGetDeletedUserResponse();
    }

    protected function assertGetUsersEmpty()
    {
        $response = $this->getResponseByRoute('/users');
        $formattedResponse = $this->assertJsonResponse($response, 404, "Get Users (empty)");
        $this->assertEmpty($formattedResponse, "Users should be empty");
    }

    protected function assertCreateUserFormat()
    {
        $response = $this->createUserA();
        $formattedResponse = $this->assertJsonResponse($response, 201, "Create UserA");
        $this->assertUserFormat($formattedResponse, "Bad User response on create a user");
    }

    protected function assertGetUsersFormat()
    {
        $response = $this->getResponseByRoute('/users');
        $formattedResponse = $this->assertJsonResponse($response, 200, "Get Users (UserA)");
        $this->assertUsersFormat($formattedResponse, "Bad Users response");
    }

    protected function assertGetUserFormat()
    {
        $response = $this->getUserA();
        $formattedResponse = $this->assertJsonResponse($response, 200, "Get UserA");
        $this->assertUserFormat($formattedResponse, "Bad UserA response");
    }

    protected function assertDeleteUserResponse()
    {
        $response = $this->getResponseByRoute('/users/1', 'DELETE');
        $this->assertStatusCode($response, 200, "Delete UserA");
    }

    protected function assertGetDeletedUserResponse()
    {
        $response = $this->getUserA();
        $this->assertStatusCode($response, 404, "Get deleted UserA");
    }

    protected function assertUsersFormat($users)
    {
        $this->assertNotEmpty($users, "user 1 should exist");
        $this->assertArrayHasKey(0, $users, "users[0] does not exist");
        $this->assertUserFormat($users[0]);
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