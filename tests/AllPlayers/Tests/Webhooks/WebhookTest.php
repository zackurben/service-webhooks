<?php
/**
 * Description of WebhookTest
 *
 * @author zack
 */
namespace AllPlayers\Tests\Webhooks;

class WebhookTest extends \PHPUnit_Framework_TestCase
{
    // Test Variables.
    public $webhook;
    public $subscriber = array();
    public $data = array();

    public $domain = 'http://www.example.com';

    public function setUp()
    {
        $this->webhook = new \AllPlayers\Webhooks\Webhook($this->subscriber, $this->data);
    }

    public function testWebhookAttributes()
    {
        $test = $this->webhook->getWebhook();
        $this->assertObjectHasAttribute('subscriber', $test);
        $this->assertObjectHasAttribute('data', $test);

        $this->assertNotNull($this->webhook->getClient());
        $this->assertInstanceOf("Guzzle\Http\Client", $this->webhook->getClient());

        $this->assertNotNull($this->webhook->getSend());
        $this->assertNotNull($this->webhook->getAuthentication());
        $this->assertNotNull($this->webhook->getMethod());
    }

    public function tearDown()
    {
        unset($this->webhook);
    }
}
