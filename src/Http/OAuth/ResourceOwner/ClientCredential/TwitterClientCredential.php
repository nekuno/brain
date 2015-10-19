<?php
/**
 * Created by PhpStorm.
 * User: yawmoght
 * Date: 8/10/15
 * Time: 14:22
 */

namespace Http\OAuth\ResourceOwner\ClientCredential;


use Symfony\Component\OptionsResolver\OptionsResolver;

class TwitterClientCredential extends AbstractClientCredential {

    /**
     * {@inheritDoc}
     */
    public function getClientToken()
    {
        return $this->getOption('application_token');
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setRequired(
            array(
                'application_token',
            )
        );

    }


}