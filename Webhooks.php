<?php
/**
 * @file
 *
 * Provides the basic webhooks plugin definition.
 */

namespace Webhooks;

use Guzzle\Http\Client;
use Guzzle\Http\Plugin\CurlAuthPlugin;

/**
 * Abstract base class to provide interface common to all plugins.
 */
class Webhook {

    /**
     * Headers that will be sent with each request,
     *
     * @var array
     */
    protected $headers = NULL;

    /**
     * The top object of a webhook.
     *
     * @var webhook
     */
    public $webhook = NULL;

    /**
     * The service client object.
     *
     * @var client
     */
    public $client = NULL;

    /**
     * The request to be sent.
     *
     * @var /Guzzle/Http/Message/Request
     */
    public $request = NULL;

    /**
     * Initialize webhook.
     */
    public function __construct($auth_data = array(), $authentication = 'basic_auth', $domain = '') {
        $this->webhook->domain = $domain;
        $this->webhook->authentication = $authentication;
        $this->webhook->auth_data = $auth_data;

        $this->client = new Client($domain);
        $this->authenticate();
    }

    /**
     * Authenticate client.
     */
    public function authenticate() {
        switch ($this->webhook->authentication) {
            case 'basic_auth':
                $curlauth = new CurlAuthPlugin($this->webhook->auth_data['user'], $this->webhook->auth_data['pass']);
                $this->client->addSubscriber($curlauth);
                break;
            default:
                // No authentication, do nothing.
        }
    }

    /**
     * Get webhook object.
     *
     * @return Webhook
     */
    public function getWebhook() {
        return $this->webhook;
    }

    /**
     * Get service client.
     *
     * @return \Guzzle\Http\Client
     */
    public function getClient() {
        return $this->client;
    }

    /**
     * Makes a POST request to the external service.
     *
     * @param string $url
     *   URL to post the message to.
     *
     * @return \Guzzle\Http\Message\Request
     */
    public function post($url) {
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
    public function send($data) {
        $this->request->addPostFields($data);
        return $this->request->send();
    }

}
