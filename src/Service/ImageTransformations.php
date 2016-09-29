<?php

namespace Service;

class ImageTransformations
{
    public function isImage($url)
    {
        return preg_match('/\.(gif|png|jpe?g|bmp|svg)$/', $url);
    }

    public function isGif($url)
    {
        return preg_match('/\.gif$/', $url);
    }

    public function gifToPng($url)
    {
        $img = imagecreatefromgif($url);
        ob_start();
        imagepng($img, null, 5, PNG_NO_FILTER);
        $pngData = ob_get_contents();
        ob_end_clean();
        unset($img);

        return 'data:image/jpg;base64,' . base64_encode($pngData);
    }
}