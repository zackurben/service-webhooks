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
    }

    /**
     * Confirm that the webhook has a PartnerMap object.
     */
    public function testWebhookPartnerMap()
    {
        $this->assertObjectHasAttribute('partner_mapping', $this->webhook);
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
