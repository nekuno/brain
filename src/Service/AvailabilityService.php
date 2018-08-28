<?php

namespace Service;

use Model\Availability\AvailabilityDataFormatter;
use Model\Availability\AvailabilityManager;
use Model\User\User;

class AvailabilityService
{
    protected $availabilityManager;
    protected $availabilityDataFormatter;

    /**
     * AvailabilityService constructor.
     * @param $availabilityManager
     * @param $availabilityDataFormatter
     */
    public function __construct(AvailabilityManager $availabilityManager, AvailabilityDataFormatter $availabilityDataFormatter)
    {
        $this->availabilityManager = $availabilityManager;
        $this->availabilityDataFormatter = $availabilityDataFormatter;
    }

    public function get(User $user)
    {
        return $this->availabilityManager->getByUser($user);
    }

    public function create($data, User $user)
    {
        $formattedData = $this->availabilityDataFormatter->getFormattedData($data);

        $availability = $this->availabilityManager->create();
        $availability = $this->availabilityManager->addDynamic($availability, $formattedData['dynamic']);
        $availability = $this->availabilityManager->addStatic($availability, $formattedData['static']);

        $this->availabilityManager->relateToUser($availability, $user);

        return $availability;
    }

    public function update($data, User $user)
    {
        $this->delete($user);

        return $this->create($data, $user);
    }

    public function delete(User $user)
    {
        $availability = $this->get($user);

        return $this->availabilityManager->delete($availability->getId());
    }

}