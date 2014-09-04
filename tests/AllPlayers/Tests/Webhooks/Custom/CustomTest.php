<?php
/**
 * Description of SimpleWebhookTest
 *
 * @author zack
 */
namespace AllPlayers\Tests\Webhooks\Custom;

class CustomTest extends \PHPUnit_Framework_TestCase
{
    // Test Variables.
    public $webhook;
    public $domain = 'http://www.example.com';
    public $subscriber = array();
    public $data = array();

    public function setUp()
    {
        $this->subscriber['url'] = $this->domain;
        $this->webhook = new \AllPlayers\Webhooks\Custom\Custom($this->subscriber, $this->data);
    }

    public function testWebhookDomain()
    {
        $this->assertNotNull($this->webhook->getWebhook()->getDomain());
        $this->assertInternalType('string', $this->webhook->getWebhook()->getDomain());
    }

    public function tearDown()
    {
        unset($this->webhook);
    }
}
