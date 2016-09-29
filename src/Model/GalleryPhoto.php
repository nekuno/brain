<?php

namespace Model;

class GalleryPhoto extends Photo
{

    protected function getSizes()
    {
        $path = 'media/cache/';

        return array(
            'small' => array(
                'cache' => $path . 'gallery_small/',
                'resolve' => $path . 'resolve/gallery_small/',
            ),
            'medium' => array(
                'cache' => $path . 'gallery_medium/',
                'resolve' => $path . 'resolve/gallery_medium/',
            ),
            'big' => array(
                'cache' => $path . 'gallery_big/',
                'resolve' => $path . 'resolve/gallery_big/',
            ),
        );
    }

}