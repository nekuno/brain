<?php

namespace Http\OAuth\ResourceOwner;

use Model\User\TokensModel;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class TwitterResourceOwner
 *
 * @package ApiConsumer\ResourceOwner
 */
class TwitterResourceOwner extends Oauth1GenericResourceOwner
{
    protected $name = TokensModel::TWITTER;

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
        if ($username){
            $request->getQuery()->add('screen_name', $username);
        }

        return $request;
    }
}
