<?php

namespace Service;

class ImageTransformations
{
    public function isImage($url)
    {
        return preg_match('/\.(gif|png|jpe?g|bmp|svg)$/i', $url);
    }

    public function isGif($url)
    {
        return preg_match('/\.gif$/i', $url);
    }

    public function gifToPng($url, $newWidth = 60)
    {
        $img = imagecreatefromgif($url);
        $newImage = $this->resize($img, $newWidth);
        ob_start();
        imagepng($newImage, null, 5, PNG_NO_FILTER);
        $pngData = ob_get_contents();
        ob_end_clean();
        unset($img);

        return 'data:image/jpg;base64,' . base64_encode($pngData);
    }

    public function resize($img, $newWidth)
    {
        list($width, $height) = getimagesize($img);
        $ratio = $width / $height;
        $newHeight = $newWidth / $ratio;
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($newImage, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
        imagefill($newImage, 0, 0, $transparent);
        imagealphablending($newImage, true);

        return $newImage;
    }
}