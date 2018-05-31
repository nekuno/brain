<?php

namespace Tests\API\Profile;

class ProfileTest extends ProfileAPITest
{
    public function testProfile()
    {
        $this->assertGetProfileWithoutCredentialsResponse();
        $this->assertGetCategories();
        $this->assertGetNoneExistentProfileResponse();
        $this->assertGetExistentProfileResponse();
        $this->assertGetOwnProfileResponse();
        $this->assertEditOwnProfileResponse();
        $this->assertValidationErrorsResponse();
        $this->assertEditComplexProfileResponse();
        $this->assetsGetProfileMetadataResponse();
        $this->assetsGetProfileFiltersResponse();
        $this->assetsGetProfileTagsResponse();
    }

    protected function assertGetProfileWithoutCredentialsResponse()
    {
        $response = $this->getOtherProfile(self::OTHER_USER_SLUG, null);
        $this->assertStatusCode($response, 401, "Get Profile without credentials");
    }

    protected function assertGetCategories()
    {
        $response = $this->getCategories();
        $formattedResponse = $this->assertJsonResponse($response, 200, "Get categories status");
        $this->assertArrayOfType('array', $formattedResponse, 'Categories is array of array');
        
        $this->arrayHasKey('profile')->evaluate($formattedResponse, 'Categories has profile key');
        $this->assertArrayOfType('array', $formattedResponse['profile'], 'Categories profile is array of array');
        foreach ($formattedResponse['profile'] as $item) {
            $this->assertHasLocaleLabel($item, 'Categories profile field');
        }

        $this->arrayHasKey('filters')->evaluate($formattedResponse, 'Categories has filter key');
        $this->assertArrayOfType('array', $formattedResponse['filters'], 'Categories filter is array of array');
        foreach ($formattedResponse['filters'] as $item) {
            $this->assertHasLocaleLabel($item, 'Categories filters field');
        }
    }

    protected function assertGetNoneExistentProfileResponse()
    {
        $response = $this->getOtherProfile(self::UNDEFINED_USER_SLUG);
        $this->assertStatusCode($response, 404, "Get non-existent profile");
    }

    protected function assertGetExistentProfileResponse()
    {
        $response = $this->getOtherProfile(self::OTHER_USER_SLUG);
        $formattedResponse = $this->assertJsonResponse($response, 200, "Get existent profile");
        $this->assertProfileFormat($formattedResponse);
    }

    protected function assertGetOwnProfileResponse()
    {
        $response = $this->getOwnProfile();
        $formattedResponse = $this->assertJsonResponse($response, 200, "Get own profile");
        $this->assertProfileFormat($formattedResponse);
    }

    protected function assertEditOwnProfileResponse()
    {
        $profileData = $this->getEditedProfileFixtures();
        $response = $this->editProfile($profileData);
        $formattedResponse = $this->assertJsonResponse($response, 200, "Edit Profile");
        $this->assertEditedProfileFormat($formattedResponse);

        $profileData = $this->getProfileFixtures();
        $response = $this->editProfile($profileData);
        $formattedResponse = $this->assertJsonResponse($response, 200, "Edit Profile");
        $this->assertProfileFormat($formattedResponse);
    }

    protected function assertValidationErrorsResponse()
    {
        $profileData = $this->getProfileWithMalformedBirthdayFixtures();
        $response = $this->editProfile($profileData);
        $formattedResponse = $this->assertJsonResponse($response, 422, "Create Profile with birthday error");
        $this->assertBirthdayValidationErrorFormat($formattedResponse);

        $profileData = $this->getProfileWithMalformedLocationFixtures();
        $response = $this->editProfile($profileData);
        $formattedResponse = $this->assertJsonResponse($response, 422, "Create Profile with location error");
        $this->assertLocationValidationErrorFormat($formattedResponse);

        $profileData = $this->getProfileWithMalformedGenderFixtures();
        $response = $this->editProfile($profileData);
        $formattedResponse = $this->assertJsonResponse($response, 422, "Create Profile with gender error");
        $this->assertGenderValidationErrorFormat($formattedResponse);

        $profileData = $this->getProfileWithMalformedOrientationFixtures();
        $response = $this->editProfile($profileData);
        $formattedResponse = $this->assertJsonResponse($response, 422, "Create Profile with orientation error");
        $this->assertOrientationValidationErrorFormat($formattedResponse);

        $profileData = $this->getProfileWithMalformedInterfaceLanguageFixtures();
        $response = $this->editProfile($profileData);
        $formattedResponse = $this->assertJsonResponse($response, 422, "Create Profile with interfaceLanguage error");
        $this->assertInterfaceLanguageValidationErrorFormat($formattedResponse);
    }

    protected function assertEditComplexProfileResponse()
    {
        $profileData = $this->getEditedComplexProfileFixtures();
        $response = $this->editProfile($profileData);
        $formattedResponse = $this->assertJsonResponse($response, 200, "Edit Complex ProfileA");
        $this->assertEditedComplexProfileFormat($formattedResponse);
    }

    protected function assetsGetProfileMetadataResponse()
    {
        $response = $this->getProfileMetadata();
        $formattedResponse = $this->assertJsonResponse($response, 200, "Get Profile metadata");
        $this->assertGetProfileMetadataFormat($formattedResponse);
    }

    protected function assetsGetProfileFiltersResponse()
    {
        $response = $this->getProfileFilters();
        $formattedResponse = $this->assertJsonResponse($response, 200, "Get Profile filters");
        $this->assertGetProfileFiltersFormat($formattedResponse);
    }

    protected function assetsGetProfileTagsResponse()
    {
        $response = $this->getProfileTags('profession');
        $formattedResponse = $this->assertJsonResponse($response, 200, "Get Profile tags for profession type");
        $this->assertArrayHasKey('items', $formattedResponse, "Profile tag has not items key");
        $this->assertArrayHasKey(0, $formattedResponse['items'], "Profile tag items has not 0 key");
        $this->assertGetProfileTagFormat($formattedResponse['items'][0], 'writer');
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
        $this->assertContains('heterosexual', $profile['orientation'], "orientation is not heterosexual");
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
        $this->assertContains('homosexual', $profile['orientation'], "orientation is not homosexual");
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
        $this->assertContains('heterosexual', $profile['orientation'], "orientation is not heterosexual");
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
        $this->assertArrayHasKey('name', $profile['language'][0]['tag'], "Language tag has not name key");
        $this->assertArrayHasKey('choice', $profile['language'][0], "Profile has not language[0]['choice'] key");
        $this->assertArrayHasKey('tag', $profile['language'][1], "Profile has not language[1]['tag'] key");
        $this->assertArrayHasKey('name', $profile['language'][1]['tag'], "Language tag has not name key");
        $this->assertArrayHasKey('choice', $profile['language'][1], "Profile has not language[1]['choice'] key");
        $this->assertArrayNotHasKey('education', $profile, "Profile has education key");
        $this->assertArrayHasKey('profession', $profile, "Profile has not profession key");
        $this->assertArrayHasKey(0, $profile['profession'], "Profile has not profession[0] key");
        $this->assertArrayHasKey('name', $profile['profession'][0], "Profile profession tag has not name key");
        $this->assertEquals('writer', $profile['profession'][0]['name'], "profession[0] detail is not writer");
        $this->assertArrayNotHasKey('allergy', $profile, "Profile has allergy key");
        $this->assertEquals('1980-01-01', $profile['birthday'], "birthday is not 1980-01-01");
        $this->assertEquals('Madrid', $profile['location']['locality'], "locality is not Madrid");
        $this->assertEquals('Madrid', $profile['location']['address'], "address is not Madrid");
        $this->assertEquals('Spain', $profile['location']['country'], "country is not Spain");
        $this->assertEquals(-3.7037902, $profile['location']['longitude'], "longitude is not -3.7037902");
        $this->assertEquals(40.4167754, $profile['location']['latitude'], "latitude is not 40.4167754");
        $this->assertEquals('capricorn', $profile['zodiacSign'], "zodiacSign is not capricorn");
        $this->assertEquals('male', $profile['gender'], "gender is not male");
        $this->assertContains('heterosexual', $profile['orientation'], "orientation is not heterosexual");
        $this->assertEquals('es', $profile['interfaceLanguage'], "interfaceLanguage is not es");
        $this->assertEquals('agnosticism', $profile['religion']['choice'], "religion choice is not agnosticism");
        $this->assertEquals('important', $profile['religion']['detail'], "religion detail is not important");
        $this->assertEquals('yes', $profile['sons']['choice'], "sons choice is not yes");
        $this->assertEquals('want', $profile['sons']['detail'], "sons detail is not want");
        $this->assertEquals('married', $profile['civilStatus'], "civilStatus is not married");
        $this->assertEquals('black', $profile['hairColor'], "hairColor is not black");
        $this->assertEquals('friendship', $profile['relationshipInterest'], "relationshipInterest is not friendship");
        $this->assertRegExp('/^(german)|(japanese)$/', $profile['language'][0]['tag']['name'], "language[0]['tag']['name'] is not German or Japanese");
        $this->assertRegExp('/^(professional_working)|(native)$/', $profile['language'][0]['choice'], "language[0]['choice'] is not professional_working or native");
        $this->assertRegExp('/^(german)|(japanese)$/', $profile['language'][1]['tag']['name'], "language[1]['tag']['name'] is not German or Japanese");
        $this->assertRegExp('/^(professional_working)|(native)$/', $profile['language'][1]['choice'], "language[1]['choice'] is not professional_working or native");
    }

    protected function assertGetProfileMetadataFormat($metadata)
    {
        $this->assertArrayOfType('array', $metadata, "Metadata is not an array of arrays");
        $this->assertArrayHasKey('birthday', $metadata, "Metadata has not birthday key");
        $this->assertArrayHasKey('location', $metadata, "Metadata has not location key");
        $this->assertArrayHasKey('gender', $metadata, "Metadata has not gender key");
        $this->assertArrayHasKey('orientation', $metadata, "Metadata has not orientation key");
        $this->assertArrayHasKey('interfaceLanguage', $metadata, "Metadata has not interfaceLanguage key");
        foreach ($metadata as $field)
        {
            $this->assertArrayHasKey('label', $field, "Metadata does not have label key");
            $this->assertArrayHasKey('labelEdit', $field, "Metadata does not have labelEdit key");
            $this->assertArrayHasKey('required', $field, "Metadata does not have required key");
            $this->assertArrayHasKey('editable', $field, "Metadata does not have editable key");
            $this->assertArrayHasKey('type', $field, "Metadata does not have type key");
            if (in_array($field['type'], array('choice', 'multiple_choices'))){
                $this->assertArrayHasKey('choices', $field, 'Metadata does not have required choices');
                $this->assertArrayOfType('array', $field['choices'], 'Metadata choices are not arrays');
                foreach ($field['choices'] as $choice)
                {
                    $this->assertArrayHasKey('id', $choice);
                    $this->assertArrayHasKey('text', $choice);
                }
            };
            if ($field['type'] === 'tags_and_choice'){
                $this->assertArrayHasKey('choices', $field, 'Metadata does not have required choices');
                $this->assertArrayOfType('string', $field['choices'], 'Metadata choices are not strings');
            }
            if ($field['type'] === 'double_choice'){
                $this->assertArrayHasKey('doubleChoices', $field, 'Metadata does not have required double choices');
                $this->assertArrayOfType('array', $field['doubleChoices'], 'Metadata choices are not arrays');
                foreach ($field['doubleChoices'] as $doubleChoice){
                    $this->assertArrayOfType('string', $doubleChoice ,'Metadata double choices are not strings');
                }
            }
        }
    }

    protected function assertGetProfileFiltersFormat($metadata)
    {
        $this->assertArrayOfType('array', $metadata, "Metadata is not an array of arrays");
        $this->assertArrayHasKey('birthday', $metadata, "Metadata has not birthday key");
        $this->assertArrayHasKey('location', $metadata, "Metadata has not location key");
        $this->assertNotContains('gender', $metadata, "Filter metadata has gender key");
        $this->assertArrayHasKey('orientation', $metadata, "Metadata has not orientation key");
        $this->assertNotContains('interfaceLanguage', $metadata, "Filter metadata has interfaceLanguage key");
        foreach ($metadata as $field)
        {
            $this->assertArrayHasKey('label', $field, "Metadata does not have label key");
            $this->assertArrayHasKey('labelEdit', $field, "Metadata does not have labelEdit key");
            $this->assertArrayHasKey('required', $field, "Metadata does not have required key");
            $this->assertArrayHasKey('editable', $field, "Metadata does not have editable key");
            $this->assertArrayHasKey('type', $field, "Metadata does not have type key");
            if (in_array($field['type'], array('choice', 'multiple_choices'))){
                $this->assertArrayHasKey('choices', $field, 'Metadata does not have required choices');
                $this->assertArrayOfType('array', $field['choices'], 'Metadata choices are not arrays');
                foreach ($field['choices'] as $choice)
                {
                    $this->assertArrayHasKey('id', $choice);
                    $this->assertArrayHasKey('text', $choice);
                }
            };
            if ($field['type'] === 'tags_and_choice'){
                $this->assertArrayHasKey('choices', $field, 'Metadata does not have required choices');
                $this->assertArrayOfType('string', $field['choices'], 'Metadata choices are not strings');
            }
            if ($field['type'] === 'double_choice'){
                $this->assertArrayHasKey('doubleChoices', $field, 'Metadata does not have required double choices');
                $this->assertArrayOfType('array', $field['doubleChoices'], 'Metadata choices are not arrays');
                foreach ($field['doubleChoices'] as $doubleChoice){
                    $this->assertArrayOfType('string', $doubleChoice ,'Metadata double choices are not strings');
                }
            }
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
        $this->assertEquals('Invalid date format, valid format is "Y-m-d".', $exception['validationErrors']['birthday'][0], "birthday key is not Invalid date format, valid format is \"YYYY-MM-DD\".");
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
        $this->assertContains('Option with value "non-existent" is not valid, possible values are', $exception['validationErrors']['gender'][0], "gender key is not Option with value \"non-existent\" is not valid, possible values are");
    }

    protected function assertOrientationValidationErrorFormat($exception)
    {
        $this->assertValidationErrorFormat($exception);
        $this->assertArrayHasKey('orientation', $exception['validationErrors'], "Profile has not orientation key");
        $this->assertContains('Option with value "non-existent" is not valid, possible values are', $exception['validationErrors']['orientation'][0], "orientation key is not Option with value \"non-existent\" is not valid, possible values are");
    }

    protected function assertInterfaceLanguageValidationErrorFormat($exception)
    {
        $this->assertValidationErrorFormat($exception);
        $this->assertArrayHasKey('interfaceLanguage', $exception['validationErrors'], "Profile has not interfaceLanguage key");
        $this->assertContains('Option with value "non-existent" is not valid, possible values are', $exception['validationErrors']['interfaceLanguage'][0], "interfaceLanguage key is not Option with value \"non-existent\" is not valid, possible values are");
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
            "orientation" => array("heterosexual"),
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
            "orientation" => array("homosexual"),
            "interfaceLanguage" => "en"
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
            "orientation" => array("heterosexual"),
            "language" => array(
                array(
                    "tag" => array("name" => "German"),
                    "choice" => "professional_working"
                ),
                array(
                    "tag" => array("name" => "Japanese"),
                    "choice" => "native"
                )
            ),
            "education" => null,
            "profession" => array(
                array("name" => "writer")
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
            "orientation" => array("heterosexual"),
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
            "orientation" => array("heterosexual"),
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
            "gender" => "non-existent",
            "orientation" => array("heterosexual"),
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
            "orientation" => array("non-existent"),
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
            "orientation" => array("heterosexual"),
            "interfaceLanguage" => "non-existent"
        );
    }
}