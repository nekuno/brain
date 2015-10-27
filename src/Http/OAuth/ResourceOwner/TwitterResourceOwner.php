<?php

namespace Http\OAuth\ResourceOwner;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class TwitterResourceOwner
 *
 * @package ApiConsumer\ResourceOwner
 */
class TwitterResourceOwner extends Oauth1GenericResourceOwner
{
    protected $name = 'twitter';

    /**
     * {@inheritDoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'base_url' => 'https://api.twitter.com/1.1/',
        ));
    }

    public function getAPIRequest($url, array $query = array(), array $token = array())
    {
        $request = parent::getAPIRequest($url, $query, $token);

        $clientToken = $this->getClientToken();

        if (!empty($clientToken)) {
            $request->addHeader('Authorization', 'Bearer ' . $clientToken);
        }

        $username = $this->getUsername($token);
        if ($username) {
            $request->getQuery()->add('screen_name', $username);
        }

        return $request;
    }

    public function lookupUsersBy($parameter,array $userIds)
    {
        if ($parameter !== 'user_id' && $parameter !== 'screen_name'){
            return false;
        }

        $chunks = array_chunk($userIds, 100);
        $baseUrl = $this->getOption('base_url');
        $url = $baseUrl . 'users/lookup';

        $users = array();
        foreach ($chunks as $chunk) {
            $query = array($parameter => implode(', ', $chunk));
            $request = $this->getAPIRequest($url, $query);
            $response = $this->httpClient->send($request)->json();
            $users = array_merge($users, $response);
        }

        return $users;
    }

}
