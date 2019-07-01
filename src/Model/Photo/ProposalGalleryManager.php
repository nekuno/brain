<?php

namespace Model\Photo;

use Model\Metadata\ProposalMetadataManager;
use Model\User\User;

class ProposalGalleryManager extends GalleryManager
{
    protected $folderBase = 'uploads/proposals/';
    protected $metadataManager;

    /**
     * ProposalGalleryManager constructor.
     * @param $base
     * @param ProposalMetadataManager $metadataManager
     */
    public function __construct($base, ProposalMetadataManager $metadataManager)
    {
        parent::__construct($base);
        $this->metadataManager = $metadataManager;
    }

    public function getRandomPhoto($type)
    {

        $path = 'default_images/default-upload-image.png';
        $pathType = 'default_images/proposals/' . $type . '.png';

        $metadata = $this->metadataManager->getMetadata();
        if (in_array($type, array_keys($metadata)) && is_readable($pathType)) {
            $path = $pathType;
        }

        return file_get_contents($path);
    }

    public function save($file, User $user, $extension, $proposalId = null)
    {
        $name = $this->buildFileName($proposalId, $extension);
        $folder = $this->buildFolderName($user->getId());

        return $this->saveFile($name, $folder, $file);
    }
}