<?php

namespace Http\OAuth\ResourceOwner\ClientCredential;

use Symfony\Component\OptionsResolver\OptionsResolver;

class GoogleClientCredentialFromLibrary extends AbstractClientCredential
{

    protected $key = null;

    /**
     * {@inheritDoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'key_password' => 'notasecret',
        ));
        $resolver->setRequired(
            array(
                'service_account_email',
                'key_file',
                'scopes'
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getClientToken()
    {
        $serviceAccountName = $this->getOption('service_account_email');
        $keyFileLocation = $this->getOption('key_file');
        $keyPassword = $this->getOption('key_password');
        $scopes = $this->getOption('scopes');


        $client = new \Google_Client();
        $client->setApplicationName("Nekuno"); //seems irrelevant

        if (isset($_SESSION['google']['service_token'])) {
            $client->setAccessToken($_SESSION['google']['service_token']);
        }
        if (is_null($this->key)) {
            $this->key = file_get_contents($keyFileLocation);
        }

        $credentials = new \Google_Auth_AssertionCredentials(
            $serviceAccountName,
            $scopes,
            $this->key,
            $keyPassword
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