<?php

namespace Model\Photo;

use Model\Exception\ErrorList;
use Model\Exception\ValidationException;
use Model\User\User;

class GalleryManager
{
    protected $base;

    protected $folderBase = 'uploads/gallery/';

    public function __construct($base)
    {
        $this->base = $base;
    }

    public function save($file, User $user, $extension)
    {
        $name = $this->buildFileName($user->getUsernameCanonical(), $extension);
        $folder = $this->buildFolderName($user->getId());

        return $this->saveFile($name, $folder, $file);
    }

    protected function saveFile($fileName, $folder, $file)
    {
        if (!is_dir($this->base . $this->folderBase)) {
            mkdir($this->base . $this->folderBase, 0775);
        }

        if (!is_dir($this->base . $folder)) {
            mkdir($this->base . $folder, 0775);
        }

        $path = $folder . $fileName;
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
        $folder = $this->buildFolderName($userId);
        $fullPath = $this->base . $folder;

        //https://stackoverflow.com/questions/4594180/deleting-all-files-from-a-folder-using-php
        $files = glob($fullPath . '*'); // get all file names
        foreach ($files as $file) { // iterate files
            if (is_file($file)) {
                unlink($file); // delete file
            }
        }

        rmdir($fullPath);
    }

    protected function buildFileName($name, $extension)
    {
        return sha1(uniqid($name . '_' . time(), true)) . '.' . $extension;
    }

    protected function buildFolderName($userId)
    {
        return $this->folderBase . md5($userId) . '/';
    }
}