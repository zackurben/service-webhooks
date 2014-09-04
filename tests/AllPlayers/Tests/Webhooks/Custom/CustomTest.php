<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Custom/CustomTest.
 */

namespace AllPlayers\Tests\Webhooks\Custom;

use AllPlayers\Webhooks\Custom\Custom;
use AllPlayers\Tests\Webhooks\WebhookTest;

/**
 * The PHPUnit test cases for the AllPlayers service-webhooks class: Custom.
 */
class CustomTest extends WebhookTest
{
    // Test Variables.
    public $webhook_processor;
    public $webhook;
    public $domain = 'http://www.example.com';
    public $subscriber = array();
    public $data = array();

    /**
     * Initialize a webhook object to test.
     */
    public function setUp()
    {
        $this->subscriber['url'] = $this->domain;
        $this->webhook_processor = new Custom($this->subscriber, $this->data);
        $this->webhook = $this->webhook_processor->getWebhook();
    }

    /**
     * Confirm that the webhook has a domain and it is set correctly.
     */
    public function testWebhookDomain()
    {
        $actual_domain = $this->webhook->getDomain();

        $this->assertNotNull($actual_domain);
        $this->assertInternalType('string', $actual_domain);
        $this->assertEquals($this->domain, $actual_domain);
    }

    /**
     * Destroy the test webhook.
     */
    public function tearDown()
    {
        unset($this->webhook_processor, $this->webhook, $this->domain, $this->subscriber, $this->data);
    }
}
