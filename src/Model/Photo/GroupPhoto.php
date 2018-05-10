<?php

namespace Model\Photo;

class GroupPhoto extends Photo
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
                'cache' => $path . 'group_small/',
                'resolve' => $path . 'resolve/group_small/',
            ),
            'medium' => array(
                'cache' => $path . 'group_medium/',
                'resolve' => $path . 'resolve/group_medium/',
            ),
            'big' => array(
                'cache' => $path . 'group_big/',
                'resolve' => $path . 'resolve/group_big/',
            ),
        );
    }

}