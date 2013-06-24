<?php
/**
 * @file
 *
 * Provides the basic webhooks plugin definition.
 */

namespace Webhooks;

use Guzzle\Http\Client;
use Guzzle\Http\Plugin\CurlAuthPlugin;
use Guzzle\Plugin\Oauth\OauthPlugin;

/**
 * Abstract base class to provide interface common to all plugins.
 */
class Webhook
{
    /**
     * Headers that will be sent with each request,
     *
     * @var array
     */
    protected $headers = array();

    /**
     * The top object of a webhook.
     *
     * @var webhook
     */
    public $webhook;

    /**
     * The service client object.
     *
     * @var client
     */
    public $client;

    /**
     * The request to be sent.
     *
     * @var /Guzzle/Http/Message/Request
     */
    public $request;

    /**
     * Initialize webhook.
     *
     * @param array $data
     *   The paramaters for the request.
     * @param string $domain
     *   The domain of the service.
     * @param string $authentication
     *   The type of authentication to be used in service. 
     *
     * @return Webhook
     *   Returns the webhook object.
     */
    public function __construct(array $data = array(), $domain = '', $authentication = 'no_authentication')
    {
        $this->webhook->domain = $domain;
        $this->webhook->authentication = $authentication;
        $this->webhook->data = $data;

        $this->client = new Client($domain);
        if ($authentication != 'no_authentication') {
            $this->authenticate();
        }
    }

    /**
     * Authenticate client.
     */
    public function authenticate()
    {
        switch ($this->webhook->authentication) {
            case 'basic_auth':
                $curlauth = new CurlAuthPlugin($this->webhook->data['user'], $this->webhook->data['pass']);
                $this->client->addSubscriber($curlauth);
                break;
            case 'oauth':
                $oauth_config = array(
                    'consumer_key' => $this->webhook->data['consumer_key'],
                    'consumer_secret' => $this->webhook->data['consumer_secret'],
                    'token' => $this->webhook->data['token'],
                    'secret' => $this->webhook->data['secret']
                );
                $auth_plugin = new OauthPlugin($oauth_config);
                $this->client->addSubscriber($auth_plugin);
                break;
        }
    }

    /**
     * Get webhook object.
     *
     * @return Webhook
     *   Returns the webhook object.
     */
    public function getWebhook()
    {
        return $this->webhook;
    }

    /**
     * Get service client.
     *
     * @return \Guzzle\Http\Client
     *   Returns the http service client.
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Makes a POST request to the external service.
     *
     * @param string $url
     *   URL to post the message to.
     *
     * @return \Guzzle\Http\Message\Request
     *   Returns the service request object.
     */
    public function post($url)
    {
        $this->request = $this->client->post($url, $this->headers);
        return $this->request;
    }

    /**
     * Sends a request.
     *
     * @param array $data
     *   Data to be posted in the request.
     *
     * @return \Guzzle\Http\Message\Response
     *   Response from the service.
     */
    public function send(array $data)
    {
        $this->request->addPostFields($data);
        return $this->request->send();
    }
}

