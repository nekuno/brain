<?php

namespace Model\Photo;

class ProfileOptionGalleryManager extends GalleryManager
{
    protected $folderBase = 'options/';

    protected $brainBaseUrl;

    public function __construct($base, $brainBaseUrl)
    {
        $this->brainBaseUrl = $brainBaseUrl;
        parent::__construct($base);
    }

    //TODO: Refactor to use committed files
    public function saveOption(array $data)
    {
        $picture = $data['picture'];
        if (empty($picture)){
            return '';
        }
//        $file = @file_get_contents($picture);

        $id = $data['id'];
//        $extension = $ext = pathinfo($picture, PATHINFO_EXTENSION);
        $extension = 'jpg';
        $fileName = $this->buildFileName($id, $extension);

        $type = strtolower($data['type']);
        $folder = $this->buildFolderName($type);

//        $relativePath = $this->saveFile($fileName, $folder, $file);
        $relativePath = $folder . $fileName;

        return $this->brainBaseUrl . $relativePath;
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