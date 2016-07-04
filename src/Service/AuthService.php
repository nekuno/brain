<?php

namespace Service;

use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Provider\OAuthProvider;
use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken;
use Manager\UserManager;
use Model\User;
use Silex\Component\Security\Core\Encoder\JWTEncoder;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class AuthService
{

    /**
     * @var UserManager
     */
    protected $um;

    /**
     * @var PasswordEncoderInterface
     */
    protected $encoder;

    /**
     * @var JWTEncoder
     */
    protected $jwtEncoder;

    /**
     * @var OAuthProvider
     */
    protected $oAuthProvider;

    public function __construct(UserManager $um, PasswordEncoderInterface $encoder, JWTEncoder $jwtEncoder, OAuthProvider $oAuthProvider)
    {
        $this->um = $um;
        $this->encoder = $encoder;
        $this->jwtEncoder = $jwtEncoder;
        $this->oAuthProvider = $oAuthProvider;
    }

    /**
     * @param $username
     * @param $password
     * @return string
     * @throws UnauthorizedHttpException
     */
    public function login($username, $password)
    {

        try {
            $user = $this->um->findUserBy(array('usernameCanonical' => $this->um->canonicalize($username)));
        } catch (\Exception $e) {
            throw new UnauthorizedHttpException('', 'Los datos introducidos no coinciden con nuestros registros.');
        }

        $encodedPassword = $user->getPassword();
        $salt = $user->getSalt();
        $valid = $this->encoder->isPasswordValid($encodedPassword, $password, $salt);

        if (!$valid) {
            throw new UnauthorizedHttpException('', 'Los datos introducidos no coinciden con nuestros registros.');
        }

        return $this->buildToken($user);
    }

    /**
     * @param $resourceOwner
     * @param $accessToken
     * @param $oauthTokenSecret|null
     * @return string
     * @throws UnauthorizedHttpException
     */
    public function loginByResourceOwner($resourceOwner, $accessToken, $oauthTokenSecret = null)
    {
        $token = new OAuthToken(array(
	       'oauth_token' => $accessToken,
	       'oauth_token_secret' => $oauthTokenSecret,
        ));
        $token->setResourceOwnerName($resourceOwner);
        try {
            $newToken = $this->oAuthProvider->authenticate($token);
        } catch (\Exception $e) {
            throw new UnauthorizedHttpException('', 'Los datos introducidos no coinciden con nuestros registros.');
        }

        if (!$newToken) {
            throw new UnauthorizedHttpException('', 'Los datos introducidos no coinciden con nuestros registros.');
        }

        return $this->buildToken($newToken->getUser());
    }

    /**
     * @param string $id
     * @return string
     */
    public function getToken($id)
    {
        $user = $this->um->getById($id);

        return $this->buildToken($user);
    }

    /**
     * @param User $user
     * @return string
     */
    protected function buildToken(User $user)
    {
        $token = array(
            'iss' => 'https://nekuno.com',
            'sub' => $user->getUsernameCanonical(),
            'user' => $user->jsonSerialize(),
        );

        $jwt = $this->jwtEncoder->encode($token);

        return $jwt;
    }
}