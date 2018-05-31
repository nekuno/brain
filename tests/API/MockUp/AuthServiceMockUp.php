<?php

namespace Tests\API\MockUp;

use Service\AuthService;
use Tests\API\APITest;

class AuthServiceMockUp extends AuthService
{
    /**
     * @param $resourceOwner
     * @param $accessToken
     * @param $refreshToken
     * @return string
     * @throws \ErrorException
     */
    public function loginByResourceOwner($resourceOwner, $accessToken, $refreshToken = null)
    {
        $token = $this->tokensModel->getById(APITest::OWN_USER_ID, $resourceOwner);
        if ($token->getUserId() !== APITest::OWN_USER_ID) {
            throw new \ErrorException('Token does not exists');
        }

        $user = $this->um->getById($token->getUserId());

        $user = $this->updateLastLogin($user);

        $data = array('oauthToken' => $accessToken);
        if ($refreshToken) {
            $data['refreshToken'] = $refreshToken;
        }

        try {
            $this->tokensModel->update($user->getId(), $resourceOwner, $data);
        } catch (\Exception $e) {

        }
        return $this->buildToken($user);
    }
}