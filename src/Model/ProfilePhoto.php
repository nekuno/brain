<?php

namespace Model;

class ProfilePhoto extends Photo
{

    public function getDefaultPath()
    {
        return 'media/cache/user_avatar_180x180/bundles/qnoowweb/images/user-no-img.jpg';
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