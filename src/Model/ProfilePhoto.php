<?php

namespace Model;

class ProfilePhoto extends Photo
{

    protected function getSizes()
    {
        $path = 'media/cache/';

        return array(
            'small' => array(
                'cache' => $path . 'profile_small/',
                'resolve' => $path . 'resolve/profile_small/',
            ),
            'medium' => array(
                'cache' => $path . 'profile_medium/',
                'resolve' => $path . 'resolve/profile_medium/',
            ),
            'big' => array(
                'cache' => $path . 'profile_big/',
                'resolve' => $path . 'resolve/profile_big/',
            ),
        );
    }

}