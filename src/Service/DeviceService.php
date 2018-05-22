<?php

namespace Service;

use GuzzleHttp\Client;
use Model\Device\Device;
use Model\Device\DeviceManager;
use Model\Profile\ProfileManager;
use Symfony\Component\Translation\TranslatorInterface;

class DeviceService
{
    const MESSAGE_CATEGORY = 'message';
    const PROCESS_FINISH_CATEGORY = 'process_finish';
    const BOTH_USER_LIKED_CATEGORY = 'both_user_liked';
    const GENERIC_CATEGORY = 'generic';

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var DeviceManager
     */
    protected $dm;

    /**
     * @var ProfileManager
     */
    protected $pm;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    protected $fireBaseUrl;
    protected $fireBaseApiKey;
    protected $serverPublicKey;
    protected $serverPrivateKey;

    public function __construct(Client $client, DeviceManager $dm, ProfileManager $pm, TranslatorInterface $translator, $fireBaseUrl, $fireBaseApiKey, $serverPublicKey, $serverPrivateKey)
    {
        $this->client = $client;
        $this->dm = $dm;
        $this->pm = $pm;
        $this->translator = $translator;
        $this->fireBaseUrl = $fireBaseUrl;
        $this->fireBaseApiKey = $fireBaseApiKey;
        $this->serverPublicKey = $serverPublicKey;
        $this->serverPrivateKey = $serverPrivateKey;
    }

    public function pushMessage(array $data, $userId, $category = self::GENERIC_CATEGORY)
    {
        $this->validatePushData($category, $data);
        $devices = $this->dm->getAll($userId);
        $interfaceLanguage = $this->pm->getInterfaceLocale($userId);
        $this->translator->setLocale($interfaceLanguage);

        $registrationIds = array();
        /** @var Device $device */
        foreach ($devices as $device) {
            $registrationIds[] = $device->getRegistrationId();
        }

        $payloadData = $this->getPayloadData($category, $data);

        if (empty($devices)) {
            return null;
        }
        $payload = array(
            'notification' => array(
                'title' => $payloadData['title'],
                'body' => $payloadData['body'],
            ),
            'data' => $payloadData,
            'collapse_key' => $this->getCollapseKey($category, $data),
            'registration_ids' => $registrationIds,
        );

        return $this->client->post($this->fireBaseUrl, array(
            'json' => $payload,
            'headers' => array(
                'Authorization' => 'key=' . $this->fireBaseApiKey,
                'Content-Type' => 'application/json',
            ),
        ))->getBody()->getContents();
    }

    // This is for delivering only one notification per collapse key if delayed
    private function getCollapseKey($category, $data)
    {
        switch ($category) {
            case self::MESSAGE_CATEGORY:
                return 'Conversation with ' . $data['username'];
            case self::PROCESS_FINISH_CATEGORY:
                return 'Process finished for resource ' . $data['resource'];
            case self::BOTH_USER_LIKED_CATEGORY:
                return 'Both user liked. You and ' . $data['username'];
            default:
                return 'Generic notification with title ' . $data['title'];
        }
    }

    private function getPayloadData($category, $data)
    {
        switch ($category) {
            case self::MESSAGE_CATEGORY:
                return array(
                    'title' => $this->translator->trans('push_notifications.message.title', array('%username%' => $data['username'])),
                    'body' => $data['body'],
                    'image' => $data['image'],
                    'image-type' => "circle",
                    'on_click_path' => "/conversations/" . $data['slug'],
                    'notId' => rand(1, 50000),
                    'force_show' => 0,
                );
            case self::PROCESS_FINISH_CATEGORY:
                return array(
                    'title' => $this->translator->trans('push_notifications.process_finish.title'),
                    'body' => $this->translator->trans('push_notifications.process_finish.body', array('%resource%' => $data['resource'])),
                    'on_click_path' => "/social-networks",
                    'notId' => rand(1, 50000),
                    'force_show' => 1,
                );
            case self::BOTH_USER_LIKED_CATEGORY:
                return array(
                    'title' => $this->translator->trans('push_notifications.both_user_liked.title'),
                    'body' => $this->translator->trans('push_notifications.both_user_liked.body', array('%username%' => $data['username'])),
                    'image' => $data['image'],
                    'image-type' => "circle",
                    'on_click_path' => "/p/" . $data['slug'],
                    'notId' => rand(1, 50000),
                    'force_show' => 1,
                );
            default:
                return array(
                    'title' => $data['title'],
                    'body' => $data['body'],
                    'image' => isset($data['image']) ? $data['image'] : null,
                    'on_click_path' => isset($data['on_click_path']) ? $data['on_click_path'] : null,
                    'notId' => rand(1, 50000),
                    'force_show' => 1,
                );
        }
    }

    private function validatePushData($category, $data)
    {
        if (!in_array($category, $this->getValidCategories())) {
            throw new \Exception(sprintf("Category %s does not exist", $category));
        }

        switch ($category) {
            case self::MESSAGE_CATEGORY:
                if (!isset($data['username']) || !isset($data['image']) || !isset($data['body']) || !isset($data['slug'])) {
                    throw new \Exception("Username, image, body or slug are not defined for message category");
                }
                break;
            case self::PROCESS_FINISH_CATEGORY:
                if (!isset($data['resource'])) {
                    throw new \Exception("Resource is not defined for process finish category");
                }
                break;
            case self::BOTH_USER_LIKED_CATEGORY:
                if (!isset($data['username']) || !isset($data['image']) || !isset($data['slug'])) {
                    throw new \Exception("Username, image or slug is not defined for both user liked category");
                }
                break;
            case self::GENERIC_CATEGORY:
                if (!isset($data['title']) || !isset($data['body'])) {
                    throw new \Exception("Title or body is not defined for generic category");
                }
                break;
        }
    }

    private function getValidCategories()
    {
        return array(
            self::MESSAGE_CATEGORY,
            self::PROCESS_FINISH_CATEGORY,
            self::BOTH_USER_LIKED_CATEGORY,
            self::GENERIC_CATEGORY,
        );
    }
}