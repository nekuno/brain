<?php

namespace Model\Photo;

use Model\Exception\ErrorList;
use Model\Exception\ValidationException;
use Model\User\User;

class GalleryManager
{
    protected $base;

    public function __construct($base)
    {
        $this->base = $base;
    }

    public function save($file, User $user, $extension)
    {
        $name = $this->buildFileName($user->getUsernameCanonical(), $extension);
        $folder = $this->buildGalleryFolderName($user->getId());
        if (!is_dir($this->base . $folder)) {
            mkdir($this->base . $folder, 0775);
        }
        $path = $folder . $name;
        $saved = file_put_contents($this->base . $path, $file);

        if ($saved === false) {
            $errorList = new ErrorList();
            $errorList->addError('photo', 'File can not be saved');
            throw new ValidationException($errorList);
        }

        return $path;
    }

    public function deleteAllFromUser(User $user)
    {
        $userId = $user->getId();
        $folder = $this->buildGalleryFolderName($userId);
        $fullPath = $this->base . $folder;

        //https://stackoverflow.com/questions/4594180/deleting-all-files-from-a-folder-using-php
        $files = glob($fullPath . '*'); // get all file names
        foreach($files as $file){ // iterate files
            if(is_file($file)){
                unlink($file); // delete file
            }
        }

        rmdir($fullPath);
    }

    protected function buildFileName($username, $extension)
    {
        return sha1(uniqid($username . '_' . time(), true)) . '.' . $extension;
    }

    protected function buildGalleryFolderName($userId)
    {
        return 'uploads/gallery/' . md5($userId) . '/';
    }
}