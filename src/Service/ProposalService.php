<?php

namespace Service;

use Model\Availability\AvailabilityManager;
use Model\Filters\FilterUsersManager;
use Model\Photo\PhotoManager;
use Model\Photo\ProposalGalleryManager;
use Model\Profile\OtherProfileData;
use Model\Proposal\OwnProposalLikedPaginated;
use Model\Proposal\Proposal;
use Model\Profile\ProfileFields\FieldAvailability;
use Model\Proposal\ProposalLiked;
use Model\Proposal\ProposalManager;
use Model\Proposal\ProposalTagManager;
use Model\User\User;
use Model\User\UserManager;
use Paginator\Paginator;
use Symfony\Component\HttpFoundation\Request;

class ProposalService
{
    protected $userService;
    protected $availabilityManager;
    protected $availabilityService;
    protected $userManager;
    protected $proposalManager;
    protected $proposalTagManager;
    protected $proposalGalleryManager;
    protected $photoManager;
    protected $filterUsersManager;
    protected $paginator;
    protected $ownProposalLikedPaginated;

    /**
     * ProposalService constructor.
     * @param UserService $userService
     * @param AvailabilityManager $availabilityManager
     * @param AvailabilityService $availabilityService
     * @param UserManager $userManager
     * @param ProposalManager $proposalManager
     * @param ProposalTagManager $proposalTagManager
     * @param FilterUsersManager $filterUsersManager
     * @param ProposalGalleryManager $proposalGalleryManager
     * @param PhotoManager $photoManager
     * @param Paginator $paginator
     * @param OwnProposalLikedPaginated $ownProposalLikedPaginated
     */
    public function __construct(
        UserService $userService,
        AvailabilityManager $availabilityManager,
        AvailabilityService $availabilityService,
        UserManager $userManager,
        ProposalManager $proposalManager,
        ProposalTagManager $proposalTagManager,
        FilterUsersManager $filterUsersManager,
        ProposalGalleryManager $proposalGalleryManager,
        PhotoManager $photoManager,
        Paginator $paginator,
        OwnProposalLikedPaginated $ownProposalLikedPaginated
    ) {
        $this->userService = $userService;
        $this->availabilityManager = $availabilityManager;
        $this->availabilityService = $availabilityService;
        $this->userManager = $userManager;
        $this->proposalManager = $proposalManager;
        $this->proposalTagManager = $proposalTagManager;
        $this->proposalGalleryManager = $proposalGalleryManager;
        $this->photoManager = $photoManager;
        $this->filterUsersManager = $filterUsersManager;
        $this->paginator = $paginator;
        $this->ownProposalLikedPaginated = $ownProposalLikedPaginated;
    }

    public function getById($proposalId, $locale, User $user = null)
    {
        $proposal = $this->proposalManager->getById($proposalId, $locale);

        $proposal = $this->addAvailabilityField($proposal);

        $proposal = $this->addFilters($proposal);

        $proposal = $this->addHasMatch($proposal, $user);

        return $proposal;
    }

    public function getByUser(User $user, $locale)
    {
        $proposalIds = $this->proposalManager->getIdsByUser($user);

        $proposals = $this->buildProposalsFromIds($proposalIds, $locale);

        $proposals = $this->setMatches($proposals);
        $proposals = $this->orderByMatches($proposals);

        foreach ($proposals as $index => $proposal) {
            $proposals[$index] = $this->addAvailabilityField($proposal);
        }

        return $proposals;
    }

    protected function buildProposalsFromIds(array $proposalIds, $locale)
    {
        $proposals = array();
        foreach ($proposalIds as $proposalId) {
            $proposal = $this->getById($proposalId, $locale);
            $proposals[] = $proposal;
        }

        return $proposals;
    }

    /**
     * @param $locale
     * @param Request $request
     * @param User $user
     * @return array //TODO: change to Pagination object
     */
    public function getOwnLiked($locale, Request $request, User $user)
    {
        $userId = $user->getId();
        $pagination = $this->getLikedPagination($request, $userId);
        $items = $pagination['items'];
        $proposals = $this->buildProposalsLiked($items, $locale);
        $pagination['items'] = $proposals;

        return $pagination;
    }

    protected function buildProposalsLiked(array $items, $locale)
    {
        $proposalsLiked = array();

        foreach ($items as $item) {
            $proposalData = $item['proposalData'];

            $proposalLiked = new ProposalLiked();

            $proposalId = $proposalData['proposalId'];
            $proposal = $this->proposalManager->getById($proposalId, $locale);
            $proposalLiked->setProposal($proposal);

            $hasMatch = $proposalData['hasMatch'];
            $proposalLiked->setHasMatch($hasMatch);
            $user = $this->userManager->getByProposalCreated($proposal);

            $profileData = new OtherProfileData();
            $profileData->setUserName($user->getUsername());
            $profileData->setSlug($user->getSlug());

            $photo = $user->getPhoto();
            $thumbnail = $photo->getUrl();
            $profileData->setPhotos([$thumbnail]);

            $proposalLiked->setOwner($profileData);
            $proposalsLiked[] = $proposalLiked;
        }

        return $proposalsLiked;
    }

    protected function getLikedPagination(Request $request, $userId)
    {
        $filters = array('userId' => $userId);

        $pagination = $this->paginator->paginate($filters, $this->ownProposalLikedPaginated, $request);

        return $pagination;
    }

    /**
     * @param Proposal[] $proposals
     * @return array
     */
    protected function setMatches(array $proposals)
    {
        foreach ($proposals as $proposal) {
            $matches = $this->userService->getOtherInterestedInProposal($proposal);
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
        usort(
            $proposals,
            function ($a, $b) {
                /** @var $a Proposal */
                /** @var $b Proposal */
                if ($a->countMatches() == $b->countMatches()) {
                    return 0;
                }

                return ($a->countMatches() > $b->countMatches()) ? 1 : 1;
            }
        );

        return $proposals;
    }

    /**
     * @param Proposal $proposal
     * @return Proposal
     */
    public function addAvailabilityField(Proposal $proposal)
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

    /**
     * @param Proposal $proposal
     * @param User $user
     * @return Proposal
     */
    protected function addHasMatch(Proposal $proposal, User $user = null)
    {
        if (null === $user) {
            return $proposal;
        }

        $hasMatch = $this->proposalManager->getHasMatch($proposal, $user);

        $proposal->setHasMatch($hasMatch);

        return $proposal;
    }

    public function create($data, User $user)
    {

        $proposalId = $this->proposalManager->create();

        if (!isset($data['photo'])) {
            $data['photo'] = base64_encode($this->proposalGalleryManager->getRandomPhoto($data['type']));
        }

        $path = $this->saveProposalPhoto($proposalId, $user, $data);
        $data['fields']['photo'] = $path ? $path : '';

        $proposal = $this->proposalManager->update($proposalId, $data);
        $this->proposalManager->relateToUser($proposal, $user);

        $this->createAvailability($proposal, $data);
        $proposal = $this->addAvailabilityField($proposal);

        $proposal = $this->updateFilters($proposal, $data);

        return $proposal;
    }

    public function update($proposalId, $data, User $user)
    {

        $path = $this->saveProposalPhoto($proposalId, $user, $data);
        $data['fields']['photo'] = $path ? $path : '';

        $proposal = $this->proposalManager->update($proposalId, $data);
        $proposal = $this->addAvailabilityField($proposal);

        if ($proposal->getField('availability')) {
            $availabilityId = $this->getAvailabilityId($proposal);
            $this->availabilityManager->delete($availabilityId);
            $proposal->removeField('availability');
        }
        $this->createAvailability($proposal, $data);
        $proposal = $this->addAvailabilityField($proposal);

        $proposal = $this->updateFilters($proposal, $data);

        return $proposal;
    }

    protected function saveProposalPhoto($proposalId, User $user, array $data)
    {

        $path = null;

        if (isset($data['photo'])) {
            $file = base64_decode($data['photo']);
            $extension = $this->photoManager->validate($file);
            $path = $this->proposalGalleryManager->save($file, $user, $extension, $proposalId);
        }

        return $path;
    }

    protected function updateFilters(Proposal $proposal, $data)
    {
        $filters = $this->getFiltersData($data);

        if (empty($filters)) {
            return $proposal;
        }
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