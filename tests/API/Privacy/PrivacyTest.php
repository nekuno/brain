<?php

namespace Tests\API\Privacy;

class PrivacyTest extends PrivacyAPITest
{
    public function testPrivacy()
    {
        $this->assertGetPrivacyWithoutCredentialsResponse();
        $this->assertCreateAndDeleteVoidPrivacyResponse();
        $this->assertCreatePrivacyResponse();
        $this->assertGetOwnPrivacyResponse();
        $this->assertEditOwnPrivacyResponse();
        $this->assertGetDeletedPrivacyResponse();
        $this->assertValidationErrorsResponse();
        $this->assetsGetPrivacyMetadataResponse();
    }

    protected function assertGetPrivacyWithoutCredentialsResponse()
    {
        $response = $this->getOwnPrivacy(null);
        $this->assertStatusCode($response, 401, "Get Privacy without credentials");
    }

    protected function assertCreateAndDeleteVoidPrivacyResponse()
    {
        $privacyData = $this->getVoidPrivacyFixtures();
        $response = $this->createPrivacy($privacyData);
        $formattedResponse = $this->assertJsonResponse($response, 201, "Create Void PrivacyA");
        $this->assertEmptyPrivacyFormat($formattedResponse);

        $response = $this->deletePrivacy();
        $formattedResponse = $this->assertJsonResponse($response, 200, "Delete Privacy");
        $this->assertEmptyPrivacyFormat($formattedResponse);
    }

    protected function assertValidatePrivacyResponse()
    {
        $privacyData = $this->getPrivacyFixtures();
        $response = $this->validatePrivacy($privacyData);
        $this->assertStatusCode($response, 200, "Bad response on validate privacy");
    }

    protected function assertCreatePrivacyResponse()
    {
        $privacyData = $this->getPrivacyFixtures();
        $response = $this->createPrivacy($privacyData);
        $formattedResponse = $this->assertJsonResponse($response, 201, "Create PrivacyA");
        $this->assertPrivacyFormat($formattedResponse);
    }

    protected function assertGetOwnPrivacyResponse()
    {
        $response = $this->getOwnPrivacy();
        $formattedResponse = $this->assertJsonResponse($response, 200, "Get own privacy");
        $this->assertPrivacyFormat($formattedResponse);
    }

    protected function assertEditOwnPrivacyResponse()
    {
        $privacyData = $this->getEditedPrivacyFixtures();
        $response = $this->editPrivacy($privacyData);
        $formattedResponse = $this->assertJsonResponse($response, 200, "Edit Privacy");
        $this->assertEditedPrivacyFormat($formattedResponse);

        $privacyData = $this->getPrivacyFixtures();
        $response = $this->editPrivacy($privacyData);
        $formattedResponse = $this->assertJsonResponse($response, 200, "Edit Privacy");
        $this->assertPrivacyFormat($formattedResponse);
    }

    protected function assertGetDeletedPrivacyResponse()
    {
        $response = $this->deletePrivacy();
        $formattedResponse = $this->assertJsonResponse($response, 200, "Delete Privacy");
        $this->assertPrivacyFormat($formattedResponse);

        $response = $this->getOwnPrivacy();
        $this->assertJsonResponse($response, 404, "Get none-existent privacy");
    }

    protected function assertValidationErrorsResponse()
    {
        $privacyData = $this->getPrivacyWithNoneExistentDescriptionKeyFixtures();
        $response = $this->createPrivacy($privacyData);
        $formattedResponse = $this->assertJsonResponse($response, 422, "Create Privacy with description key error");
        $this->assertNoneExistentDescriptionKeyValidationErrorFormat($formattedResponse);

        $privacyData = $this->getPrivacyWithStringDescriptionValueFixtures();
        $response = $this->createPrivacy($privacyData);
        $formattedResponse = $this->assertJsonResponse($response, 422, "Create Privacy with string as value");
        $this->assertStringDescriptionValueValidationErrorFormat($formattedResponse);
    }

    protected function assetsGetPrivacyMetadataResponse()
    {
        $response = $this->getPrivacyMetadata();
        $formattedResponse = $this->assertJsonResponse($response, 200, "Get Privacy metadata");
        $this->assertGetPrivacyMetadataFormat($formattedResponse);
    }

    protected function assertEmptyPrivacyFormat($privacy)
    {
        $this->assertEquals([], $privacy, "privacy is not an empty array");
    }

    protected function assertPrivacyFormat($privacy)
    {
        $this->assertArrayHasKey('profile', $privacy, "Privacy has not profile key");
        $this->assertArrayHasKey('description', $privacy, "Privacy has not description key");
        $this->assertArrayHasKey('questions', $privacy, "Privacy has not questions key");
        $this->assertArrayHasKey('gallery', $privacy, "Privacy has not gallery key");
        $this->assertArrayHasKey('messages', $privacy, "Privacy has not messages key");
        $this->assertArrayHasKey('key', $privacy['profile'], "Profile Privacy has not key key");
        $this->assertArrayHasKey('value', $privacy['profile'], "Profile Privacy has not value key");
        $this->assertArrayHasKey('key', $privacy['description'], "Description Privacy has not key key");
        $this->assertArrayHasKey('value', $privacy['description'], "Description Privacy has not value key");
        $this->assertArrayHasKey('key', $privacy['questions'], "Questions Privacy has not key key");
        $this->assertArrayHasKey('value', $privacy['questions'], "Questions Privacy has not value key");
        $this->assertArrayHasKey('key', $privacy['gallery'], "Gallery Privacy has not key key");
        $this->assertArrayHasKey('value', $privacy['gallery'], "Gallery Privacy has not value key");
        $this->assertArrayHasKey('key', $privacy['messages'], "Messages Privacy has not key key");
        $this->assertArrayHasKey('value', $privacy['messages'], "Messages Privacy has not value key");
        $this->assertEquals('favorite', $privacy['profile']['key'], "Profile Privacy key is not favorite");
        $this->assertEquals(null, $privacy['profile']['value'], "Profile Privacy value is not null");
        $this->assertEquals('min_compatibility', $privacy['description']['key'], "Description Privacy key is not min_compatibility");
        $this->assertEquals(84, $privacy['description']['value'], "Description Privacy value is not 84");
        $this->assertEquals('favorite', $privacy['questions']['key'], "Questions Privacy key is not favorite");
        $this->assertEquals(null, $privacy['questions']['value'], "Questions Privacy value is not null");
        $this->assertEquals('min_similarity', $privacy['gallery']['key'], "Gallery Privacy key is not min_similarity");
        $this->assertEquals(72, $privacy['gallery']['value'], "Gallery Privacy value is not 72");
        $this->assertEquals('message', $privacy['messages']['key'], "Messages Privacy key is not message");
        $this->assertEquals(null, $privacy['messages']['value'], "Messages Privacy value is not null");
    }

    protected function assertEditedPrivacyFormat($privacy)
    {
        $this->assertArrayHasKey('profile', $privacy, "Privacy has not profile key");
        $this->assertArrayHasKey('description', $privacy, "Privacy has not description key");
        $this->assertArrayHasKey('questions', $privacy, "Privacy has not questions key");
        $this->assertArrayHasKey('gallery', $privacy, "Privacy has not gallery key");
        $this->assertArrayHasKey('messages', $privacy, "Privacy has not messages key");
        $this->assertArrayHasKey('key', $privacy['profile'], "Profile Privacy has not key key");
        $this->assertArrayHasKey('value', $privacy['profile'], "Profile Privacy has not value key");
        $this->assertArrayHasKey('key', $privacy['description'], "Description Privacy has not key key");
        $this->assertArrayHasKey('value', $privacy['description'], "Description Privacy has not value key");
        $this->assertArrayHasKey('key', $privacy['questions'], "Questions Privacy has not key key");
        $this->assertArrayHasKey('value', $privacy['questions'], "Questions Privacy has not value key");
        $this->assertArrayHasKey('key', $privacy['gallery'], "Gallery Privacy has not key key");
        $this->assertArrayHasKey('value', $privacy['gallery'], "Gallery Privacy has not value key");
        $this->assertArrayHasKey('key', $privacy['messages'], "Messages Privacy has not key key");
        $this->assertArrayHasKey('value', $privacy['messages'], "Messages Privacy has not value key");
        $this->assertEquals('message', $privacy['profile']['key'], "Profile Privacy key is not message");
        $this->assertEquals(null, $privacy['profile']['value'], "Profile Privacy value is not null");
        $this->assertEquals('all', $privacy['description']['key'], "Description Privacy key is not all");
        $this->assertEquals(null, $privacy['description']['value'], "Description Privacy value is not null");
        $this->assertEquals('min_similarity', $privacy['questions']['key'], "Questions Privacy key is not min_similarity");
        $this->assertEquals(51, $privacy['questions']['value'], "Questions Privacy value is not 51");
        $this->assertEquals('min_compatibility', $privacy['gallery']['key'], "Gallery Privacy key is not min_compatibility");
        $this->assertEquals(99, $privacy['gallery']['value'], "Gallery Privacy value is not 99");
        $this->assertEquals('all', $privacy['messages']['key'], "Messages Privacy key is not all");
        $this->assertEquals(null, $privacy['messages']['value'], "Messages Privacy value is not null");
    }

    protected function assertGetPrivacyMetadataFormat($metadata)
    {
        $this->assertArrayHasKey('profile', $metadata, "Metadata has not profile key");
        $this->assertArrayHasKey('description', $metadata, "Metadata has not description key");
        $this->assertArrayHasKey('questions', $metadata, "Metadata has not questions key");
        $this->assertArrayHasKey('gallery', $metadata, "Metadata has not gallery key");
        $this->assertArrayHasKey('messages', $metadata, "Metadata has not messages key");
        $this->assertArrayHasKey('type', $metadata['profile'], "Metadata profile has not type key");
        $this->assertArrayHasKey('label', $metadata['profile'], "Metadata profile has not label key");
        $this->assertArrayHasKey('choices', $metadata['profile'], "Metadata profile has not choices key");
        $this->assertArrayHasKey('all', $metadata['profile']['choices'], "Metadata profile choices has not all key");
        $this->assertArrayHasKey('favorite', $metadata['profile']['choices'], "Metadata profile choices has not favorite key");
        $this->assertArrayHasKey('message', $metadata['profile']['choices'], "Metadata profile choices has not message key");
        $this->assertArrayHasKey('min_compatibility', $metadata['profile']['choices'], "Metadata profile choices has not min_compatibility key");
        $this->assertArrayHasKey('min_similarity', $metadata['profile']['choices'], "Metadata profile choices has not min_similarity key");
        $this->assertArrayHasKey('name', $metadata['profile']['choices']['min_similarity'], "Metadata profile min_similarity choice has not name key");
        $this->assertArrayHasKey('min_value', $metadata['profile']['choices']['min_similarity'], "Metadata profile min_similarity choice has not min_value key");
        $this->assertArrayHasKey('max_value', $metadata['profile']['choices']['min_similarity'], "Metadata profile min_similarity choice has not max_value key");
    }

    protected function assertNoneExistentDescriptionKeyValidationErrorFormat($exception)
    {
        $this->assertValidationErrorFormat($exception);
        $this->assertArrayHasKey('description', $exception['validationErrors'], "Privacy has not description key");
        $this->assertContains('Option with value "none-existent" is not valid, possible values are', $exception['validationErrors']['description'][0], "description key does not contain 'Option with value \"none-existent\" is not valid, possible values are'");
    }

    protected function assertStringDescriptionValueValidationErrorFormat($exception)
    {
        $this->assertValidationErrorFormat($exception);
        $this->assertArrayHasKey('description', $exception['validationErrors'], "Privacy has not description key");
        $this->assertEquals('Integer value required for "min_compatibility"', $exception['validationErrors']['description'][0], "description value is not 'Integer value required for \"min_compatibility\"");
    }

    private function getVoidPrivacyFixtures()
    {
        return array();
    }

    private function getPrivacyFixtures()
    {
        return array(
            "profile" => array(
                "key" => "favorite",
                "value" => null,
            ),
            "description" => array(
                "key" => "min_compatibility",
                "value" => 84,
            ),
            "questions" => array(
                "key" => "favorite",
                "value" => null,
            ),
            "gallery" => array(
                "key" => "min_similarity",
                "value" => 72,
            ),
            "messages" => array(
                "key" => "message",
                "value" => null,
            ),
        );
    }

    private function getEditedPrivacyFixtures()
    {
        return array(
            "profile" => array(
                "key" => "message",
                "value" => null,
            ),
            "description" => array(
                "key" => "all",
                "value" => null,
            ),
            "questions" => array(
                "key" => "min_similarity",
                "value" => 51,
            ),
            "gallery" => array(
                "key" => "min_compatibility",
                "value" => 99,
            ),
            "messages" => array(
                "key" => "all",
                "value" => null,
            ),
        );
    }

    private function getPrivacyWithNoneExistentDescriptionKeyFixtures()
    {
        return array(
            "profile" => array(
                "key" => "all",
                "value" => null,
            ),
            "description" => array(
                "key" => "none-existent",
                "value" => null,
            ),
            "questions" => array(
                "key" => "min_similarity",
                "value" => 51,
            ),
            "gallery" => array(
                "key" => "min_compatibility",
                "value" => 99,
            ),
            "messages" => array(
                "key" => "all",
                "value" => null,
            ),
        );
    }

    private function getPrivacyWithStringDescriptionValueFixtures()
    {
        return array(
            "profile" => array(
                "key" => "all",
                "value" => null,
            ),
            "description" => array(
                "key" => "min_compatibility",
                "value" => 'no-integer',
            ),
            "questions" => array(
                "key" => "min_similarity",
                "value" => 51,
            ),
            "gallery" => array(
                "key" => "min_compatibility",
                "value" => 99,
            ),
            "messages" => array(
                "key" => "all",
                "value" => null,
            ),
        );
    }
}