<?php

namespace Service;

use Firebase\JWT\JWT;
use Model\UserModel;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class AuthService
{

    /**
     * @var UserModel
     */
    protected $um;

    /**
     * @var PasswordEncoderInterface
     */
    protected $encoder;

    /**
     * @var string
     */
    protected $secret;

    public function __construct(UserModel $um, PasswordEncoderInterface $encoder, $secret)
    {
        $this->um = $um;
        $this->encoder = $encoder;
        $this->secret = $secret;
    }

    public function login($username, $password)
    {

        try {
            $user = $this->um->findBy(array('usernameCanonical' => $this->um->canonicalize($username)));
        } catch (\Exception $e) {
            throw new UnauthorizedHttpException('', 'El nombre de usuario y la contraseña que ingresaste no coinciden con nuestros registros.');
        }

        $encodedPassword = $user['password'];
        $salt = $user['salt'];
        $valid = $this->encoder->isPasswordValid($encodedPassword, $password, $salt);

        if (!$valid) {
            throw new UnauthorizedHttpException('', 'El nombre de usuario y la contraseña que ingresaste no coinciden con nuestros registros.');
        }

        unset($user['password']);
        $token = array(
            'iss' => 'https://nekuno.com',
            'user' => $user,
        );

        $jwt = JWT::encode($token, $this->secret);

        return $jwt;
    }
}