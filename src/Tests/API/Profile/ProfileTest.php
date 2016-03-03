<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Tests\API\Profile;

use Console\Command\Neo4jProfileOptionsCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

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
        $this->assertCreateProfilesFormat();
        $this->assertGetExistentProfileResponse();
        $this->assertGetDeletedProfileResponse();
        $this->assertValidationErrorsResponse();
    }

    protected function assertProfileOptionsCommandDisplay()
    {
        $display = $this->runProfileOptionsCommand();
        $this->assertRegExp('/\n[^0]\snew\sprofile\soptions\screated\./', $display);
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

    protected function assertCreateProfilesFormat()
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

    protected function assertGetOwnProfileFormat()
    {
        $response = $this->getOwnProfile();
        $formattedResponse = $this->assertJsonResponse($response, 200, "Get own profile");
        $this->assertProfileFormat($formattedResponse, "Bad own profile response");
    }

    protected function assertEditOwnProfileFormat()
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

    protected function assertProfileFormat($user)
    {
        $this->assertArrayHasKey('birthday', $user, "User has not birthday key");
        $this->assertArrayHasKey('location', $user, "User has not location key");
        $this->assertArrayHasKey('locality', $user['location'], "User has not location key");
        $this->assertArrayHasKey('address', $user['location'], "User has not location key");
        $this->assertArrayHasKey('country', $user['location'], "User has not location key");
        $this->assertArrayHasKey('longitude', $user['location'], "User has not location key");
        $this->assertArrayHasKey('latitude', $user['location'], "User has not location key");
        $this->assertArrayHasKey('zodiacSign', $user, "User has not zodiacSign key");
        $this->assertArrayHasKey('gender', $user, "User has not gender key");
        $this->assertArrayHasKey('orientation', $user, "User has not orientation key");
        $this->assertArrayHasKey('interfaceLanguage', $user, "User has not interfaceLanguage key");
        $this->assertEquals('1970-01-01', $user['birthday'], "birthday is not 1970-01-01");
        $this->assertEquals('Madrid', $user['location']['locality'], "locality is not Madrid");
        $this->assertEquals('Madrid', $user['location']['address'], "address is not Madrid");
        $this->assertEquals('Spain', $user['location']['country'], "country is not Spain");
        $this->assertEquals(-3.7037902, $user['location']['longitude'], "longitude is not -3.7037902");
        $this->assertEquals(40.4167754, $user['location']['latitude'], "latitude is not 40.4167754");
        $this->assertEquals('capricorn', $user['zodiacSign'], "zodiacSign is not capricorn");
        $this->assertEquals('male', $user['gender'], "gender is not male");
        $this->assertEquals('heterosexual', $user['orientation'], "orientation is not heterosexual");
        $this->assertEquals('es', $user['interfaceLanguage'], "interfaceLanguage is not es");
    }

    protected function assertEditedProfileFormat($user)
    {
        $this->assertArrayHasKey('birthday', $user, "User has not birthday key");
        $this->assertArrayHasKey('location', $user, "User has not location key");
        $this->assertArrayHasKey('locality', $user['location'], "User has not location key");
        $this->assertArrayHasKey('address', $user['location'], "User has not location key");
        $this->assertArrayHasKey('country', $user['location'], "User has not location key");
        $this->assertArrayHasKey('longitude', $user['location'], "User has not location key");
        $this->assertArrayHasKey('latitude', $user['location'], "User has not location key");
        $this->assertArrayHasKey('zodiacSign', $user, "User has not zodiacSign key");
        $this->assertArrayHasKey('gender', $user, "User has not gender key");
        $this->assertArrayHasKey('orientation', $user, "User has not orientation key");
        $this->assertArrayHasKey('interfaceLanguage', $user, "User has not interfaceLanguage key");
        $this->assertEquals('1981-11-10', $user['birthday'], "birthday is not 1981-11-10");
        $this->assertEquals('Palma', $user['location']['locality'], "locality is not Palma");
        $this->assertEquals('c/ Marquès de la Fontsanta, 36', $user['location']['address'], "address is not c/ Marquès de la Fontsanta, 36");
        $this->assertEquals('Spain', $user['location']['country'], "country is not Spain");
        $this->assertEquals(2.657593, $user['location']['longitude'], "longitude is not -3.7037902");
        $this->assertEquals(39.577383, $user['location']['latitude'], "latitude is not 40.4167754");
        $this->assertEquals('scorpio', $user['zodiacSign'], "zodiacSign is not scorpio");
        $this->assertEquals('female', $user['gender'], "gender is not female");
        $this->assertEquals('homosexual', $user['orientation'], "orientation is not homosexual");
        $this->assertEquals('en', $user['interfaceLanguage'], "interfaceLanguage is not en");
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
        $application = new Application();
        $application->add(new Neo4jProfileOptionsCommand($this->app));

        $command = $application->find('neo4j:profile-options');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        return $commandTester->getDisplay();
    }
}