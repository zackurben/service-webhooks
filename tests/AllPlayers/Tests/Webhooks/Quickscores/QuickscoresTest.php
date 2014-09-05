<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Custom/CustomTest.
 */

namespace AllPlayers\Tests\Webhooks\Quickscores;

use AllPlayers\Webhooks\Webhook;
use AllPlayers\Webhooks\Quickscores\Quickscores;
use AllPlayers\Tests\Webhooks\WebhookTest;

/**
 * The PHPUnit test cases for the AllPlayers service-webhooks class: Quickscores.
 */
class QuickscoresTest extends WebhookTest
{
    // Test Variables.
    public $webhook_processor;
    public $webhook;
    public $subscriber = array('user' => 'foo', 'token' => 'bar');
    public $data = array();

    /**
     * Initialize a webhook object to test.
     */
    public function setUp()
    {
        $this->webhook_processor = new Quickscores($this->subscriber, $this->data);
        $this->webhook = $this->webhook_processor->getWebhook();
    }

    /**
     * Confirm that the domain is set in the webhook definition.
     */
    public function testDomain()
    {
        $this->assertObjectHasAttribute('domain', $this->webhook);
        $this->assertNotNull($this->webhook->getDomain());
        $this->assertInternalType('string', $this->webhook->getDomain());
    }

    /**
     * Confirm that the webhook uses basic authentication.
     */
    public function testWebhookAuthentication()
    {
        $actual_auth = $this->webhook->getAuthentication();
        $expected_auth = Webhook::AUTHENTICATION_BASIC;

        $this->assertObjectHasAttribute('authentication', $this->webhook);
        $this->assertNotNull($actual_auth);
        $this->assertInternalType('int', $actual_auth);
        $this->assertEquals($expected_auth, $actual_auth);
    }

    /**
     * Confirm that the Guzzle requests send data using the urlencoded scheme.
     */
    public function testWebhookTransmission()
    {
        $actual_trans = $this->webhook->getMethod();
        $expected_trans = Webhook::TRANSMISSION_URLENCODED;

        $this->assertObjectHasAttribute('method', $this->webhook);
        $this->assertNotNull($actual_trans);
        $this->assertInternalType('int', $actual_trans);
        $this->assertEquals($expected_trans, $actual_trans);
    }

    /**
     * Destroy the test webhook.
     */
    public function tearDown()
    {
        unset($this->webhook_processor, $this->webhook, $this->subscriber, $this->data);
    }
}
