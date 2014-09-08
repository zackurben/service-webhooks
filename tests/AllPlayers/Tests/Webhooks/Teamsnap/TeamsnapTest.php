<?php
/**
 * @file
 * Contains /AllPlayers/Tests/Webhooks/Teamsnap/TeamsnapTest.
 */

namespace AllPlayers\Tests\Webhooks\Teamsnap;

use AllPlayers\Tests\Webhooks\WebhookTest;
use AllPlayers\Webhooks\Teamsnap\SimpleWebhook;

/**
 * The base test cases for the TeamSnap implementation of service-webhooks.
 */
class TeamsnapTest extends WebhookTest
{
    // Test Variables.
    public $webhook;
    public $subscriber = array();
    public $data = array();

    /**
     * Initialize a webhook object to test.
     */
    public function setUp()
    {
        $this->subscriber['division_id'] = 12345;
        $this->data['group']['timezone'] = 'America/Chicago';
        $this->data['unit_test'] = 1;

        $this->webhook = new SimpleWebhook($this->subscriber, $this->data);
    }

    /**
     * Confirm that the webhook has a Helper object.
     */
    public function testWebhookHelper()
    {
        $this->assertObjectHasAttribute('helper', $this->webhook);
        $this->assertNotNull($this->webhook->getHelper());
        $this->assertInstanceOf(
            "AllPlayers\Utilities\Helper",
            $this->webhook->getHelper()
        );
    }

    /**
     * Confirm that the webhook has a PartnerMap object.
     */
    public function testWebhookPartnerMap()
    {
        $this->assertObjectHasAttribute('partner_mapping', $this->webhook);
        $this->assertNotNull($this->webhook->getPartnerMap());
        $this->assertInstanceOf(
            "AllPlayers\Utilities\PartnerMap",
            $this->webhook->getPartnerMap()
        );
    }

    /**
     * Confirm that getSport() returns an integer.
     */
    public function testWebhookGetSport()
    {
        $var = $this->webhook->getSport(null);
        $this->assertNotNull($var);
        $this->assertInternalType('int', $var);
    }

    /**
     * Confirm that getRegion() returns an array of timezone and location.
     */
    public function testWebhookGetRegion()
    {
        $obj = $this->webhook->getRegion('America/Chicago');

        $this->assertInternalType('array', $obj);
        $this->assertArrayHasKey('timezone', $obj);
        $this->assertNotNull($obj['timezone']);
        $this->assertArrayHasKey('location', $obj);
        $this->assertNotNull($obj['location']);
    }

    /**
     * Confirm that getGameScores() returns an array of results.
     */
    public function testWebhookGetGameScores()
    {
        // Test data.
        $home_uuid = '00000000-0000-0000-0000-000000000000';
        $home_score = 10;
        $away_uuid = '10000000-0000-0000-0000-000000000000';
        $away_score = 5;
        $competitors = array(
            array(
                'uuid' => $home_uuid,
                'label' => 'Home',
                'score' => $home_score,
            ),
            array(
                'uuid' => $away_uuid,
                'label' => 'Away',
                'score' => $away_score,
            ),
        );

        $obj = $this->webhook->getGameScores($home_uuid, $competitors);

        // Check the contents of the array returned from getGameScore().
        $this->assertInternalType('array', $obj);
        $this->assertArrayHasKey('score_for', $obj);
        $this->assertEquals($home_score, $obj['score_for']);
        $this->assertArrayHasKey('home_or_away', $obj);
        // Check if the team is home.
        $this->assertEquals(1, $obj['home_or_away']);
        $this->assertArrayHasKey('score_against', $obj);
        $this->assertEquals($away_score, $obj['score_against']);
    }

    /**
     * Destroy the test webhook.
     */
    public function tearDown()
    {
        unset(
            $this->webhook_processor,
            $this->webhook,
            $this->domain,
            $this->subscriber,
            $this->data
        );
    }
}
