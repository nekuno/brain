<?php

namespace Http\OAuth\ResourceOwner;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class FacebookResourceOwner
 *
 * @package ApiConsumer\ResourceOwner
 */
class
FacebookResourceOwner extends Oauth2GenericResourceOwner
{
    protected $name = 'facebook';

    /**
     * {@inheritDoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'base_url' => 'https://graph.facebook.com/v2.0/',
        ));
    }
}
