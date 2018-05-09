<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Service;

class TokenGenerator
{
    private $useOpenSsl;

    public function __construct()
    {

        // determine whether to use OpenSSL
        if (defined('PHP_WINDOWS_VERSION_BUILD') && version_compare(PHP_VERSION, '5.3.4', '<')) {
            $this->useOpenSsl = false;
        } elseif (!function_exists('openssl_random_pseudo_bytes')) {
            $this->useOpenSsl = false;
        } else {
            $this->useOpenSsl = true;
        }
    }

    public function generateToken($length = 5)
    {
        return strtolower(substr(rtrim(strtr(base64_encode($this->getRandomNumber()), '+/', '-_'), '='), 0, $length));
    }

    private function getRandomNumber()
    {
        $nbBytes = 32;

        // try OpenSSL
        if ($this->useOpenSsl) {
            $bytes = openssl_random_pseudo_bytes($nbBytes, $strong);

            if (false !== $bytes && true === $strong) {
                return $bytes;
            }
        }

        return hash('sha256', uniqid(mt_rand(), true), true);
    }
}
