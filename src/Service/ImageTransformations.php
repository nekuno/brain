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

    public function gifToPng($url, $newWidth = 76)
    {
        // TODO: Not enabled because slows down
        //$image = $this->resize($url, $newWidth);
        $image = imagecreatefromgif($url);
        ob_start();
        imagepng($image, null, 5, PNG_NO_FILTER);
        $pngData = ob_get_contents();
        ob_end_clean();
        unset($image);

        return 'data:image/jpg;base64,' . base64_encode($pngData);
    }

    public function resize($url, $newWidth)
    {
        $img = imagecreatefromgif($url);
        list($width, $height) = getimagesize($url);
        $ratio = $width / $height;
        $newHeight = $newWidth / $ratio;
        $newImg = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($newImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        unset($img);
        $transparent = imagecolorallocatealpha($newImg, 0, 0, 0, 127);
        imagefill($newImg, 0, 0, $transparent);
        imagealphablending($newImg, true);

        return $newImg;
    }
}