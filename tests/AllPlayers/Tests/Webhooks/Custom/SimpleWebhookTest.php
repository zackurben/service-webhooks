<?php
/**
 * Description of SimpleWebhookTest
 *
 * @author zack
 */
namespace AllPlayers\Tests\Webhooks\Custom;

class SimpleWebhookTest extends \PHPUnit_Framework_TestCase
{
    // Test Variables.
    public $webhook;
    public $domain = 'http://www.example.com';
    public $data = array(

    );

    public function setUp()
    {
        $this->webhook = new AllPlayers\Webhooks\Custom\Custom(
            array('url' => $this->domain),
            array()
        );
    }

    public function testWebhookAttributes()
    {

    }

    public function tearDown()
    {
        unset($this->webhook);
    }
}
