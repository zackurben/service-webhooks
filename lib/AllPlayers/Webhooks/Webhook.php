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
     *   ['data']       => Webhook data.
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
     * The method used for Client authentication.
     *
     * Options:
     *   'no_authentication'
     *   'basic_auth'
     *   'oauth'
     * Default:
     *   'no_authentication'
     *
     * If using 'basic_auth', the $subscriber must contain: user and pass.
     * If using 'oauth', the $subscriber must contain: consumer_key, consumer_secret, token, and secret.
     */
    public $authentication = 'no_authentication';

    /**
     * The method of data transmission.
     *
     * Options:
     *   'form-urlencoded'
     *   'json'
     * Default:
     *   'json'
     *
     * @var string
     */
    public $method = 'json';

    /**
     * Determines if the webhook will return data that requires processing.
     *
     * Options:
     *   true
     *   false
     * Default:
     *   false
     *
     * @var boolean
     */
    public $processing = false;

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
     * Set the Clients' domain, based on the URL in the webhook definition and test url.
     */
    public function setDomain()
    {
        // swap domain and redirect domain
        if (isset($this->test_domain) && $this->test_domain != '') {
            $this->webhook->data['original_url'] = $this->domain;
            $this->client->setBaseUrl($this->test_domain);
        } else {
            $this->client->setBaseUrl($this->domain);
        }
    }

    /**
     * Makes a POST request to the external service.
     *
     * @return \Guzzle\Http\Message\Request
     *   Returns the service request object.
     */
    public function post()
    {
        $this->setDomain();

        // send data in the requested method
        if ($this->method === 'form-urlencoded') {
            $this->request = $this->client->post($this->client->getBaseUrl(), $this->headers);
            $this->request->addPostFields($this->webhook->data);
        } else {
            $this->headers['Content-Type'] = 'application/json';
            $this->request = $this->client->post($this->client->getBaseUrl(), $this->headers, json_encode($this->webhook->data));
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
        $this->setDomain();

        // send data in the requested method
        if ($this->method === 'form-urlencoded') {
            $this->request = $this->client->put($this->client->getBaseUrl(), $this->headers);
            $this->request->addPostFields($this->webhook->data);
        } else {
            $this->headers['Content-Type'] = 'application/json';
            $this->request = $this->client->put($this->client->getBaseUrl(), $this->headers, json_encode($this->webhook->data));
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
     * Create a resource mapping between AllPlayers and a partner.
     *
     * @param string $external_resource_id
     *   The partner resource id to map.
     * @param string $item_type
     *   The AllPlayers item type to map. Available options are: user, event,
     *   group, and resource.
     * @param string $item_uuid
     *   The AllPlayers item uuid to map.
     * @param string $partner_uuid
     *   The AllPlayers partner uuid.
     *
     * @return array
     *   The AllPlayers response from creating a resource mapping.
     */
    public function createPartnerMap($external_resource_id, $item_type, $item_uuid, $partner_uuid)
    {
        // temp var to reset client after use
        $temp_method = $this->method;

        // set required data fields
        $data = array(
            'external_resource_id' => $external_resource_id,
            'item_type' => $item_type,
            'item_uuid' => $item_uuid,
            'partner_uuid' => $partner_uuid,
        );

        // force method to be json
        $this->method = 'json';

        // set and send json data
        $this->webhook->data = $data;
        $this->post();

        // reset client to prior state
        $this->method = $temp_method;
        return $this->request->send();
    }

    /**
     * Read a resource mapping between AllPlayers and a partner.
     *
     * @param string $item_type
     *   The AllPlayers item type to map. Available options are: user, event,
     *   group, and resource.
     * @param string $item_uuid
     *   The AllPlayers item uuid to map.
     * @param string $partner_uuid (Optional)
     *   The AllPlayers partner uuid.
     *
     * @return array
     *   The AllPlayers response from reading a resouce mapping.
     */
    public function readPartnerMap($item_type, $item_uuid, $partner_uuid = null)
    {
        $data = array(
            'item_type' => 'event',
            'item_uuid' => '94018426-f61e-11e0-98df-12313d18191a',
        );

        if (!is_null($partner_uuid)) {
            $data['partner_uuid'] = '92920be2-f61e-11e0-98df-12313d18191a';
        }

        // build and send guzzle GET
    }

    /**
     * Delete a resource mapping between AllPlayers and a partner.
     *
     * @param string $item_type
     *   The AllPlayers item type to map. Available options are: user, event,
     *   group, and resource.
     * @param string $item_uuid
     *   The AllPlayers item uuid to map.
     * @param string $partner_uuid (Optional)
     *   The AllPlayers partner uuid.
     */
    public function deletePartnerMap($item_type, $item_uuid, $partner_uuid = null)
    {
        $data = array(
            'item_type' => 'event',
            'item_uuid' => '94018426-f61e-11e0-98df-12313d18191a',
        );

        if (!is_null($partner_uuid)) {
            $data['partner_uuid'] = '92920be2-f61e-11e0-98df-12313d18191a';
        }

        // build and send guzzle DELETE
    }

}
