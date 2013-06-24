<?php

use Webhooks\Webhook;

class Webhooks_Test extends PHPUnit_Framework_TestCase
{
    /**
     * The user name.
     *
     * @var string
     */
    protected $user = 'test';

    /**
     * The user's password.
     *
     * @var string
     */
    protected $pass = 'test';

    /**
     * The domain.
     *
     * @var string
     */
    protected $domain = 'http://www.example.com';

    /**
     * The auth type to use.
     *
     * @var string
     */
    protected $auth_type = 'basic_auth';

    function setUp()
    {
        $this->test = new Webhook(array('user' => $this->user, 'pass' => $this->pass), $this->domain, $this->auth_type);
    }
    
    function tearDown()
    {
        unset($this->test);
    }

    // Test to see if webhook contains the necessary attributes.
    function testWebhookAttributes()
    {
        $webhook = $this->test->webhook;
        $this->assertObjectHasAttribute('authentication', $webhook);
        $this->assertObjectHasAttribute('domain', $webhook);
        $this->assertEquals($webhook->domain, $this->domain);
        $this->assertObjectHasAttribute('data', $webhook);
        $this->assertArrayHasKey('user', $webhook->data);
        $this->assertArrayHasKey('pass', $webhook->data);
        $this->assertEquals($webhook->data['user'], $this->user);
        $this->assertEquals($webhook->data['pass'], $this->pass);
    }

    // Test public function to retrieve webhook.
    function testGetWebhook()
    {
        $webhook = $this->test->getWebhook();
        $this->assertEquals($webhook, $this->test->webhook);
    }

    // Test if creating a proper HTTP Client.
    function testGuzzleClient()
    {
        $client = $this->test->getClient();
        $this->assertEquals($client->getBaseUrl(), $this->domain);
    } 

    // Test basic authentication.
    function testBasicAuthentication()
    {
        $client = $this->test->getClient();
        $request = $client->get('/');
        $this->assertEquals($this->user, $request->getUsername());
        $this->assertEquals($this->pass, $request->getPassword());
    }
}
