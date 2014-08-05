<?php

namespace ApiConsumer\ResourceOwner;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class FacebookResourceOwner
 *
 * @package ApiConsumer\ResourceOwner
 */
class FacebookResourceOwner extends Oauth2GenericResourceOwner
{
    protected $name = 'facebook';

    /**
     * {@inheritDoc}
     */
    protected function configureOptions(OptionsResolverInterface $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'base_url' => 'https://graph.facebook.com/v2.0/',
        ));
    }
}
