<?php

/**
 * @file Webhook.php
 *
 * Provides the basic Webhooks plugin definition. Every custom Webhook should
 * extend this skeleton, and be throughly documented. Any custom authentication
 * methods should be communicated to AllPlayers to be included into our
 * authenticate method.
 */

namespace AllPlayers\Webhooks;

use Guzzle\Http\Client;
use Guzzle\Http\Plugin\CurlAuthPlugin;
use Guzzle\Plugin\Oauth\OauthPlugin;

/**
 * Base Webhook definition, to provide structure to all child Webhooks.
 */
class Webhook
{

    /**
     * The list of headers that will be sent with each request.
     *
     * @var array
     */
    protected $headers = array();

    /**
     * The top object of a webhook.
     *
     * @var webhook
     *   ['subscriber'] => Webhook information.
     *   ['data']		=> Webhook data.
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
     * @var /Guzzle/Http/Message/EntityEnclosingRequest
     */
    public $request;

    /**
     * The URL to post the webhook.
     *
     * @var string
     */
    public $domain;

    /**
     * The URL to send webhook data, if testing.
     *
     * @var string
     */
    public $test_domain;

    /**
     * The authentication method used in the post requests.
     *
     * @var string
     */
    public $authentication = 'no_authentication';

    /**
     * The method of data transmission. Valid options are:
     *   'form-urlencoded'
     *   'json'
     *
     * @var string
     */
    public $method = 'json';

    /**
     * Initialize the webhook object.
     *
     * @param array $subscriber
     *   The parameters (subscriber variables) for the request.
     *
     * @param array $data
     *   The event data from the webhook.
     *
     * @param array $preprocess
     *   Data that needs to be processed before the REST methods are called.
     */
    public function __construct(array $subscriber = array(), array $data = array(), array $preprocess = array())
    {
        $this->webhook->subscriber = $subscriber;
        $this->webhook->data = $data;

        $this->client = new Client($this->domain);
        if ($this->authentication != 'no_authentication') {
            $this->authenticate();
        }
        $this->preprocess($preprocess);
    }

    /**
     * Authenticate client based on the webhooks authentication method.
     *
     * This function is not abstract due to the possibility that many partners will
     * need to use either basic_auth or oauth; those who do not can have a custom
     * auth definition here.
     */
    public function authenticate()
    {
        switch ($this->authentication) {
            case 'basic_auth':
                $curlauth = new CurlAuthPlugin($this->webhook->subscriber['user'], $this->webhook->subscriber['pass']);
                $this->client->addSubscriber($curlauth);
                break;
            case 'oauth':
                $oauth_config = array(
                    'consumer_key' => $this->webhook->subscriber['consumer_key'],
                    'consumer_secret' => $this->webhook->subscriber['consumer_secret'],
                    'token' => $this->webhook->subscriber['token'],
                    'secret' => $this->webhook->subscriber['secret'],
                );
                $auth_plugin = new OauthPlugin($oauth_config);
                $this->client->addSubscriber($auth_plugin);
                break;
        }
    }

    /**
     * Get the data to be transmitted for the webhook.
     *
     * @return array
     *   Returns the data to be transmitted in the post request.
     */
    public function getData()
    {
        return $this->webhook->data;
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
     * @return \Guzzle\Http\Message\Request
     *   Returns the service request object.
     */
    public function post()
    {
        // swap domain and redirect domain
        if (isset($this->test_domain) && $this->test_domain != '') {
            $this->webhook->data['original_url'] = $this->domain;
            $this->domain = $this->test_domain;
        }

        // send data in the requested method
        if ($this->method === 'form-urlencoded') {
            $this->request = $this->client->post($this->domain, $this->headers);
            $this->request->addPostFields($this->webhook->data);
        } else {
            $this->request = $this->client->post($this->domain, $this->headers, json_encode($this->webhook->data));
        }
    }

    /**
     * Makes a PUT request to the external service
     *
     * @return \Guzzle\Http\Message\Request
     *   Returns the service request object.
     */
    public function put()
    {
        // swap domain and redirect domain
        if (isset($this->test_domain) && $this->test_domain != '') {
            $this->webhook->data['original_url'] = $this->domain;
            $this->domain = $this->test_domain;
        }

        // send data in the requested method
        if ($this->method === 'form-urlencoded') {
            $this->request = $this->client->put($this->domain, $this->headers);
            $this->request->addPostFields($this->webhook->data);
        } else {
            $this->request = $this->client->put($this->domain, $this->headers, json_encode($this->webhook->data));
        }
    }

    /**
     * Perform any additional processing on the webhook before sending it.
     *
     * @param $data
     *   An array of data to be processed before the webhook data is sent.
     */
    public function preprocess($data)
    {
        // set the redirect url, so we can swap domains before sending the data
        if (isset($data['test_url']) && $data['test_url'] != '') {
            $this->test_domain = $data['test_url'];
        }
    }

    /**
     * Sends a request.
     *
     * @return \Guzzle\Http\Message\Response
     *   Response from the service.
     */
    public function send()
    {
        $this->request->send();
    }

}
