<?php

namespace Service;

use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Provider\OAuthProvider;
use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken;
use Manager\UserManager;
use Model\User;
use Silex\Component\Security\Core\Encoder\JWTEncoder;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use HWI\Bundle\OAuthBundle\DependencyInjection\Configuration;

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
     * @return string
     * @throws UnauthorizedHttpException
     */
    public function loginByResourceOwner($resourceOwner, $accessToken)
    {
		$type = Configuration::getResourceOwnerType($resourceOwner);
	    if ($type == 'oauth1') {
		    $accessToken = array(
			    'oauth_token' => substr($accessToken, 0, strpos(':', $accessToken)),
			    'oauth_token_secret' => substr($accessToken, strpos(':', $accessToken) + 1, strpos('@', $accessToken)),
		    );
	    }
        $token = new OAuthToken($accessToken);
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