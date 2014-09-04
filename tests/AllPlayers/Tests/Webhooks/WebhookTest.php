<?php
/**
 * @file
 * Contains /AllPlayers/Tests/Webhooks/WebhookTest.
 */

namespace AllPlayers\Tests\Webhooks;

/**
 * The PHPUnit test cases for the AllPlayers service-webhooks class: Webhook.
 */
class WebhookTest extends \PHPUnit_Framework_TestCase
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
        $this->webhook = new \AllPlayers\Webhooks\Webhook($this->subscriber, $this->data);
    }

    /**
     * Confirm that the webhook object has a reference to supported webhooks.
     */
    public function testWebhookClasses()
    {
        $this->assertObjectHasAttribute('classes', $this->webhook);
        $this->assertNotNull(\AllPlayers\Webhooks\Webhook::$classes);
        $this->assertInternalType('array', \AllPlayers\Webhooks\Webhook::$classes);
    }

    /**
     * Confirm that the webhook has a subscriber attribute and that it was set correctly.
     */
    public function testWebhookSubscriber()
    {
        // The webhook data.
        $obj = $this->webhook->getWebhook();

        $this->assertObjectHasAttribute('subscriber', $obj);
        $this->assertNotNull($obj->subscriber);
        $this->assertInternalType('array', $obj->subscriber);
    }

    /**
     * Confirm that the webhook has a data attribute and that it was set correctly.
     */
    public function testWebhookData()
    {
        // The webhook data.
        $obj = $this->webhook->getWebhook();

        $this->assertObjectHasAttribute('data', $obj);
        $this->assertNotNull($obj->data);
        $this->assertInternalType('array', $obj->data);
    }

    /**
     * Confirm that the webhook has an Guzzle Client and that it is initalized.
     */
    public function testWebhookClient()
    {
        $this->assertObjectHasAttribute('client', $this->webhook);
        $this->assertNotNull($this->webhook->getClient());
        $this->assertInstanceOf("Guzzle\Http\Client", $this->webhook->getClient());
    }

    /**
     * Confirm that the webhook has an enumerated value for the send variable.
     */
    public function testWebhookSend()
    {
        $this->assertObjectHasAttribute('send', $this->webhook);
        $this->assertNotNull($this->webhook->getSend());
        $this->assertInternalType('int', $this->webhook->getSend());
    }

    /**
     * Confirm that the webhook has an enumerated value for the Authentication variable.
     */
    public function testWebhookAuthentication()
    {
        $this->assertObjectHasAttribute('authentication', $this->webhook);
        $this->assertNotNull($this->webhook->getAuthentication());
        $this->assertInternalType('int', $this->webhook->getAuthentication());
    }

    /**
     * Confirm that the webhook has an enumerated value for the method variable.
     */
    public function testWebhookMethod()
    {
        $this->assertObjectHasAttribute('method', $this->webhook);
        $this->assertNotNull($this->webhook->getMethod());
        $this->assertInternalType('int', $this->webhook->getMethod());
    }

    /**
     * Destroy the test webhook.
     */
    public function tearDown()
    {
        unset($this->webhook, $this->subscriber, $this->data);
    }
}