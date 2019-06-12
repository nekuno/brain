<?php

namespace Model\Profile;

use Event\ProfileEvent;
use Model\Location\LocationManager;
use Model\Metadata\MetadataUtilities;
use Model\Metadata\ProfileMetadataManager;
use Model\Neo4j\GraphManager;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Exception\ValidationException;
use Model\Neo4j\QueryBuilder;
use Service\Validator\ProfileValidator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProfileManager
{
    const MAX_TAGS_AND_CHOICE_LENGTH = 15;
    protected $gm;
    protected $profileOptionManager;
    protected $profileTagManager;
    protected $profileMetadataManager;
    protected $locationManager;
    protected $metadataUtilities;
    protected $dispatcher;
    protected $validator;

    public function __construct(
        GraphManager $gm,
        ProfileMetadataManager $profileMetadataManager,
        ProfileOptionManager $profileOptionManager,
        ProfileTagManager $profileTagModel,
        LocationManager $locationManager,
        MetadataUtilities $metadataUtilities,
        EventDispatcherInterface $dispatcher,
        ProfileValidator $validator
    ) {
        $this->gm = $gm;
        $this->profileMetadataManager = $profileMetadataManager;
        $this->profileOptionManager = $profileOptionManager;
        $this->profileTagManager = $profileTagModel;
        $this->locationManager = $locationManager;
        $this->metadataUtilities = $metadataUtilities;
        $this->dispatcher = $dispatcher;
        $this->validator = $validator;
    }

    /**
     * @param int $id
     * @return Profile
     * @throws NotFoundHttpException
     */
    public function getById($id)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(user:User)<-[:PROFILE_OF]-(profile:Profile)')
            ->where('user.qnoow_id = { id }')
            ->setParameter('id', (integer)$id)
            ->optionalMatch('(profile)<-[optionOf:OPTION_OF]-(option:ProfileOption)')
            ->with('profile', 'collect(distinct {option: option, detail: (CASE WHEN EXISTS(optionOf.detail) THEN optionOf.detail ELSE null END)}) AS options')
            ->optionalMatch('(profile)<-[tagged:TAGGED]-(tag:ProfileTag)-[:TEXT_OF]-(text:TextLanguage)')
            ->returns('profile', 'options', 'collect(distinct {tag: tag, tagged: tagged, text: text.canonical, locale: text.locale}) AS tags')
            ->limit(1);

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        if (count($result) < 1) {
            throw new NotFoundHttpException('Profile not found');
        }

        /* @var $row Row */
        $row = $result->current();

        return $this->build($row);
    }

    /**
     * @param string $slug
     * @return Profile
     * @throws NotFoundHttpException
     */
    public function getBySlug($slug)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(user:User)<-[:PROFILE_OF]-(profile:Profile)')
            ->where('user.slug = { slug }')
            ->setParameter('slug', $slug)
            ->optionalMatch('(profile)<-[optionOf:OPTION_OF]-(option:ProfileOption)')
            ->with('profile', 'collect(distinct {option: option, detail: (CASE WHEN EXISTS(optionOf.detail) THEN optionOf.detail ELSE null END)}) AS options')
            ->optionalMatch('(profile)<-[tagged:TAGGED]-(tag:ProfileTag)-[:TEXT_OF]-(text:TextLanguage)')
            ->returns('profile', 'options', 'collect(distinct {tag: tag, tagged: tagged, text: text.canonical, locale: text.locale}) AS tags')
            ->limit(1);

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        if (count($result) < 1) {
            throw new NotFoundHttpException('Profile not found');
        }

        /* @var $row Row */
        $row = $result->current();

        return $this->build($row);
    }

    /**
     * @param $id
     * @param array $data
     * @return Profile
     * @throws NotFoundHttpException|MethodNotAllowedHttpException
     */
    public function create($id, array $data)
    {
        $this->validateOnCreate($data, $id);

        list($userNode, $profileNode) = $this->getUserAndProfileNodesById($id);

        if (!($userNode instanceof Node)) {
            throw new NotFoundHttpException('User not found');
        }

        if ($profileNode instanceof Node) {
            throw new MethodNotAllowedHttpException(array('PUT'), 'Profile already exists');
        }

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(user:User)')
            ->where('user.qnoow_id = { id }')
            ->setParameter('id', (int)$id)
            ->merge('(profile:Profile)-[po:PROFILE_OF]->(user)');

        $qb->getQuery()->getResultSet();

        $this->saveProfileData($id, $data);

        $profile = $this->getById($id);
        $this->dispatcher->dispatch(\AppEvents::PROFILE_CREATED, (new ProfileEvent($profile, $id)));

        return $profile;
    }

    /**
     * @param integer $id
     * @param array $data
     * @return Profile
     * @throws ValidationException|NotFoundHttpException
     */
    public function update($id, array $data)
    {
        $this->validateOnUpdate($data, $id);

        list($userNode, $profileNode) = $this->getUserAndProfileNodesById($id);

        if (!($userNode instanceof Node)) {
            throw new NotFoundHttpException('User not found');
        }

        if (!($profileNode instanceof Node)) {
            throw new NotFoundHttpException('Profile not found');
        }

        $this->saveProfileData($id, $data);

        return $this->getById($id);
    }

    /**
     * @param $id
     */
    public function remove($id)
    {
        $this->validateOnRemove($id);

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(user:User)<-[:PROFILE_OF]-(profile:Profile)')
            ->where('user.qnoow_id = { id }')
            ->setParameter('id', (integer)$id)
            ->optionalMatch('(profile)-[r]-()')
            ->delete('r, profile');

        $query = $qb->getQuery();

        $query->getResultSet();
    }

    /**
     * @param array $data
     * @param $userId
     */
    public function validateOnCreate(array $data, $userId = null)
    {
        $data['userId'] = $userId;
        $data['choices'] = $this->profileOptionManager->getOptions();

        $this->validator->validateOnCreate($data);
    }

    public function validateOnUpdate(array $data, $userId)
    {
        $data['userId'] = $userId;
        $data['choices'] = $this->profileOptionManager->getOptions();
        $this->validator->validateOnUpdate($data);
    }

    public function validateOnRemove($userId)
    {
        $data = array('userId' => $userId);
        $this->validator->validateOnDelete($data);
    }

    public function build(Row $row)
    {
        /* @var $node Node */
        $node = $row->offsetGet('profile');
        $profileId = $node->getId();

        $interfaceLocale = $this->getInterfaceLocaleByProfileId($profileId);
        $metadata = $this->profileMetadataManager->getMetadata($interfaceLocale);
        $profile = new Profile($metadata);

        $profileProperties = $node->getProperties();
        foreach ($profileProperties as $field => $property)
        {
            $profile->set($field, $property);
        }


        $profile->set('location', $this->getLocation($profileId));
        $profile->set('travelling', $this->getTravelling($profileId));
        $multipleFields = $this->getMultipleFields($profileId);
        foreach ($multipleFields as $field => $values)
        {
            $profile->set($field, $values);
        }

        $profileOptions = $this->profileOptionManager->buildOptions($row->offsetGet('options'));
        foreach ($profileOptions as $field => $profileOption)
        {
            $profile->set($field, $profileOption);
        }

        $profileTags = $this->profileTagManager->buildTags($row, $interfaceLocale);
        foreach ($profileTags as $field => $profileTag)
        {
            $profile->set($field, $profileTag);
        }

        return $profile;
    }

    protected function getLocation($profileId)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(profile:Profile)')
            ->where("id(profile) = $profileId")
            ->with('profile')
            ->limit(1);

        $qb->match('(profile)-[:LOCATION]-(location:Location)')
            ->returns('location');

        $result = $qb->getQuery()->getResultSet();

        if ($result->count() == 0) {
            return array();
        }

        $location = $this->locationManager->buildLocation($result->current());

        return $location;
    }

    protected function getTravelling($profileId)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(profile:Profile)')
            ->where("id(profile) = $profileId")
            ->with('profile')
            ->limit(1);

        $qb->match('(profile)-[:TRAVELLING]-(location:Location)')
            ->returns('location');

        $result = $qb->getQuery()->getResultSet();

        if ($result->count() == 0) {
            return array();
        }

        $locations = array();
        foreach ($result as $row) {
            $locations[] = $this->locationManager->buildLocation($row);
        }

        return $locations;
    }

    protected function getMultipleFields($profileId)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(profile:Profile)')
            ->where('id(profile) = { profileId }')
            ->setParameter('profileId', (int)$profileId)
            ->with('profile');

        $qb->match('(profile)<-[:MULTIPLE_FIELDS_OF]-(multiple)')
            ->optionalMatch('(multiple)<-[optionOf:OPTION_OF]-(option:ProfileOption)')
            ->with('multiple', 'collect(distinct {option: option, detail: (CASE WHEN EXISTS(optionOf.detail) THEN optionOf.detail ELSE null END)}) AS options')
            ->optionalMatch('(multiple)<-[tagged:TAGGED]-(tag:ProfileTag)-[:TEXT_OF]-(text:TextLanguage)')
            ->returns('multiple', 'options', 'collect(distinct {tag: tag, tagged: tagged, text: text.canonical, locale: text.locale}) AS tags', 'head(labels(multiple)) AS label');

        $result = $qb->getQuery()->getResultSet();

        $interfaceLocale = $this->getInterfaceLocaleByProfileId($profileId);
        $multiples = array();
        //TODO: Change to multiples = array(field=> Profile())
        foreach ($result as $row)
        {
            /** @var Node $multipleNode */
            $multipleNode = $row->offsetGet('multiple');
            $multiple = $multipleNode->getProperties();

            $multiple += $this->profileOptionManager->buildOptions($row->offsetGet('options'));
            $multiple += $this->profileTagManager->buildTags($row, $interfaceLocale);
            //if Location or Travelling is needed, remove :Profile requirement from methods or move this to own manager
            $label = $row->offsetGet('label');
            $field = $this->metadataUtilities->labelToType($label);
            $multiples[$field][] = $multiple;
        }

        return $multiples;
    }

    protected function getUserAndProfileNodesById($id)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(user:User)')
            ->where('user.qnoow_id = { id }')
            ->optionalMatch('(user)<-[:PROFILE_OF]-(profile:Profile)')
            ->setParameter('id', $id)
            ->returns('user', 'profile')
            ->limit(1);

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        if (count($result) < 1) {
            return array(null, null);
        }

        /** @var Row $row */
        $row = $result->current();
        $userNode = $row->offsetGet('user');
        $profileNode = $row->offsetGet('profile');

        return array($userNode, $profileNode);
    }

    protected function saveProfileData($userId, array $data)
    {
        $metadata = $this->profileMetadataManager->getMetadata();
        $currentOptions = $this->profileOptionManager->getUserProfileOptions($userId);
        $this->profileTagManager->deleteAllTagRelationships($userId);
        $this->deleteAllMultipleFields($userId);

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(profile:Profile)-[:PROFILE_OF]->(u:User)')
            ->where('u.qnoow_id = { id }')
            ->setParameter('id', (int)$userId)
            ->with('profile');
        $this->saveDataByType($userId, $data, $metadata, $qb, $currentOptions);

        $qb->optionalMatch('(profile)<-[optionOf:OPTION_OF]-(option:ProfileOption)')
            ->optionalMatch('(profile)<-[tagged:TAGGED]-(tag:ProfileTag)')
            ->returns('profile', 'collect(distinct {option: option, detail: (CASE WHEN EXISTS(optionOf.detail) THEN optionOf.detail ELSE null END)}) AS options', 'collect(distinct {tag: tag, tagged: tagged}) AS tags')
            ->limit(1);

        $query = $qb->getQuery();

        $query->getResultSet();
    }

    public function getIndustryIdFromDescription($description)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(industry:ProfileOption:Industry)')
            ->where('industry.name_en = {description}')
            ->setParameter('description', $description)
            ->returns('industry.id as id')
            ->limit(1);

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        /** @var Row $row */
        $row = $result->current();
        if ($row->offsetExists('id')) {
            return $row->offsetGet('id');
        }

        throw new NotFoundHttpException(sprintf("Description %s not found", $description));
    }

    public function getInterfaceLocale($userId)
    {
        //while application is only in Spanish
        return 'es';

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(profile:Profile)-[:PROFILE_OF]->(u:User)')
            ->where('u.qnoow_id = { id }')
            ->setParameter('id', (int)$userId)
            ->with('profile');

        $qb->match('(profile)-[:OPTION_OF]-(i:InterfaceLanguage)')
            ->returns('i.id AS locale');

        $result = $qb->getQuery()->getResultSet();

        if ($result->count() == 0) {
            return 'en';
        }

        return $result->current()->offsetGet('locale');
    }

    public function getInterfaceLocaleByProfileId($profileId)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(profile)')
            ->where('id(profile) = { id }')
            ->setParameter('id', (int)$profileId)
            ->with('profile');

        $qb->match('(profile)-[:OPTION_OF]-(i:InterfaceLanguage)')
            ->returns('i.id AS locale');

        $result = $qb->getQuery()->getResultSet();

        if ($result->count() == 0) {
            return 'en';
        }

        return $result->current()->offsetGet('locale');
    }

    /**
     * @param $userId
     * @param array $data
     * @param array $metadata
     * @param QueryBuilder $qb
     * @param array $currentOptions
     * @param null $forceProfileId
     * @throws \Exception
     */
    protected function saveDataByType($userId, array $data, array $metadata, QueryBuilder $qb, array $currentOptions, $forceProfileId = null)
    {
        foreach ($data as $fieldName => $fieldValue) {
            if (isset($metadata[$fieldName])) {

                $fieldType = $metadata[$fieldName]['type'];
                $editable = isset($metadata[$fieldName]['editable']) ? $metadata[$fieldName]['editable'] === true : true;

                if (!$editable) {
                    continue;
                }

                switch ($fieldType) {
                    case 'text':
                    case 'textarea':
                    case 'date':
                    case 'boolean':
                    case 'integer':
                        $qb->set('profile.' . $fieldName . ' = { ' . $fieldName . ' }')
                            ->setParameter($fieldName, $fieldValue)
                            ->with('profile');
                        break;
                    case 'birthday':
                        $zodiacSign = $this->metadataUtilities->getZodiacSignFromDate($fieldValue);
                        if (isset($currentOptions['zodiacSign'])) {
                            $qb->match('(profile)<-[zodiacSignRel:OPTION_OF]-(zs:ZodiacSign)')
                                ->delete('zodiacSignRel')
                                ->with('profile');
                        }
                        if (!is_null($zodiacSign)) {
                            $qb->match('(newZs:ZodiacSign {id: { zodiacSign }})')
                                ->merge('(profile)<-[:OPTION_OF]-(newZs)')
                                ->setParameter('zodiacSign', $zodiacSign)
                                ->with('profile');
                        }

                        $qb->set('profile.' . $fieldName . ' = { birthday }')
                            ->setParameter('birthday', $fieldValue)
                            ->with('profile');
                        break;
                    case 'location':
                        $qb->optionalMatch('(profile)-[:LOCATION]->(oldLocation:Location)')
                            ->detachDelete('oldLocation')
                            ->with('profile');

                        $location = $this->locationManager->createLocation($fieldValue);
                        $locationId = $location->getId();
                        $qb->match('(location:Location)')
                            ->where("id(location) = $locationId")
                            ->createUnique('(profile)-[:LOCATION]->(location)')
                            ->with('profile');
                        break;
                    case 'multiple_locations':
                        $qb->optionalMatch('(profile)-[:TRAVELLING]->(oldTravelling:Location)')
                            ->detachDelete('oldTravelling')
                            ->with('profile');

                        if (is_array($fieldValue)) {
                            foreach ($fieldValue as $index => $locationData) {
                                $location = $this->locationManager->createLocation($locationData);
                                $locationId = $location->getId();
                                $qb->match('(location:Location)')
                                    ->where("id(location) = $locationId")
                                    ->createUnique('(profile)-[:TRAVELLING]->(location)')
                                    ->with('profile');
                            }
                        }

                        break;

                    case 'choice':
                        if (isset($currentOptions[$fieldName])) {
                            $qb->optionalMatch('(profile)<-[optionRel:OPTION_OF]-(:' . $this->metadataUtilities->typeToLabel($fieldName) . ')')
                                ->delete('optionRel')
                                ->with('profile');
                        }
                        if (!is_null($fieldValue)) {
                            $qb->match('(option:' . $this->metadataUtilities->typeToLabel($fieldName) . ' {id: { ' . $fieldName . ' }})')
                                ->merge('(profile)<-[:OPTION_OF]-(option)')
                                ->setParameter($fieldName, $fieldValue)
                                ->with('profile');
                        }
                        break;
                    case 'double_choice':
                        $qbDoubleChoice = $this->getQbWithProfile($userId, $forceProfileId);

                        if (isset($currentOptions[$fieldName])) {
                            $qbDoubleChoice->optionalMatch('(profile)<-[doubleChoiceOptionRel:OPTION_OF]-(:' . $this->metadataUtilities->typeToLabel($fieldName) . ')')
                                ->delete('doubleChoiceOptionRel')
                                ->with('profile');
                        }
                        if (isset($fieldValue['choice'])) {
                            $detail = !is_null($fieldValue['detail']) ? $fieldValue['detail'] : '';
                            $qbDoubleChoice->match('(option:' . $this->metadataUtilities->typeToLabel($fieldName) . ' {id: { ' . $fieldName . ' }})')
                                ->merge('(profile)<-[:OPTION_OF {detail: {' . $fieldName . '_detail}}]-(option)')
                                ->setParameter($fieldName, $fieldValue['choice'])
                                ->setParameter($fieldName . '_detail', $detail);
                        }
                        $qbDoubleChoice->returns('profile');

                        $query = $qbDoubleChoice->getQuery();
                        $query->getResultSet();

                        break;
                    case 'tags_and_choice':
                        if (is_array($fieldValue)) {
                            $tagLabel = $this->metadataUtilities->typeToLabel($fieldName);
                            $interfaceLanguage = $this->getInterfaceLocale($userId);
                            if ($forceProfileId)
                            {
                                $this->profileTagManager->setTagsAndChoiceToNode($forceProfileId, $interfaceLanguage, $fieldName, $tagLabel, $fieldValue);
                            } else {
                                $this->profileTagManager->setTagsAndChoice($userId, $interfaceLanguage, $fieldName, $tagLabel, $fieldValue);
                            }
                        }

                        break;
                    case 'multiple_choices':
                        $qbMultipleChoices = $this->getQbWithProfile($userId, $forceProfileId);

                        if (isset($currentOptions[$fieldName])) {
                            $qbMultipleChoices->optionalMatch('(profile)<-[optionRel:OPTION_OF]-(:' . $this->metadataUtilities->typeToLabel($fieldName) . ')')
                                ->delete('optionRel')
                                ->with('profile');
                        }
                        if (is_array($fieldValue)) {
                            foreach ($fieldValue as $index => $value) {
                                $qbMultipleChoices->match('(option:' . $this->metadataUtilities->typeToLabel($fieldName) . ' {id: { ' . $index . ' }})')
                                    ->merge('(profile)<-[:OPTION_OF]-(option)')
                                    ->setParameter($index, $value)
                                    ->with('profile');
                            }
                        }
                        $qbMultipleChoices->returns('profile');

                        $query = $qbMultipleChoices->getQuery();
                        $query->getResultSet();
                        break;
                    case 'tags':
                        $tagLabel = $this->metadataUtilities->typeToLabel($fieldName);

                        if (is_array($fieldValue) && !empty($fieldValue)) {
                            $interfaceLanguage = $this->getInterfaceLocale($userId);
                            if ($forceProfileId)
                            {
                                $this->profileTagManager->addTagsToNode($forceProfileId, $interfaceLanguage, $tagLabel, $fieldValue);
                            } else {
                                $this->profileTagManager->addTags($userId, $interfaceLanguage, $tagLabel, $fieldValue);
                            }
                        }

                        break;
                    case 'multiple_fields':
                        $fieldLabel = $this->metadataUtilities->typeToLabel($fieldName);
                        $fieldValue = $fieldValue ?: array();
                        foreach ($fieldValue as $index => $multiValue){
                            $multiQb = $this->getQbWithProfile($userId, $forceProfileId);
                            $multiQb->create("(profile)<-[:MULTIPLE_FIELDS_OF]-(profileMulti:$fieldLabel)")
                                ->returns('id(profileMulti) AS id');
                            $result = $multiQb->getQuery()->getResultSet();
                            $profileMultiId = $result->current()->offsetGet('id');

                            $multiQb = $this->getQbWithProfile($userId, $profileMultiId);
                            $internalMetadata = $metadata[$fieldName]['metadata'];
                            $this->saveDataByType($userId, $multiValue, $internalMetadata, $multiQb, $currentOptions, $profileMultiId);

                            $multiQb->returns('profile');
                            $multiQb->getQuery()->getResultSet();
                        }

                        break;
                    default:
                        break;
                }
            }
        }
    }

    protected function getQbWithProfile($userId, $forceProfileId)
    {
        $qb = $this->gm->createQueryBuilder();

        if ($forceProfileId)
        {
            $qb->match('(profile)')
                ->where('id(profile) = { id }')
                ->setParameter('id', (int)$forceProfileId)
                ->with('profile');
        } else {
            $qb->match('(profile:Profile)-[:PROFILE_OF]->(u:User)')
                ->where('u.qnoow_id = { id }')
                ->setParameter('id', (int)$userId)
                ->with('profile');
        }

        return $qb;
    }

    protected function deleteAllMultipleFields($userId)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(profile:Profile)-[:PROFILE_OF]->(u:User)')
            ->where('u.qnoow_id = { id }')
            ->setParameter('id', (int)$userId)
            ->with('profile');

        $qb->match('(profile)<-[:MULTIPLE_FIELDS_OF]-(multi)')
            ->detachDelete('multi');

        $qb->getQuery()->getResultSet();
    }
}