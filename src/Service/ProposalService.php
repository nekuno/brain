<?php

namespace Service;

use Model\Availability\AvailabilityDataFormatter;
use Model\Date\DateManager;
use Model\Availability\AvailabilityManager;
use Model\Exception\ValidationException;
use Model\Filters\FilterUsersManager;
use Model\Photo\PhotoManager;
use Model\Photo\ProposalPhotoManager;
use Model\Proposal\Proposal;
use Model\Proposal\ProposalFields\ProposalFieldAvailability;
use Model\Proposal\ProposalManager;
use Model\Proposal\ProposalTagManager;
use Model\User\User;

class ProposalService
{
    protected $dateManager;
    protected $availabilityManager;
    protected $availabilityDataFormatter;
    protected $proposalManager;
    protected $proposalTagManager;
    protected $proposalPhotoManager;
    protected $photoManager;
    protected $filterUsersManager;

    /**
     * ProposalService constructor.
     * @param DateManager $dateManager
     * @param AvailabilityManager $availabilityManager
     * @param AvailabilityDataFormatter $availabilityDataFormatter
     * @param ProposalManager $proposalManager
     * @param ProposalTagManager $proposalTagManager
     * @param FilterUsersManager $filterUsersManager
     * @param ProposalPhotoManager $proposalPhotoManager
     * @param PhotoManager $photoManager
     */
    public function __construct(
        DateManager $dateManager,
        AvailabilityManager $availabilityManager,
        AvailabilityDataFormatter $availabilityDataFormatter,
        ProposalManager $proposalManager,
        ProposalTagManager $proposalTagManager,
        FilterUsersManager $filterUsersManager,
        ProposalPhotoManager $proposalPhotoManager,
        PhotoManager $photoManager
    ) {
        $this->dateManager = $dateManager;
        $this->availabilityManager = $availabilityManager;
        $this->availabilityDataFormatter = $availabilityDataFormatter;
        $this->proposalManager = $proposalManager;
        $this->proposalTagManager = $proposalTagManager;
        $this->proposalPhotoManager = $proposalPhotoManager;
        $this->photoManager = $photoManager;
        $this->filterUsersManager = $filterUsersManager;
    }

    public function getById($proposalId, $locale)
    {
        $proposal = $this->proposalManager->getById($proposalId, $locale);

        $availability = $this->availabilityManager->getByProposal($proposal);
        /** @var ProposalFieldAvailability $availabilityField */

        if (null !== $availability) {
            $availabilityField = new ProposalFieldAvailability();
            $availabilityField->setAvailability($availability);
            $proposal->addField($availabilityField);
        }

        return $proposal;
    }

    public function getByUser(User $user, $locale)
    {
        $proposalIds = $this->proposalManager->getIdsByUser($user);

        $proposals = array();
        foreach ($proposalIds as $proposalId) {
            $proposal = $this->getById($proposalId, $locale);
            $proposals[] = $proposal;
        }

        foreach ($proposals as $proposal) {
            $availability = $this->availabilityManager->getByProposal($proposal);
            if (null == $availability) {
                continue;
            }

            $dates = $this->dateManager->getByAvailability($availability);
            $availability->setDates($dates);
            $availabilityField = new ProposalFieldAvailability();
            $availabilityField->setAvailability($availability);
            $proposal->addField($availabilityField);
        }

        return $proposals;
    }

    public function create($data, User $user)
    {
        $proposalId = $this->proposalManager->create();
        $proposal = $this->proposalManager->update($proposalId, $data);
        $this->proposalManager->relateToUser($proposal, $user);

        $proposal = $this->createAvailability($proposal, $data);

        $filters = $this->getFiltersData($data);

        if (!empty($filters)) {
            try {
//            $this->validateFilters($data, $user->getId());
            } catch (ValidationException $e) {
                $data = array('proposalId' => $proposal->getId());
                $this->delete($data);
                throw $e;
            }

            $proposal = $this->updateFilters($proposal, $filters);
        }

        return $proposal;
    }

    public function update($proposalId, User $user, $data)
    {
        $this->saveProposalPhoto($user, $data);

        $proposal = $this->proposalManager->update($proposalId, $data);

        if ($proposal->getField('availability')) {
            $availabilityId = $this->getAvailabilityId($proposal);
            $this->availabilityManager->delete($availabilityId);
            $proposal->removeField('availability');
        }
        $proposal = $this->createAvailability($proposal, $data);

        return $proposal;
    }

    protected function saveProposalPhoto(User $user, array $data)
    {
        $photo = isset($data['photo']) ? $data['photo'] : $this->proposalPhotoManager->getRandomPhoto();
        $extension = $this->photoManager->validate($photo);
        $this->proposalPhotoManager->save($photo, $user, $extension);
    }

    protected function updateFilters(Proposal $proposal, $filters)
    {
        $proposalId = $proposal->getId();
        $filters = $this->filterUsersManager->updateFilterUsersByProposalId($proposalId, $filters);
        $proposal->setFilters($filters);

        return $proposal;
    }

    public function delete(array $data)
    {
        $proposalId = (integer)$data['proposalId'];
        $locale = $data['locale'];
        $proposal = $this->getById($proposalId, $locale);

        if ($proposal->getField('availability')) {
            $availabilityId = $this->getAvailabilityId($proposal);
            $this->availabilityManager->delete($availabilityId);
        }

        $this->deleteTags($proposal);
        $this->proposalManager->delete($proposalId);
    }

    public function setInterestedInProposal(User $user, array $data)
    {
        $proposalId = $data['proposalId'];
        $interested = $data['interested'];

        return $this->proposalManager->setInterestedInProposal($user, $proposalId, $interested);
    }

    public function setAcceptedCandidate(array $data)
    {
        $proposalId = $data['proposalId'];
        $otherUserId = $data['otherUserId'];
        $accepted = $data['accepted'];

        return $this->proposalManager->setAcceptedCandidate($otherUserId, $proposalId, $accepted);
    }

    public function setSkippedProposal(array $data, User $user)
    {
        $proposalId = $data['proposalId'];
        $skipped = $data['skipped'];
        return $this->proposalManager->setSkippedProposal($user, $proposalId, $skipped);
    }

    public function setSkippedCandidate(array $data)
    {
        $proposalId = $data['proposalId'];
        $otherUserId = $data['otherUserId'];
        $skipped = $data['skipped'];

        return $this->proposalManager->setSkippedCandidate($proposalId, $otherUserId, $skipped);
    }

    protected function getAvailabilityId(Proposal $proposal)
    {
        /** @var ProposalFieldAvailability $availabilityField */
        $availabilityField = $proposal->getField('availability');
        $availabilityId = $availabilityField->getAvailability()->getId();

        return $availabilityId;
    }

    protected function createAvailability(Proposal $proposal, array $data)
    {
        $formattedData = $this->availabilityDataFormatter->getFormattedData($data);
        $static = $formattedData['static'];
        $dynamic = $formattedData['dynamic'];

        $availability = null;
        if (!empty($static) || !empty($dynamic)) {
            $availability = $this->availabilityManager->create();
        }

        if (!empty($static)) {
            $this->availabilityManager->addStatic($availability, $static);
        }

        if (!empty($dynamic)) {
            $this->availabilityManager->addDynamic($availability, $dynamic);
        }

        if ($availability) {
            $this->availabilityManager->relateToProposal($availability, $proposal);

            $availabilityField = new ProposalFieldAvailability();
            $availabilityField->setAvailability($availability);
            $proposal->addField($availabilityField);
        }

        return $proposal;
    }

    protected function deleteTags(Proposal $proposal)
    {
        $tagFields = array('tag', 'tag_and_suggestion');
        foreach ($proposal->getFields() as $field) {
            $isTagField = in_array($field->getType(), $tagFields);
            if ($isTagField) {
                $tagName = $field->getName();
                $tagValue = $field->getValue();

                $this->proposalTagManager->deleteIfOrphan($tagName, $tagValue);
            }
        }
    }

    protected function getFiltersData(array $data)
    {
        return isset($data['filters']) ? $data['filters'] : array();
    }
}