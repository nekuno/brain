<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Tests\API\Profile;

class ProfileTest extends ProfileAPITest
{
    public function testProfile()
    {
        $this->assertProfileOptionsCommandDisplay();
        $this->assertGetProfileWithoutCredentialsResponse();
        $this->createAndLoginUserA();
        $this->assertGetNoneExistentProfileResponse();
        $this->createAndLoginUserB();
        $this->assertValidateProfileFormat();
        $this->assertCreateProfilesResponse();
        $this->assertGetExistentProfileResponse();
        $this->assertGetOwnProfileResponse();
        $this->assertEditOwnProfileResponse();
        $this->assertGetDeletedProfileResponse();
        $this->assertValidationErrorsResponse();
        $this->assertCreateComplexProfileResponse();
        $this->assertEditComplexProfileResponse();
        $this->assetsGetProfileMetadataResponse();
        $this->assetsGetProfileFiltersResponse();
        $this->assetsGetProfileTagsResponse();
    }

    protected function assertProfileOptionsCommandDisplay()
    {
        $display = $this->runProfileOptionsCommand();
        $this->assertRegExp('/\n[^0].*\snew\sprofile\soptions\screated\./', $display);
    }

    protected function assertGetProfileWithoutCredentialsResponse()
    {
        $response = $this->getOtherProfile(2);
        $this->assertStatusCode($response, 401, "Get Profile without credentials");
    }

    protected function assertGetNoneExistentProfileResponse()
    {
        $response = $this->getOtherProfile(2);
        $this->assertStatusCode($response, 404, "Get none-existent profile");
    }

    protected function assertValidateProfileFormat()
    {
        $profileData = $this->getProfileFixtures();
        $response = $this->validateProfile($profileData);
        $this->assertStatusCode($response, 200, "Bad response on validate profile");
    }

    protected function assertCreateProfilesResponse()
    {
        $userData = $this->getUserAFixtures();
        $this->loginUser($userData);
        $profileData = $this->getProfileFixtures();
        $response = $this->createProfile($profileData);
        $formattedResponse = $this->assertJsonResponse($response, 201, "Create ProfileA");
        $this->assertProfileFormat($formattedResponse, "Bad profile response on create profile A");

        $userData = $this->getUserBFixtures();
        $this->loginUser($userData);
        $response = $this->createProfile($profileData, 2);
        $formattedResponse = $this->assertJsonResponse($response, 201, "Create ProfileB");
        $this->assertProfileFormat($formattedResponse, "Bad profile response on create profile B");
    }

    protected function assertGetExistentProfileResponse()
    {
        $userData = $this->getUserAFixtures();
        $this->loginUser($userData);
        $response = $this->getOtherProfile(2);
        $formattedResponse = $this->assertJsonResponse($response, 200, "Get existent profile");
        $this->assertProfileFormat($formattedResponse, "Bad get other profile response");
    }

    protected function assertGetOwnProfileResponse()
    {
        $response = $this->getOwnProfile();
        $formattedResponse = $this->assertJsonResponse($response, 200, "Get own profile");
        $this->assertProfileFormat($formattedResponse, "Bad own profile response");
    }

    protected function assertEditOwnProfileResponse()
    {
        $profileData = $this->getEditedProfileFixtures();
        $response = $this->editProfile($profileData);
        $formattedResponse = $this->assertJsonResponse($response, 200, "Edit Profile");
        $this->assertEditedProfileFormat($formattedResponse, "Bad Profile response on edit profile A");

        $profileData = $this->getProfileFixtures();
        $response = $this->editProfile($profileData);
        $formattedResponse = $this->assertJsonResponse($response, 200, "Edit Profile");
        $this->assertProfileFormat($formattedResponse, "Bad Profile response on edit profile A");
    }

    protected function assertGetDeletedProfileResponse()
    {
        $response = $this->deleteProfile();
        $formattedResponse = $this->assertJsonResponse($response, 200, "Edit Profile");
        $this->assertProfileFormat($formattedResponse, "Bad Profile response on delete profile A");

        $response = $this->getOwnProfile();
        $this->assertStatusCode($response, 404, "Get deleted profile");
    }

    protected function assertValidationErrorsResponse()
    {
        $profileData = $this->getProfileWithMalformedBirthdayFixtures();
        $response = $this->createProfile($profileData);
        $formattedResponse = $this->assertJsonResponse($response, 422, "Create Profile with birthday error");
        $this->assertBirthdayValidationErrorFormat($formattedResponse);

        $profileData = $this->getProfileWithMalformedLocationFixtures();
        $response = $this->createProfile($profileData);
        $formattedResponse = $this->assertJsonResponse($response, 422, "Create Profile with location error");
        $this->assertLocationValidationErrorFormat($formattedResponse);

        $profileData = $this->getProfileWithMalformedGenderFixtures();
        $response = $this->createProfile($profileData);
        $formattedResponse = $this->assertJsonResponse($response, 422, "Create Profile with gender error");
        $this->assertGenderValidationErrorFormat($formattedResponse);

        $profileData = $this->getProfileWithMalformedOrientationFixtures();
        $response = $this->createProfile($profileData);
        $formattedResponse = $this->assertJsonResponse($response, 422, "Create Profile with orientation error");
        $this->assertOrientationValidationErrorFormat($formattedResponse);

        $profileData = $this->getProfileWithMalformedInterfaceLanguageFixtures();
        $response = $this->createProfile($profileData);
        $formattedResponse = $this->assertJsonResponse($response, 422, "Create Profile with interfaceLanguage error");
        $this->assertInterfaceLanguageValidationErrorFormat($formattedResponse);
    }

    protected function assertCreateComplexProfileResponse()
    {
        $profileData = $this->getComplexProfileFixtures();
        $response = $this->createProfile($profileData);
        $formattedResponse = $this->assertJsonResponse($response, 201, "Create Complex ProfileA");
        $this->assertComplexProfileFormat($formattedResponse, "Bad complex profile response on create profile A");
    }

    protected function assertEditComplexProfileResponse()
    {
        $profileData = $this->getEditedComplexProfileFixtures();
        $response = $this->editProfile($profileData);
        $formattedResponse = $this->assertJsonResponse($response, 200, "Edit Complex ProfileA");
        $this->assertEditedComplexProfileFormat($formattedResponse, "Bad complex profile response on edit profile A");
    }

    protected function assetsGetProfileMetadataResponse()
    {
        $response = $this->getProfileMetadata();
        $formattedResponse = $this->assertJsonResponse($response, 200, "Get Profile metadata");
        $this->assertGetProfileMetadataFormat($formattedResponse, "Bad response on get profile metadata");
    }

    protected function assetsGetProfileFiltersResponse()
    {
        $response = $this->getProfileFilters();
        $formattedResponse = $this->assertJsonResponse($response, 200, "Get Profile filters");
        $this->assertGetProfileFiltersFormat($formattedResponse, "Bad response on get profile filters");
    }

    protected function assetsGetProfileTagsResponse()
    {
        $response = $this->getProfileTags('allergy');
        $formattedResponse = $this->assertJsonResponse($response, 200, "Get Profile tags for allergy type");
        $this->assertArrayHasKey('items', $formattedResponse, "Profile tag has not items key");
        $this->assertArrayHasKey(0, $formattedResponse['items'], "Profile tag items has not 0 key");
        $this->assertGetProfileTagFormat($formattedResponse['items'][0], 'pollen', "Bad response on get profile tags for allergy type");

        $response = $this->getProfileTags('profession');
        $formattedResponse = $this->assertJsonResponse($response, 200, "Get Profile tags for profession type");
        $this->assertArrayHasKey('items', $formattedResponse, "Profile tag has not items key");
        $this->assertArrayHasKey(0, $formattedResponse['items'], "Profile tag items has not 0 key");
        $this->assertGetProfileTagFormat($formattedResponse['items'][0], 'programmer', "Bad response on get profile tags for profession type");
    }

    protected function assertProfileFormat($profile)
    {
        $this->assertArrayHasKey('birthday', $profile, "Profile has not birthday key");
        $this->assertArrayHasKey('location', $profile, "Profile has not location key");
        $this->assertArrayHasKey('locality', $profile['location'], "Profile has not location key");
        $this->assertArrayHasKey('address', $profile['location'], "Profile has not location key");
        $this->assertArrayHasKey('country', $profile['location'], "Profile has not location key");
        $this->assertArrayHasKey('longitude', $profile['location'], "Profile has not location key");
        $this->assertArrayHasKey('latitude', $profile['location'], "Profile has not location key");
        $this->assertArrayHasKey('zodiacSign', $profile, "Profile has not zodiacSign key");
        $this->assertArrayHasKey('gender', $profile, "Profile has not gender key");
        $this->assertArrayHasKey('orientation', $profile, "Profile has not orientation key");
        $this->assertArrayHasKey('interfaceLanguage', $profile, "Profile has not interfaceLanguage key");
        $this->assertEquals('1970-01-01', $profile['birthday'], "birthday is not 1970-01-01");
        $this->assertEquals('Madrid', $profile['location']['locality'], "locality is not Madrid");
        $this->assertEquals('Madrid', $profile['location']['address'], "address is not Madrid");
        $this->assertEquals('Spain', $profile['location']['country'], "country is not Spain");
        $this->assertEquals(-3.7037902, $profile['location']['longitude'], "longitude is not -3.7037902");
        $this->assertEquals(40.4167754, $profile['location']['latitude'], "latitude is not 40.4167754");
        $this->assertEquals('capricorn', $profile['zodiacSign'], "zodiacSign is not capricorn");
        $this->assertEquals('male', $profile['gender'], "gender is not male");
        $this->assertEquals('heterosexual', $profile['orientation'], "orientation is not heterosexual");
        $this->assertEquals('es', $profile['interfaceLanguage'], "interfaceLanguage is not es");
    }

    protected function assertEditedProfileFormat($profile)
    {
        $this->assertArrayHasKey('birthday', $profile, "Profile has not birthday key");
        $this->assertArrayHasKey('location', $profile, "Profile has not location key");
        $this->assertArrayHasKey('locality', $profile['location'], "Profile has not location key");
        $this->assertArrayHasKey('address', $profile['location'], "Profile has not location key");
        $this->assertArrayHasKey('country', $profile['location'], "Profile has not location key");
        $this->assertArrayHasKey('longitude', $profile['location'], "Profile has not location key");
        $this->assertArrayHasKey('latitude', $profile['location'], "Profile has not location key");
        $this->assertArrayHasKey('zodiacSign', $profile, "Profile has not zodiacSign key");
        $this->assertArrayHasKey('gender', $profile, "Profile has not gender key");
        $this->assertArrayHasKey('orientation', $profile, "Profile has not orientation key");
        $this->assertArrayHasKey('interfaceLanguage', $profile, "Profile has not interfaceLanguage key");
        $this->assertEquals('1981-11-10', $profile['birthday'], "birthday is not 1981-11-10");
        $this->assertEquals('Palma', $profile['location']['locality'], "locality is not Palma");
        $this->assertEquals('c/ Marquès de la Fontsanta, 36', $profile['location']['address'], "address is not c/ Marquès de la Fontsanta, 36");
        $this->assertEquals('Spain', $profile['location']['country'], "country is not Spain");
        $this->assertEquals(2.657593, $profile['location']['longitude'], "longitude is not -3.7037902");
        $this->assertEquals(39.577383, $profile['location']['latitude'], "latitude is not 40.4167754");
        $this->assertEquals('scorpio', $profile['zodiacSign'], "zodiacSign is not scorpio");
        $this->assertEquals('female', $profile['gender'], "gender is not female");
        $this->assertEquals('homosexual', $profile['orientation'], "orientation is not homosexual");
        $this->assertEquals('en', $profile['interfaceLanguage'], "interfaceLanguage is not en");
    }

    protected function assertComplexProfileFormat($profile)
    {
        $this->assertArrayHasKey('birthday', $profile, "Profile has not birthday key");
        $this->assertArrayHasKey('location', $profile, "Profile has not location key");
        $this->assertArrayHasKey('locality', $profile['location'], "Profile has not location key");
        $this->assertArrayHasKey('address', $profile['location'], "Profile has not location key");
        $this->assertArrayHasKey('country', $profile['location'], "Profile has not location key");
        $this->assertArrayHasKey('longitude', $profile['location'], "Profile has not location key");
        $this->assertArrayHasKey('latitude', $profile['location'], "Profile has not location key");
        $this->assertArrayHasKey('zodiacSign', $profile, "Profile has not zodiacSign key");
        $this->assertArrayHasKey('gender', $profile, "Profile has not gender key");
        $this->assertArrayHasKey('orientation', $profile, "Profile has not orientation key");
        $this->assertArrayHasKey('interfaceLanguage', $profile, "Profile has not interfaceLanguage key");
        $this->assertArrayHasKey('religion', $profile, "Profile has not religion key");
        $this->assertArrayHasKey('choice', $profile['religion'], "Profile has not religion choice key");
        $this->assertArrayHasKey('detail', $profile['religion'], "Profile has not religion detail key");
        $this->assertArrayHasKey('sons', $profile, "Profile has not sons key");
        $this->assertArrayHasKey('choice', $profile['sons'], "Profile has not sons choice key");
        $this->assertArrayHasKey('detail', $profile['sons'], "Profile has not sons detail key");
        $this->assertArrayHasKey('civilStatus', $profile, "Profile has not civilStatus key");
        $this->assertArrayHasKey('hairColor', $profile, "Profile has not hairColor key");
        $this->assertArrayHasKey('relationshipInterest', $profile, "Profile has not relationshipInterest key");
        $this->assertArrayHasKey('language', $profile, "Profile has not language key");
        $this->assertArrayHasKey(0, $profile['language'], "Profile has not language[0] key");
        $this->assertArrayHasKey(1, $profile['language'], "Profile has not language[1] key");
        $this->assertArrayHasKey('tag', $profile['language'][0], "Profile has not language[0]['tag'] key");
        $this->assertArrayHasKey('detail', $profile['language'][0], "Profile has not language[0]['detail'] key");
        $this->assertArrayHasKey('tag', $profile['language'][1], "Profile has not language[1]['tag'] key");
        $this->assertArrayHasKey('detail', $profile['language'][1], "Profile has not language[1]['detail'] key");
        $this->assertArrayHasKey('education', $profile, "Profile has not education key");
        $this->assertArrayHasKey(0, $profile['education'], "Profile has not education[0] key");
        $this->assertArrayHasKey('profession', $profile, "Profile has not profession key");
        $this->assertArrayHasKey(0, $profile['profession'], "Profile has not profession[0] key");
        $this->assertArrayHasKey('allergy', $profile, "Profile has not allergy key");
        $this->assertArrayHasKey(0, $profile['allergy'], "Profile has not allergy[0] key");
        $this->assertEquals('1970-01-01', $profile['birthday'], "birthday is not 1970-01-01");
        $this->assertEquals('Madrid', $profile['location']['locality'], "locality is not Madrid");
        $this->assertEquals('Madrid', $profile['location']['address'], "address is not Madrid");
        $this->assertEquals('Spain', $profile['location']['country'], "country is not Spain");
        $this->assertEquals(-3.7037902, $profile['location']['longitude'], "longitude is not -3.7037902");
        $this->assertEquals(40.4167754, $profile['location']['latitude'], "latitude is not 40.4167754");
        $this->assertEquals('capricorn', $profile['zodiacSign'], "zodiacSign is not capricorn");
        $this->assertEquals('male', $profile['gender'], "gender is not male");
        $this->assertEquals('heterosexual', $profile['orientation'], "orientation is not heterosexual");
        $this->assertEquals('es', $profile['interfaceLanguage'], "interfaceLanguage is not es");
        $this->assertEquals('atheism', $profile['religion']['choice'], "religion choice is not atheism");
        $this->assertEquals('not_important', $profile['religion']['detail'], "religion detail is not not_important");
        $this->assertEquals('no', $profile['sons']['choice'], "sons choice is not no");
        $this->assertEquals('not_want', $profile['sons']['detail'], "sons detail is not not_want");
        $this->assertEquals('married', $profile['civilStatus'], "civilStatus is not married");
        $this->assertEquals('brown', $profile['hairColor'], "hairColor is not brown");
        $this->assertEquals('friendship', $profile['relationshipInterest'], "relationshipInterest is not friendship");
        $this->assertRegExp('/^(English)|(French)$/', $profile['language'][0]['tag'], "language[0]['tag'] is not English or French");
        $this->assertRegExp('/^(full_professional)|(elementary)$/', $profile['language'][0]['detail'], "language[0]['detail'] is not full_professional or elementary");
        $this->assertRegExp('/^(English)|(French)$/', $profile['language'][1]['tag'], "language[1]['tag'] is not English or French");
        $this->assertRegExp('/^(full_professional)|(elementary)$/', $profile['language'][1]['detail'], "language[1]['detail'] is not full_professional or elementary");
        $this->assertEquals('bit', $profile['education'][0], "education[0] detail is not bit");
        $this->assertEquals('programmer', $profile['profession'][0], "profession[0] detail is not programmer");
        $this->assertEquals('pollen', $profile['allergy'][0], "allergy[0] detail is not pollen");
    }

    protected function assertEditedComplexProfileFormat($profile)
    {
        $this->assertArrayHasKey('birthday', $profile, "Profile has not birthday key");
        $this->assertArrayHasKey('location', $profile, "Profile has not location key");
        $this->assertArrayHasKey('locality', $profile['location'], "Profile has not location key");
        $this->assertArrayHasKey('address', $profile['location'], "Profile has not location key");
        $this->assertArrayHasKey('country', $profile['location'], "Profile has not location key");
        $this->assertArrayHasKey('longitude', $profile['location'], "Profile has not location key");
        $this->assertArrayHasKey('latitude', $profile['location'], "Profile has not location key");
        $this->assertArrayHasKey('zodiacSign', $profile, "Profile has not zodiacSign key");
        $this->assertArrayHasKey('gender', $profile, "Profile has not gender key");
        $this->assertArrayHasKey('orientation', $profile, "Profile has not orientation key");
        $this->assertArrayHasKey('interfaceLanguage', $profile, "Profile has not interfaceLanguage key");
        $this->assertArrayHasKey('religion', $profile, "Profile has not religion key");
        $this->assertArrayHasKey('choice', $profile['religion'], "Profile has not religion choice key");
        $this->assertArrayHasKey('detail', $profile['religion'], "Profile has not religion detail key");
        $this->assertArrayHasKey('sons', $profile, "Profile has not sons key");
        $this->assertArrayHasKey('choice', $profile['sons'], "Profile has not sons choice key");
        $this->assertArrayHasKey('detail', $profile['sons'], "Profile has not sons detail key");
        $this->assertArrayHasKey('civilStatus', $profile, "Profile has not civilStatus key");
        $this->assertArrayHasKey('hairColor', $profile, "Profile has not hairColor key");
        $this->assertArrayHasKey('relationshipInterest', $profile, "Profile has not relationshipInterest key");
        $this->assertArrayHasKey('language', $profile, "Profile has not language key");
        $this->assertArrayHasKey(0, $profile['language'], "Profile has not language[0] key");
        $this->assertArrayHasKey(1, $profile['language'], "Profile has not language[1] key");
        $this->assertArrayHasKey('tag', $profile['language'][0], "Profile has not language[0]['tag'] key");
        $this->assertArrayHasKey('detail', $profile['language'][0], "Profile has not language[0]['detail'] key");
        $this->assertArrayHasKey('tag', $profile['language'][1], "Profile has not language[1]['tag'] key");
        $this->assertArrayHasKey('detail', $profile['language'][1], "Profile has not language[1]['detail'] key");
        $this->assertArrayNotHasKey('education', $profile, "Profile has education key");
        $this->assertArrayHasKey('profession', $profile, "Profile has not profession key");
        $this->assertArrayHasKey(0, $profile['profession'], "Profile has not profession[0] key");
        $this->assertArrayNotHasKey('allergy', $profile, "Profile has allergy key");
        $this->assertEquals('1980-01-01', $profile['birthday'], "birthday is not 1980-01-01");
        $this->assertEquals('Madrid', $profile['location']['locality'], "locality is not Madrid");
        $this->assertEquals('Madrid', $profile['location']['address'], "address is not Madrid");
        $this->assertEquals('Spain', $profile['location']['country'], "country is not Spain");
        $this->assertEquals(-3.7037902, $profile['location']['longitude'], "longitude is not -3.7037902");
        $this->assertEquals(40.4167754, $profile['location']['latitude'], "latitude is not 40.4167754");
        $this->assertEquals('capricorn', $profile['zodiacSign'], "zodiacSign is not capricorn");
        $this->assertEquals('male', $profile['gender'], "gender is not male");
        $this->assertEquals('heterosexual', $profile['orientation'], "orientation is not heterosexual");
        $this->assertEquals('es', $profile['interfaceLanguage'], "interfaceLanguage is not es");
        $this->assertEquals('agnosticism', $profile['religion']['choice'], "religion choice is not agnosticism");
        $this->assertEquals('important', $profile['religion']['detail'], "religion detail is not important");
        $this->assertEquals('yes', $profile['sons']['choice'], "sons choice is not yes");
        $this->assertEquals('want', $profile['sons']['detail'], "sons detail is not want");
        $this->assertEquals('married', $profile['civilStatus'], "civilStatus is not married");
        $this->assertEquals('black', $profile['hairColor'], "hairColor is not black");
        $this->assertEquals('friendship', $profile['relationshipInterest'], "relationshipInterest is not friendship");
        $this->assertRegExp('/^(German)|(Japanese)$/', $profile['language'][0]['tag'], "language[0]['tag'] is not German or Japanese");
        $this->assertRegExp('/^(professional_working)|(native)$/', $profile['language'][0]['detail'], "language[0]['detail'] is not professional_working or native");
        $this->assertRegExp('/^(German)|(Japanese)$/', $profile['language'][1]['tag'], "language[1]['tag'] is not German or Japanese");
        $this->assertRegExp('/^(professional_working)|(native)$/', $profile['language'][1]['detail'], "language[1]['detail'] is not professional_working or native");
        $this->assertEquals('writer', $profile['profession'][0], "profession[0] detail is not writer");
    }

    protected function assertGetProfileMetadataFormat($metadata)
    {
        $this->assertArrayHasKey('birthday', $metadata, "Metadata has not birthday key");
        $this->assertArrayHasKey('location', $metadata, "Metadata has not location key");
        $this->assertArrayHasKey('gender', $metadata, "Metadata has not gender key");
        $this->assertArrayHasKey('orientation', $metadata, "Metadata has not orientation key");
        $this->assertArrayHasKey('interfaceLanguage', $metadata, "Metadata has not interfaceLanguage key");
    }

    protected function assertGetProfileFiltersFormat($metadata)
    {
        foreach ($metadata as $value) {
            $this->assertArrayHasKey('label', $value, "Filters has not label key");
            $this->assertArrayNotHasKey('labelFilter', $value, "Filters has labelFilter key");
            $this->assertArrayNotHasKey('filterable', $value, "Filters has filterable key");
        }
   }

    protected function assertGetProfileTagFormat($tag, $name)
    {
        $this->assertArrayHasKey('name', $tag, "Tag has not label name");
        $this->assertEquals($name, $tag['name'], $tag['name'] . " is not " . $name);
    }

    protected function assertBirthdayValidationErrorFormat($exception)
    {
        $this->assertValidationErrorFormat($exception);
        $this->assertArrayHasKey('birthday', $exception['validationErrors'], "Profile has not birthday key");
        $this->assertEquals('Invalid date format, valid format is "YYYY-MM-DD".', $exception['validationErrors']['birthday'][0], "birthday key is not Invalid date format, valid format is \"YYYY-MM-DD\".");
    }

    protected function assertLocationValidationErrorFormat($exception)
    {
        $this->assertValidationErrorFormat($exception);
        $this->assertArrayHasKey('location', $exception['validationErrors'], "Profile has not location key");
        $this->assertEquals('Longitude must be float', $exception['validationErrors']['location'][0], "location key is not Longitude not valid");
        $this->assertEquals('Locality required', $exception['validationErrors']['location'][1], "location key is not Locality required");
    }

    protected function assertGenderValidationErrorFormat($exception)
    {
        $this->assertValidationErrorFormat($exception);
        $this->assertArrayHasKey('gender', $exception['validationErrors'], "Profile has not gender key");
        $this->assertContains('Option with value "none-existent" is not valid, possible values are', $exception['validationErrors']['gender'][0], "gender key is not Option with value \"none-existent\" is not valid, possible values are");
    }

    protected function assertOrientationValidationErrorFormat($exception)
    {
        $this->assertValidationErrorFormat($exception);
        $this->assertArrayHasKey('orientation', $exception['validationErrors'], "Profile has not orientation key");
        $this->assertContains('Option with value "none-existent" is not valid, possible values are', $exception['validationErrors']['orientation'][0], "orientation key is not Option with value \"none-existent\" is not valid, possible values are");
    }

    protected function assertInterfaceLanguageValidationErrorFormat($exception)
    {
        $this->assertValidationErrorFormat($exception);
        $this->assertArrayHasKey('interfaceLanguage', $exception['validationErrors'], "Profile has not interfaceLanguage key");
        $this->assertContains('Option with value "none-existent" is not valid, possible values are', $exception['validationErrors']['interfaceLanguage'][0], "interfaceLanguage key is not Option with value \"none-existent\" is not valid, possible values are");
    }

    private function getProfileFixtures()
    {
        return array(
            "birthday" => "1970-01-01",
            "location" => array(
                "locality" => "Madrid",
                "address" => "Madrid",
                "country" => "Spain",
                "longitude" => -3.7037902,
                "latitude" => 40.4167754
            ),
            "gender" => "male",
            "orientation" => "heterosexual",
            "interfaceLanguage" => "es"
        );
    }

    private function getEditedProfileFixtures()
    {
        return array(
            "birthday" => "1981-11-10",
            "location" => array(
                "locality" => "Palma",
                "address" => "c/ Marquès de la Fontsanta, 36",
                "country" => "Spain",
                "longitude" => 2.657593,
                "latitude" => 39.577383
            ),
            "gender" => "female",
            "orientation" => "homosexual",
            "interfaceLanguage" => "en"
        );
    }

    private function getComplexProfileFixtures()
    {
        return array(
            "birthday" => "1970-01-01",
            "location" => array(
                "locality" => "Madrid",
                "address" => "Madrid",
                "country" => "Spain",
                "longitude" => -3.7037902,
                "latitude" => 40.4167754
            ),
            "religion" => array(
                "choice" => "atheism",
                "detail" => "not_important"
            ),
            "sons" => array(
                "choice" => "no",
                "detail" => "not_want"
            ),
            "interfaceLanguage" => "es",
            "gender" => "male",
            "civilStatus" => "married",
            "hairColor" => "brown",
            "relationshipInterest" => "friendship",
            "orientation" => "heterosexual",
            "language" => array(
                array(
                    "tag" => "English",
                    "choice" => "full_professional"
                ),
                array(
                    "tag" => "French",
                    "choice" => "elementary"
                )
            ),
            "education" => array(
                "bit"
            ),
            "profession" => array(
                "programmer"
            ),
            "allergy" => array(
                "pollen"
            )
        );
    }

    private function getEditedComplexProfileFixtures()
    {
        return array(
            "birthday" => "1980-01-01",
            "location" => array(
                "locality" => "Madrid",
                "address" => "Madrid",
                "country" => "Spain",
                "longitude" => -3.7037902,
                "latitude" => 40.4167754
            ),
            "religion" => array(
                "choice" => "agnosticism",
                "detail" => "important"
            ),
            "sons" => array(
                "choice" => "yes",
                "detail" => "want"
            ),
            "interfaceLanguage" => "es",
            "gender" => "male",
            "civilStatus" => "married",
            "hairColor" => "black",
            "relationshipInterest" => "friendship",
            "orientation" => "heterosexual",
            "language" => array(
                array(
                    "tag" => "German",
                    "choice" => "professional_working"
                ),
                array(
                    "tag" => "Japanese",
                    "choice" => "native"
                )
            ),
            "education" => null,
            "profession" => array(
                "writer"
            ),
            "allergy" => null,
        );
    }

    private function getProfileWithMalformedBirthdayFixtures()
    {
        return array(
            "birthday" => "190-01-01",
            "location" => array(
                "locality" => "Madrid",
                "address" => "Madrid",
                "country" => "Spain",
                "longitude" => -3.7037902,
                "latitude" => 40.4167754
            ),
            "gender" => "male",
            "orientation" => "heterosexual",
            "interfaceLanguage" => "es"
        );
    }

    private function getProfileWithMalformedLocationFixtures()
    {
        return array(
            "birthday" => "1970-01-01",
            "location" => array(
                "locality" => 1,
                "address" => "Madrid",
                "country" => "Spain",
                "longitude" => '-3.7037902',
                "latitude" => 40.4167754
            ),
            "gender" => "male",
            "orientation" => "heterosexual",
            "interfaceLanguage" => "es"
        );
    }

    private function getProfileWithMalformedGenderFixtures()
    {
        return array(
            "birthday" => "1970-01-01",
            "location" => array(
                "locality" => "Madrid",
                "address" => "Madrid",
                "country" => "Spain",
                "longitude" => -3.7037902,
                "latitude" => 40.4167754
            ),
            "gender" => "none-existent",
            "orientation" => "heterosexual",
            "interfaceLanguage" => "es"
        );
    }

    private function getProfileWithMalformedOrientationFixtures()
    {
        return array(
            "birthday" => "1970-01-01",
            "location" => array(
                "locality" => "Madrid",
                "address" => "Madrid",
                "country" => "Spain",
                "longitude" => -3.7037902,
                "latitude" => 40.4167754
            ),
            "gender" => "male",
            "orientation" => "none-existent",
            "interfaceLanguage" => "es"
        );
    }

    private function getProfileWithMalformedInterfaceLanguageFixtures()
    {
        return array(
            "birthday" => "1970-01-01",
            "location" => array(
                "locality" => "Madrid",
                "address" => "Madrid",
                "country" => "Spain",
                "longitude" => -3.7037902,
                "latitude" => 40.4167754
            ),
            "gender" => "male",
            "orientation" => "heterosexual",
            "interfaceLanguage" => "none-existent"
        );
    }

    private function runProfileOptionsCommand()
    {
        return $this->runCommand('neo4j:profile-options');
    }
}