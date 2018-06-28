<?php

namespace Service;

use HWI\Bundle\OAuthBundle\OAuth\Exception\HttpTransportException;
use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Provider\OAuthProvider;
use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Model\User\UserManager;
use Model\User\User;
use Model\Token\TokenManager;
use ReflectionObject;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AuthService
{

    /**
     * @var UserManager
     */
    protected $um;

    /**
     * @var MessageDigestPasswordEncoder
     */
    protected $encoder;

    /**
     * @var JWTEncoderInterface
     */
    protected $jwtEncoder;

    /**
     * @var OAuthProvider
     */
    protected $oAuthProvider;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var TokenManager
     */
    protected $tokensModel;

    public function __construct(UserManager $um, MessageDigestPasswordEncoder $encoder, JWTEncoderInterface $jwtEncoder, OAuthProvider $oAuthProvider, EventDispatcherInterface $dispatcher, TokenManager $tokensModel)
    {
        $this->um = $um;
        $this->encoder = $encoder;
        $this->jwtEncoder = $jwtEncoder;
        $this->oAuthProvider = $oAuthProvider;
        $this->dispatcher = $dispatcher;
        $this->tokensModel = $tokensModel;
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

        $user = $this->updateLastLogin($user);

        return $this->buildToken($user);
    }

    /**
     * @param $resourceOwner
     * @param $accessToken
     * @param $refreshToken
     * @return string
     */
    public function loginByResourceOwner($resourceOwner, $accessToken, $refreshToken = null)
    {
        $accessToken = $this->tokensModel->getOauth1Token($resourceOwner, $accessToken) ?: $accessToken;

        $token = new OAuthToken($accessToken);
        $token->setResourceOwnerName($resourceOwner);

        try {
            $newToken = $this->getNewToken($token);
        } catch (\Exception $e) {
            throw new UnauthorizedHttpException('', 'Los datos introducidos no coinciden con nuestros registros.');
        }

        if (!$newToken) {
            throw new UnauthorizedHttpException('', 'Los datos introducidos no coinciden con nuestros registros.');
        }

        $user = $this->updateLastLogin($newToken->getUser());

        $data = array(
            'oauthToken' => $accessToken,
            'resourceOwner' => $resourceOwner
        );
        if ($refreshToken) {
            $data['refreshToken'] = $refreshToken;
        }

        try {
            $this->tokensModel->update($user->getId(), $data);
        } catch (\Exception $e) {

        }
        return $this->buildToken($user);
    }

    protected function getNewToken($token, $counter = 0) {
        $newToken = null;
        if ($counter >= 5) {
            return $newToken;
        }

        try {
            $newToken = $this->oAuthProvider->authenticate($token);
        }
        catch (HttpTransportException $e) {
            sleep(1);
            $counter++;
            $newToken = $this->getNewToken($token, $counter);
        }
        catch (\Exception $e) {
            throw new UnauthorizedHttpException('', 'Los datos introducidos no coinciden con nuestros registros.', $e);
        }

        return $newToken;
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
            'user' => json_encode($user->jsonSerialize(), true),
        );

        $jwt = $this->jwtEncoder->encode($token);

        return $jwt;
    }

    public function getUser($token)
    {
        /** @var array $data */
        $data = $this->jwtEncoder->decode($token);

        $user = new User();
        $this->cast($user, json_decode($data['user']));

        return $user;
    }

    protected function cast($destination, $sourceArray)
    {
        if (is_string($destination)) {
            $destination = new $destination();
        }
        $destinationReflection = new ReflectionObject($destination);
        foreach ($sourceArray as $name => $value) {
            if ($destinationReflection->hasProperty($name)) {
                $propDest = $destinationReflection->getProperty($name);
                $propDest->setAccessible(true);
                $propDest->setValue($destination, $value);
            } else {
                $destination->$name = $value;
            }
        }
        return $destination;
    }

    protected function updateLastLogin(User $user)
    {

        $data = array(
            'userId' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'lastLogin' => (new \DateTime())->format('Y-m-d H:i:s'),
        );

        return $this->um->update($data);
    }

}