<?php

namespace Model\Photo;

class ProposalPhotoManager extends GalleryManager
{
    protected $folderBase = 'uploads/proposals/';

    //TODO: Add random photos
    public function getRandomPhoto()
    {
        return '';
    }
}