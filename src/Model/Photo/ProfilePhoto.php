<?php

namespace Model\Photo;

class ProfilePhoto extends Photo
{

    public function getDefaultPath()
    {
        return 'bundles/qnoowlanding/images/user-no-img.jpg';
    }

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