<?php
/**
 * @file
 * Contains /AllPlayers/Tests/Webhooks/WebhookTest.
 */

namespace AllPlayers\Tests\Webhooks;

use AllPlayers\Webhooks\Webhook;

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
        $this->webhook = new Webhook($this->subscriber, $this->data);
    }

    /**
     * Confirm public access to supported webhooks.
     */
    public function testWebhookClasses()
    {
        $this->assertObjectHasAttribute('classes', $this->webhook);
        $this->assertNotNull(Webhook::$classes);
        $this->assertInternalType('array', Webhook::$classes);
    }

    /**
     * Confirm that the getWebhook function returns expected results.
     */
    public function testWebhookGetWebhook()
    {
        $this->assertObjectHasAttribute('webhook', $this->webhook);
        $this->assertNotNull($this->webhook->getWebhook());
        $this->assertInternalType('object', $this->webhook->getWebhook());
    }

    /**
     * Confirm that the webhook has a subscriber.
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
     * Confirm that we can manipulate the subscriber data.
     */
    public function testWebhookSubscriberMutate()
    {
        // Dummy data to use.
        $expected = array('test1', 'test2');

        $this->webhook->setSubscriber($expected);
        $this->assertEquals($expected, $this->webhook->getSubscriber());
    }

    /**
     * Confirm that the webhook has request data.
     */
    public function testWebhookRequestData()
    {
        // The webhook data.
        $obj = $this->webhook->getWebhook();

        $this->assertObjectHasAttribute('request_data', $obj);
        $this->assertNotNull($obj->request_data);
        $this->assertInternalType('array', $obj->request_data);
    }

    /**
     * Confirm that the webhook has allplayers data.
     */
    public function testWebhookAllplayersData()
    {
        // The webhook data.
        $obj = $this->webhook->getWebhook();

        $this->assertObjectHasAttribute('allplayers_data', $obj);
        $this->assertNotNull($obj->allplayers_data);
        $this->assertInternalType('array', $obj->allplayers_data);
    }

    /**
     * Confirm that the webhook has an Guzzle Client and that it is initialized.
     */
    public function testWebhookClient()
    {
        $this->assertObjectHasAttribute('client', $this->webhook);
        $this->assertNotNull($this->webhook->getClient());
        $this->assertInstanceOf(
            'Guzzle\\Http\\Client',
            $this->webhook->getClient()
        );
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
     * Confirm that the webhook has an enumerated value for the Authentication.
     */
    public function testWebhookAuthentication()
    {
        $this->assertObjectHasAttribute('authentication', $this->webhook);
        $this->assertNotNull($this->webhook->getAuthentication());
        $this->assertInternalType('int', $this->webhook->getAuthentication());
    }

    /**
     * Confirm that the webhook has an enumerated value for the transmission.
     */
    public function testWebhookMethod()
    {
        $this->assertObjectHasAttribute('method', $this->webhook);
        $this->assertNotNull($this->webhook->getMethod());
        $this->assertInternalType('int', $this->webhook->getMethod());
    }

    /**
     * Confirm that the webhook has valid headers for a Guzzle request object.
     */
    public function testWebhookHeaders()
    {
        $this->assertObjectHasAttribute('headers', $this->webhook);

        // The webhook request headers.
        $headers = $this->webhook->getHeaders();
        $this->assertNotNull($headers);
        $this->assertInternalType('array', $headers);
    }

    /**
     * Confirm that the api request has valid headers for a Guzzle request.
     */
    public function testWebhookApiHeaders()
    {
        $this->assertObjectHasAttribute('api_headers', $this->webhook);

        // The webhook api request headers.
        $headers = $this->webhook->getApiHeaders();
        $this->assertNotNull($headers);
        $this->assertInternalType('array', $headers);
    }

    /**
     * Confirm that the webhook POST function correctly prepares the request.
     */
    public function testWebhookPost()
    {
        $this->webhook->post();
        $this->assertObjectHasAttribute('request', $this->webhook);

        // The webhook request object.
        $request = $this->webhook->getRequest();
        $this->assertNotNull($request);
        $this->assertInstanceOf(
            '\\Guzzle\\Http\\Message\\EntityEnclosingRequest',
            $request
        );
        $this->assertEquals($request->getMethod(), 'POST');
    }

    /**
     * Confirm that the webhook PUT function correctly prepares the request.
     */
    public function testWebhookPut()
    {
        $this->webhook->put();
        $this->assertObjectHasAttribute('request', $this->webhook);

        // The webhook request object.
        $request = $this->webhook->getRequest();
        $this->assertNotNull($request);
        $this->assertInstanceOf(
            '\\Guzzle\\Http\\Message\\EntityEnclosingRequest',
            $request
        );
        $this->assertEquals($request->getMethod(), 'PUT');
    }

    /**
     * Confirm that the webhook DELETE function correctly prepares the request.
     */
    public function testWebhookDelete()
    {
        $this->webhook->delete();
        $this->assertObjectHasAttribute('request', $this->webhook);

        // The webhook request object.
        $request = $this->webhook->getRequest();
        $this->assertNotNull($request);
        $this->assertInstanceOf(
            '\\Guzzle\\Http\\Message\\EntityEnclosingRequest',
            $request
        );
        $this->assertEquals($request->getMethod(), 'DELETE');
    }

    /**
     * Destroy the test webhook.
     */
    public function tearDown()
    {
        unset($this->webhook, $this->subscriber, $this->data);
    }
}
