<?php

namespace Http\OAuth\ResourceOwner\ClientCredential;


class GoogleClientCredentialFromLibrary extends AbstractClientCredential
{

    /**
     * {@inheritDoc}
     */
    public function getClientToken()
    {
        $service_account_name = $this->options['service_account_email'];
        $key_file_location = $this->options['key_file'];
        $scopes = $this->options['scopes'];

        $client = new \Google_Client();
        $client->setApplicationName("Nekuno"); //seems irrelevant

        if (isset($_SESSION['google']['service_token'])) {
            $client->setAccessToken($_SESSION['google']['service_token']);
        }
        $key = file_get_contents($key_file_location);

        $credentials = new \Google_Auth_AssertionCredentials(
            $service_account_name,
            $scopes,
            $key
        );

        $client->setAssertionCredentials($credentials);
        if ($client->getAuth()->isAccessTokenExpired()) {
            $client->getAuth()->refreshTokenWithAssertion($credentials);
        }
        $_SESSION['google']['service_token'] = $client->getAccessToken();

        $tokenInfo = json_decode($_SESSION['google']['service_token']);

        if (!isset($tokenInfo->access_token)) {
            return '';
        }

        return $tokenInfo->access_token;
    }
} 