<?php

namespace Model\Photo;

class ProposalGalleryManager extends GalleryManager
{
    protected $folderBase = 'uploads/proposals/';

    //TODO: Add random photos
    public function getRandomPhoto()
    {
        return 'fake.png';
    }
}