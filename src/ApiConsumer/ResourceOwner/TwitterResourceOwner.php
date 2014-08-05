<?php

namespace ApiConsumer\ResourceOwner;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
    protected function configureOptions(OptionsResolverInterface $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'base_url' => 'https://api.twitter.com/1.1/',
        ));
    }
}
