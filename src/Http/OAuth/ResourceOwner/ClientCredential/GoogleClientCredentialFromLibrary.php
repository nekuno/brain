<?php

namespace Http\OAuth\ResourceOwner\ClientCredential;

use Symfony\Component\OptionsResolver\OptionsResolver;

class GoogleClientCredentialFromLibrary extends AbstractClientCredential
{

    /**
     * @var \Google_Client
     */
    protected $client = null;

    /**
     * @var \Google_Auth_AssertionCredentials
     */
    protected $credentials = null;

    /**
     * {@inheritDoc}
     */
    public function getClientToken()
    {

        $client = $this->getClient();

        /* @var $auth \Google_Auth_OAuth2 */
        $auth = $client->getAuth();
        if ($auth->isAccessTokenExpired()) {
            $auth->refreshTokenWithAssertion($this->getCredentials());
        }

        $tokenInfo = json_decode($client->getAccessToken());

        if (!isset($tokenInfo->access_token)) {
            return '';
        }

        return $tokenInfo->access_token;
    }

    /**
     * {@inheritDoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            array(
                'key_password' => 'notasecret',
            )
        );
        $resolver->setRequired(
            array(
                'service_account_email',
                'key_file',
                'scopes'
            )
        );
        $resolver->setDefined(
            array(
                'application_token',
            )
        );
    }

    /**
     * @return \Google_Client
     */
    protected function getClient()
    {

        if ($this->client instanceof \Google_Client) {
            return $this->client;
        }

        $this->client = new \Google_Client();
        $this->client->setApplicationName('Nekuno'); // seems irrelevant
        $this->client->setAssertionCredentials($this->getCredentials());

        /* @var $auth \Google_Auth_OAuth2 */
        $auth = $this->client->getAuth();
        if ($auth->isAccessTokenExpired()) {
            $auth->refreshTokenWithAssertion($this->getCredentials());
        }

        return $this->client;

    }

    /**
     * @return \Google_Auth_AssertionCredentials
     */
    protected function getCredentials()
    {

        if ($this->credentials instanceof \Google_Auth_AssertionCredentials) {
            return $this->credentials;
        }

        $serviceAccountName = $this->getOption('service_account_email');
        $keyFileLocation = $this->getOption('key_file');
        $keyPassword = $this->getOption('key_password');
        $scopes = $this->getOption('scopes');
        $key = file_get_contents($keyFileLocation);

        $this->credentials = new \Google_Auth_AssertionCredentials(
            $serviceAccountName,
            $scopes,
            $key,
            $keyPassword
        );

        return $this->credentials;
    }
} 