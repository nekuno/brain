<?php

namespace Model\Photo;

use Model\User\User;

class ProposalGalleryManager extends GalleryManager
{
    protected $folderBase = 'uploads/proposals/';

    public function getRandomPhoto()
    {
        return file_get_contents('default_images/default-upload-image.png');
    }

    public function save($file, User $user, $extension, $proposalId = null)
    {
        $name = $this->buildFileName($proposalId, $extension);
        $folder = $this->buildFolderName($user->getId());

        return $this->saveFile($name, $folder, $file);
    }
}