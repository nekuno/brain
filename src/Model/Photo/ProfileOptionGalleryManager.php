<?php

namespace Model\Photo;

class ProfileOptionGalleryManager extends GalleryManager
{
    protected $folderBase = 'uploads/../../../brain/public/options/';

    public function saveOption(array $data)
    {
        $picture = $data['picture'];
        if (empty($picture)){
            return '';
        }
        $file = @file_get_contents($picture);

        $id = $data['id'];
        $extension = $ext = pathinfo($picture, PATHINFO_EXTENSION);
        $fileName = $this->buildFileName($id, $extension);

        $type = $data['type'];
        $folder = $this->buildFolderName($type);

        return $this->saveFile($fileName, $folder, $file);
    }

    protected function buildFolderName($type)
    {
        return $this->folderBase . $type .'/';
    }

    protected function buildFileName($optionId, $extension = null)
    {
        return $optionId . '.' . $extension;
    }

}