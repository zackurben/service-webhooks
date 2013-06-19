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
        $this->test = new Webhook(array('user' => static::$user, 'pass' => static::$pass), static::$auth_type, static::$domain);
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
        $this->assertEquals($webhook->domain, static::$domain);
        $this->assertObjectHasAttribute('auth_data', $webhook);
        $this->assertArrayHasKey('user', $webhook->auth_data);
        $this->assertArrayHasKey('pass', $webhook->auth_data);
        $this->assertEquals($webhook->auth_data['user'], static::$user);
        $this->assertEquals($webhook->auth_data['pass'], static::$pass);
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
        $this->assertEquals($client->getBaseUrl(), static::$domain);
    } 

    // Test basic authentication.
    function testBasicAuthentication()
    {
        $client = $this->test->getClient();
        $request = $client->get('/');
        $this->assertEquals(static::$user, $request->getUsername());
        $this->assertEquals(static::$pass, $request->getPassword());
    }
}
