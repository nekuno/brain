<?php

namespace Service;

use Model\Availability\AvailabilityManager;
use Model\Exception\ValidationException;
use Model\Filters\FilterUsersManager;
use Model\Photo\PhotoManager;
use Model\Photo\ProposalGalleryManager;
use Model\Proposal\Proposal;
use Model\Profile\ProfileFields\FieldAvailability;
use Model\Proposal\ProposalManager;
use Model\Proposal\ProposalTagManager;
use Model\User\User;

class ProposalService
{
    protected $userService;
    protected $availabilityManager;
    protected $availabilityService;
    protected $proposalManager;
    protected $proposalTagManager;
    protected $proposalGalleryManager;
    protected $photoManager;
    protected $filterUsersManager;

    /**
     * ProposalService constructor.
     * @param UserService $userService
     * @param AvailabilityManager $availabilityManager
     * @param AvailabilityService $availabilityService
     * @param ProposalManager $proposalManager
     * @param ProposalTagManager $proposalTagManager
     * @param FilterUsersManager $filterUsersManager
     * @param ProposalGalleryManager $proposalGalleryManager
     * @param PhotoManager $photoManager
     */
    public function __construct(
        UserService $userService,
        AvailabilityManager $availabilityManager,
        AvailabilityService $availabilityService,
        ProposalManager $proposalManager,
        ProposalTagManager $proposalTagManager,
        FilterUsersManager $filterUsersManager,
        ProposalGalleryManager $proposalGalleryManager,
        PhotoManager $photoManager
    ) {
        $this->userService = $userService;
        $this->availabilityManager = $availabilityManager;
        $this->availabilityService = $availabilityService;
        $this->proposalManager = $proposalManager;
        $this->proposalTagManager = $proposalTagManager;
        $this->proposalGalleryManager = $proposalGalleryManager;
        $this->photoManager = $photoManager;
        $this->filterUsersManager = $filterUsersManager;
    }

    public function getById($proposalId, $locale)
    {
        $proposal = $this->proposalManager->getById($proposalId, $locale);

        $proposal = $this->addAvailabilityField($proposal);

        $proposal = $this->addFilters($proposal);

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

        $proposals = $this->setMatches($proposals);
        $proposals = $this->orderByMatches($proposals);

        foreach ($proposals as $index => $proposal) {
            $proposals[$index] = $this->addAvailabilityField($proposal);
        }

        return $proposals;
    }

    /**
     * @param Proposal[] $proposals
     * @return array
     */
    protected function setMatches(array $proposals)
    {
        foreach ($proposals as $proposal) {
            $matches = $this->userService->getOtherByProposal($proposal);
            $proposal->setMatches($matches);
        }

        return $proposals;
    }

    /**
     * @param Proposal[] $proposals
     * @return Proposal[]
     */
    protected function orderByMatches(array $proposals)
    {
        usort($proposals, function($a, $b){
            /** @var $a Proposal */
            /** @var $b Proposal */
            if ($a->countMatches() == $b->countMatches()){
                return 0;
            }

            return ($a->countMatches() > $b->countMatches()) ? 1 : 1;
        });

        return $proposals;
    }

    /**
     * @param Proposal $proposal
     * @return Proposal
     */
    protected function addAvailabilityField(Proposal $proposal)
    {
        $availability = $this->availabilityService->getByProposal($proposal);

        if (null == $availability) {
            return $proposal;
        }

        $availabilityField = new FieldAvailability();
        $availabilityField->setAvailability($availability);
        $availabilityField->setName('availability');
        $proposal->addField($availabilityField);

        return $proposal;
    }

    /**
     * @param Proposal $proposal
     * @return Proposal
     */
    protected function addFilters(Proposal $proposal)
    {
        $proposalId = $proposal->getId();
        $filters = $this->filterUsersManager->getFilterUsersByProposalId($proposalId);
        $proposal->setFilters($filters);

        return $proposal;
    }

    public function create($data, User $user)
    {
        $proposalId = $this->proposalManager->create();
        $proposal = $this->proposalManager->update($proposalId, $data);
        $this->proposalManager->relateToUser($proposal, $user);

        $this->createAvailability($proposal, $data);
        $proposal = $this->addAvailabilityField($proposal);

        $filters = $this->getFiltersData($data);

        if (!empty($filters)) {
            try {
//            $this->validateFilters($data, $user->getId());
            } catch (ValidationException $e) {
                $data = array('locale' => 'en');
                $this->delete($proposal->getId(), $data);
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
        $this->createAvailability($proposal, $data);
        $proposal = $this->addAvailabilityField($proposal);

        return $proposal;
    }

    protected function saveProposalPhoto(User $user, array $data)
    {
        $photo = isset($data['photo']) ? $data['photo'] : $this->proposalGalleryManager->getRandomPhoto();
//        $extension = $this->photoManager->validate($photo);
//        $this->proposalGalleryManager->save($photo, $user, $extension);
    }

    protected function updateFilters(Proposal $proposal, $filters)
    {
        $proposalId = $proposal->getId();
        $filters = $this->filterUsersManager->updateFilterUsersByProposalId($proposalId, $filters);
        $proposal->setFilters($filters);

        return $proposal;
    }

    public function delete($proposalId, array $data)
    {
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
        $otherUserId = $data['candidateId'];
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
        $otherUserId = $data['candidateId'];
        $skipped = $data['skipped'];

        return $this->proposalManager->setSkippedCandidate($proposalId, $otherUserId, $skipped);
    }

    protected function getAvailabilityId(Proposal $proposal)
    {
        /** @var FieldAvailability $availabilityField */
        $availabilityField = $proposal->getField('availability');
        $availabilityId = $availabilityField->getAvailability()->getId();

        return $availabilityId;
    }

    protected function createAvailability(Proposal $proposal, array $data)
    {
        $availability = $this->availabilityService->saveWithData($data['fields']);

        if ($availability) {
            $this->availabilityManager->relateToProposal($availability, $proposal);
        }

        return $availability;
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