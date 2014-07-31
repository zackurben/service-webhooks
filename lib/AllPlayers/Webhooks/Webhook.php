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
use Guzzle\Http\Message\Response;
use Guzzle\Http\Plugin\OauthPlugin;
use Guzzle\Http\Plugin\CookiePlugin;
use Guzzle\Http\Plugin\CurlAuthPlugin;
use Guzzle\Http\CookieJar\ArrayCookieJar;

/**
 * Base Webhook definition, to provide structure to all child Webhooks.
 */
class Webhook
{
    /**
     * An enumerated value for using no authentication.
     *
     * @var integer
     */
    const AUTHENTICATION_NONE = 0;

    /**
     * An enumerated value for using basic authentication.
     *
     * AUTHENTICATION_BASIC requires the $subscriber to contain: username and
     * password.
     *
     * @var integer
     */
    const AUTHENTICATION_BASIC = 1;

    /**
     * An enumerated value for using oAuth authentication.
     *
     * AUTHENTICATION_OAUTH requires the $subscriber to contain: consumer_key,
     * consumer_secret, token, and secret.
     *
     * @var integer
     */
    const AUTHENTICATION_OAUTH = 2;

    /**
     * An enumerated value for signifying form-urlencoded data transmission.
     *
     * @var integer
     */
    const TRANSMISSION_URLENCODED = 3;

    /**
     * An enumerated value for signifying JSON data transmission.
     *
     * @var integer
     */
    const TRANSMISSION_JSON = 4;

    /**
     * An enumerated value for a HTTP request method.
     *
     * @var integer
     */
    const HTTP_POST = 5;

    /**
     * An enumerated value for a HTTP request method.
     *
     * @var integer
     */
    const HTTP_PUT = 6;

    /**
     * An enumerated value to confirm sending the webhook.
     *
     * @var integer
     */
    const WEBHOOK_SEND = 7;

    /**
     * An enumerated value to cancel sending the webhook.
     *
     * @var integer
     */
    const WEBHOOK_CANCEL = 8;

    /**
     * A string value for an available webhook type.
     *
     * @var string
     */
    const WEBHOOK_CREATE_GROUP = 'user_creates_group';

    /**
     * A string value for an available webhook type.
     *
     * @var string
     */
    const WEBHOOK_UPDATE_GROUP = 'user_updates_group';

    /**
     * A string value for an available webhook type.
     *
     * @var string
     */
    const WEBHOOK_DELETE_GROUP = 'user_deletes_group';

    /**
     * A string value for an available webhook type.
     *
     * @var string
     */
    const WEBHOOK_ADD_ROLE = 'user_adds_role';

    /**
     * A string value for an available webhook type.
     *
     * @var string
     */
    const WEBHOOK_REMOVE_ROLE = 'user_removes_role';

    /**
     * A string value for an available webhook type.
     *
     * @var string
     */
    const WEBHOOK_ADD_SUBMISSION = 'user_adds_submission';

    /**
     * A string value for an available webhook type.
     *
     * @var string
     */
    const WEBHOOK_DELETE_USER = 'user_removed_from_group';

    /**
     * A string value for an available webhook type.
     *
     * @var string
     */
    const WEBHOOK_CREATE_EVENT = 'user_creates_event';

    /**
     * A string value for an available webhook type.
     *
     * @var string
     */
    const WEBHOOK_UPDATE_EVENT = 'user_updates_event';

    /**
     * A string value for an available webhook type.
     *
     * @var string
     */
    const WEBHOOK_DELETE_EVENT = 'user_deletes_event';

    /**
     * A string value for an available partner mapping option.
     *
     * @var string
     */
    const PARTNER_MAP_USER = 'user';

    /**
     * A string value for an available partner mapping option.
     *
     * @var string
     */
    const PARTNER_MAP_EVENT = 'event';

    /**
     * A string value for an available partner mapping option.
     *
     * @var string
     */
    const PARTNER_MAP_GROUP = 'group';

    /**
     * A string value for an available partner mapping option.
     *
     * @var string
     */
    const PARTNER_MAP_RESOURCE = 'resource';

    /**
     * A string value for an available partner mapping subtype option.
     *
     * @var string
     */
    const PARTNER_MAP_SUBTYPE_USER_EMAIL = 'email';

    /**
     * A string value for an available partner mapping subtype option.
     *
     * @var string
     */
    const PARTNER_MAP_SUBTYPE_USER_CONTACT = 'contact';

    /**
     * A string value for an available partner mapping subtype option.
     *
     * @var string
     */
    const PARTNER_MAP_SUBTYPE_USER_CONTACT_EMAIL = 'contact_email';

    /**
     * A string value for an available partner mapping subtype option.
     *
     * @var string
     */
    const PARTNER_MAP_SUBTYPE_USER_PHONE = 'phone';

    /**
     * A string value for an available partner mapping subtype option.
     *
     * @var string
     */
    const PARTNER_MAP_SUBTYPE_USER_PHONE_CELL = 'phone_cell';

    /**
     * A string value for an available partner mapping subtype option.
     *
     * @var string
     */
    const PARTNER_MAP_SUBTYPE_USER_PHONE_WORK = 'phone_work';

    /**
     * The base url for the AllPlayers Partner-Mapping API.
     *
     * @var string
     */
    const PARTNER_MAPPING_URL_BASE = 'https://api.zurben.apci.ws/api/v2/externalid';

    /**
     * The list of headers that will be sent with each client request.
     *
     * @var array
     */
    protected $headers = array();

    /**
     * The list of headers that will be sent with each api request.
     *
     * @var array
     */
    protected $api_headers = array();

    /**
     * The name of the cookie for AllPlayers Partner-Mapping API Authentication.
     *
     * @var null|CookiePlugin
     */
    protected $cookie = null;

    /**
     * The top object of a webhook.
     *
     * @var stdClass
     */
    protected $webhook;

    /**
     * The Guzzle client object.
     *
     * @var \Guzzle\Http\Client
     */
    protected $client;

    /**
     * The Guzzle request to be sent.
     *
     * @var \Guzzle\Http\Message\EntityEnclosingRequest
     */
    protected $request;

    /**
     * The URL to post the webhook.
     *
     * @var string
     */
    protected $domain;

    /**
     * The URL to redirect webhook data, if testing.
     *
     * @var string
     */
    protected $test_domain;

    /**
     * The method used for Client authentication.
     *
     * @see AUTHENTICATION_NONE
     * @see AUTHENTICATION_BASIC
     * @see AUTHENTICATION_OAUTH
     *
     * @var integer
     */
    protected $authentication = self::AUTHENTICATION_NONE;

    /**
     * The method of data transmission.
     *
     * This establishes the method of transmission between the AllPlayers
     * webhook and the third-party webhook.
     *
     * @see TRANSMISSION_URLENCODED
     * @see TRANSMISSION_JSON
     *
     * @var string
     */
    protected $method = self::TRANSMISSION_JSON;

    /**
     * Determines if the webhook should be sent or not.
     *
     * @see WEBHOOK_SEND
     * @see WEBHOOK_CANCEL
     *
     * @var integer
     */
    protected $send = self::WEBHOOK_SEND;

    /**
     * The unique partner identifier for the AllPlayers partner-mapping API.
     *
     * @var null|string
     */
    protected $partner_id = null;

    /**
     * Initialize the webhook object.
     *
     * @param array $subscriber
     *   The parameters (subscriber variables) for the request.
     * @param array $data
     *   The event data from the webhook.
     * @param array $preprocess
     *   Data that needs to be processed before the REST methods are called.
     */
    public function __construct(
        array $subscriber = array(),
        array $data = array(),
        array $preprocess = array()
    ) {
        $this->webhook->subscriber = $subscriber;
        $this->setData($data);

        $this->client = new Client($this->domain);
        if ($this->authentication != self::AUTHENTICATION_NONE) {
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
            case self::AUTHENTICATION_BASIC:
                $curl_auth = new CurlAuthPlugin(
                    $this->webhook->subscriber['user'],
                    $this->webhook->subscriber['pass']
                );
                $this->client->addSubscriber($curl_auth);
                break;
            case self::AUTHENTICATION_OAUTH:
                $oauth_config = array(
                    'consumer_key' => $this->webhook->subscriber['consumer_key'],
                    'consumer_secret' => $this->webhook->subscriber['consumer_secret'],
                    'token' => $this->webhook->subscriber['token'],
                    'secret' => $this->webhook->subscriber['secret'],
                );
                $oauth_plugin = new OauthPlugin($oauth_config);
                $this->client->addSubscriber($oauth_plugin);
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
     * Set the data in the webhook.
     *
     * This function will set the data that is to be transmitted in the Webhook.
     *
     * @param array $data
     *   The data to send in the Guzzle request.
     */
    protected function setData(array $data)
    {
        $this->webhook->data = $data;
    }

    /**
     * Get the data from the original AllPlayers Webhook.
     *
     * @return array
     *   The data from the original AllPlayers Webhook.
     */
    public function getOriginalData()
    {
        return $this->webhook->original_data;
    }

    /**
     * Store the original AllPlayers webhook data.
     *
     * @param array $data
     *   Set the original webhook data, for use in processing.
     */
    protected function setOriginalData(array $data)
    {
        $this->webhook->original_data = $data;
    }

    /**
     * Get Guzzle HTTP Client.
     *
     * @return \Guzzle\Http\Client
     *   Returns the Guzzle HTTP Client.
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Set the Clients' domain, based on the URL in the webhook definition and test url.
     */
    protected function setDomain()
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
     * Get the webhook send flag.
     *
     * @see WEBHOOK_SEND
     * @see WEBHOOK_CANCEL
     *
     * @return integer
     */
    public function getSend()
    {
        return $this->send;
    }

    /**
     * Set the webhook send flag.
     *
     * @see WEBHOOK_SEND
     * @see WEBHOOK_CANCEL
     */
    protected function setSend($send)
    {
        $this->send = $send;
    }

    /**
     * Make the AllPlayers API cookie for the subsequent requests.
     *
     * This is only required if the webhook is using the Partner-Mapping API.
     *
     * @param string $username
     *   The AllPlayers username for logging into APIv1.
     * @param string $password
     *   The AllPlayers password for logging into APIv1.
     */
    protected function makeCookie($username, $password) {
        // Fetch the AllPlayers Auth cookie
        $this->cookie = new CookiePlugin(new ArrayCookieJar());
        $cookie_client = new Client(
            'https://www.zurben.apci.ws/api/v1/rest/users/login.json',
            array(
                'curl.CURLOPT_SSL_VERIFYPEER' => false,
                'curl.CURLOPT_CERTINFO' => false,
            )
        );
        $cookie_client->addSubscriber($this->cookie);

        $cookie_auth = $cookie_client->post(
            $cookie_client->getBaseUrl(),
            array('Content-Type' => 'application/x-www-form-urlencoded'),
            'email=' . $username . '&password=' . $password
        );
        $cookie_auth->send();

        // Set the AllPlayers auth Header for subsequent API calls.
        $temp = $this->cookie->getCookieJar()->all();
        foreach ($temp as $c) {
            if (strstr($c->getName(), "SESS")) {
                $this->api_headers['X-Token'] = hash("sha256", $c->getValue());
                break;
            }
        }
    }

    /**
     * Makes a POST request to send to the external service.
     *
     * @return Request
     *   Returns the Guzzle request object, ready to send.
     */
    protected function post()
    {
        $this->setDomain();

        // encode data in the requested method
        if ($this->method === self::TRANSMISSION_URLENCODED) {
            $this->request = $this->client->post(
                $this->client->getBaseUrl(),
                $this->headers
            );
            $this->request->addPostFields($this->getData());
        } else {
            $this->headers['Content-Type'] = 'application/json';
            $this->request = $this->client->post(
                $this->client->getBaseUrl(),
                $this->headers,
                json_encode($this->getData())
            );
        }
    }

    /**
     * Makes a PUT request to send to the external service.
     *
     * @return \Guzzle\Http\Message\Request
     *   Returns the Guzzle request object, ready to send.
     */
    protected function put()
    {
        $this->setDomain();

        // encode data in the requested method
        if ($this->method === self::TRANSMISSION_URLENCODED) {
            $this->request = $this->client->put(
                $this->client->getBaseUrl(),
                $this->headers
            );
            $this->request->addPostFields($this->getData());
        } else {
            $this->headers['Content-Type'] = 'application/json';
            $this->request = $this->client->put(
                $this->client->getBaseUrl(),
                $this->headers,
                json_encode($this->getData())
            );
        }
    }

    /**
     * Makes a DELETE request to send to the external service.
     *
     * @return \Guzzle\Http\Message\Request
     *   Returns the Guzzle request object, ready to send.
     */
    protected function delete()
    {
        $this->setDomain();

        // encode data in the requested method
        if ($this->method === self::TRANSMISSION_URLENCODED) {
            $this->request = $this->client->delete(
                $this->client->getBaseUrl(),
                $this->headers
            );
            $this->request->addPostFields($this->getData());
        } else {
            $this->headers['Content-Type'] = 'application/json';
            $this->request = $this->client->delete(
                $this->client->getBaseUrl(),
                $this->headers,
                json_encode($this->getData())
            );
        }
    }

    /**
     * Send the prepared Guzzle request to the external service.
     *
     * @return \Guzzle\Http\Message\Resonse
     *   The Guzzle response object with data about the request.
     */
    public function send()
    {
        // send a debug request if enabled
        include 'config/config.php';
        if (isset($config['debug']) && $config['debug']['active']) {
            $dbg = new Client($config['debug']['url']);
            $req = $dbg->post(
                $dbg->getBaseUrl(),
                array('Content-Type' => 'application/json'),
                json_encode(
                    array(
                        'Body' => json_decode($this->request->getBody(), true),
                        'URL' => $this->request->getUrl(),
                    )
                )
            );
            $req->send();
        }

        return $this->request->send();
    }

    /**
     * Perform processing on the webhook before preparing to send it.
     *
     * @param array $data
     *   An array of data to be processed before the webhook data is sent.
     */
    protected function preprocess(array $data)
    {
        // set the redirect url, so we can swap domains before sending the data
        if (isset($data['test_url']) && $data['test_url'] != '') {
            $this->test_domain = $data['test_url'];
        }
    }

    /**
     * Return the JSON object from a Guzzle Response object.
     *
     * @param \Guzzle\Http\Message\Response $response
     *   The Guzzle Response from which to parse the JSON object.
     *
     * @return array
     *   The JSON decoded, associative keyed, array.
     */
    protected function processJsonResponse(Response $response)
    {
        $return = '';

        // Strip JSON string data from response message
        if (strpos($response->getMessage(), "\n[{") !== false) {
            $return = substr(
                $response->getMessage(),
                strpos($response->getMessage(), '[{')
            );
        } else {
            $return = substr(
                $response->getMessage(),
                strpos($response->getMessage(), '{')
            );
        }

        return json_decode($return, true);
    }

    /**
     * Create a resource mapping between AllPlayers and a partner.
     *
     * @todo Remove cURL options (Used for self-signed certificates).
     *
     * @param string $external_resource_id
     *   The partner resource id to map.
     * @param string $item_type
     *   The AllPlayers item type to map.
     *   @see PARTNER_MAP_USER
     *   @see PARTNER_MAP_EVENT
     *   @see PARTNER_MAP_GROUP
     *   @see PARTNER_MAP_RESOURCE
     * @param string $item_uuid
     *   The AllPlayers item uuid to map.
     * @param string $group_uuid
     *   The AllPlayers group uuid associated with the item uuid.
     * @param string $sub_item_type (Optional)
     *   The resource subtype to map.
     *   @see PARTNER_MAP_SUBTYPE_USER_EMAIL
     *   @see PARTNER_MAP_SUBTYPE_USER_CONTACT
     *   @see PARTNER_MAP_SUBTYPE_USER_CONTACT_EMAIL
     *
     * @return array
     *   The AllPlayers response from creating a resource mapping.
     */
    protected function createPartnerMap(
        $external_resource_id,
        $item_type,
        $item_uuid,
        $group_uuid,
        $sub_item_type = null
    ) {
        $client = new Client(
            self::PARTNER_MAPPING_URL_BASE,
            array(
                'curl.CURLOPT_SSL_VERIFYPEER' => false,
                'curl.CURLOPT_CERTINFO' => false,
            )
        );
        $client->addSubscriber($this->cookie);

        // set required data fields
        $data = array(
            'external_resource_id' => $external_resource_id,
            'item_type' => $item_type,
            'item_uuid' => $item_uuid,
            'group_uuid' => $group_uuid,
            'partner' => $this->partner_id,
        );

        // add subtype if present
        if (!is_null($sub_item_type)) {
            $data['sub_item_type'] = $sub_item_type;
        }

        // send API request and return response
        $request = $client->post(
            $client->getBaseUrl(),
            array_merge(array('Content-Type' => 'application/json'), $this->api_headers),
            json_encode($data)
        );

        $response = $request->send();
        $response = $this->processJsonResponse($response);

        return $response;
    }

    /**
     * Read a resource mapping between AllPlayers and a partner.
     *
     * If the partner_uuid parameter is not included, this function will return
     * all the elements mapped with the item_uuid.
     *
     * @todo Remove cURL options (Used for self-signed certificates).
     *
     * @param string $item_type
     *   The AllPlayers item type to map.
     *   @see PARTNER_MAP_USER
     *   @see PARTNER_MAP_EVENT
     *   @see PARTNER_MAP_GROUP
     *   @see PARTNER_MAP_RESOURCE
     * @param string $item_uuid
     *   The AllPlayers item uuid to map.
     * @param string $group_uuid
     *   The AllPlayers group uuid associated with the item uuid.
     * @param string $sub_item_type (Optional)
     *   The resource subtype to map.
     *   @see PARTNER_MAP_SUBTYPE_USER_EMAIL
     *   @see PARTNER_MAP_SUBTYPE_USER_CONTACT
     *   @see PARTNER_MAP_SUBTYPE_USER_CONTACT_EMAIL
     *
     * @return array
     *   The AllPlayers response from reading a resouce mapping.
     */
    protected function readPartnerMap(
        $item_type,
        $item_uuid,
        $group_uuid,
        $sub_item_type = 'entity'
    ) {
        $url = self::PARTNER_MAPPING_URL_BASE . '/' . $item_type . '/'
            . $item_uuid . '/' . $this->partner_id . '/' . $group_uuid
            . '?sub_item_type=' . $sub_item_type;

        $client = new Client(
            $url,
            array(
                'curl.CURLOPT_SSL_VERIFYPEER' => false,
                'curl.CURLOPT_CERTINFO' => false,
            )
        );
        $client->addSubscriber($this->cookie);

        // send API request and return response
        $request = $client->get($client->getBaseUrl(), $this->api_headers);
        $response = $request->send();
        $response = $this->processJsonResponse($response);

        // remove double array with 1 element
        if (!array_key_exists('message', $response)) {
            $response = array_shift($response);
        }

        return $response;
    }

    /**
     * Delete a resource mapping between AllPlayers and a partner.
     *
     * Either item_type and item_uuid or partner and group_uuid have to be set.
     *
     * If the item_type and item_uuid are included, the entities associated with
     * them will be removed.
     *
     * If the group_uuid alone is set, all entities associated with the group
     * will be removed.
     *
     * @todo Remove cURL options (Used for self-signed certificates).
     *
     * @param string $item_type
     *   The AllPlayers item type to map.
     *   @see PARTNER_MAP_USER
     *   @see PARTNER_MAP_EVENT
     *   @see PARTNER_MAP_GROUP
     *   @see PARTNER_MAP_RESOURCE
     * @param string $group_uuid
     *   The AllPlayers group uuid.
     * @param string $item_uuid (Optional)
     *   The AllPlayers item uuid to map.
     * @param string $sub_item_type (Optional)
     *   The resource subtype to map.
     *   @see PARTNER_MAP_SUBTYPE_USER_EMAIL
     *   @see PARTNER_MAP_SUBTYPE_USER_CONTACT
     *   @see PARTNER_MAP_SUBTYPE_USER_CONTACT_EMAIL
     *
     * @return array
     *   The AllPlayers response from deleting the resouce mapping.
     */
    protected function deletePartnerMap(
        $item_type = null,
        $group_uuid = null,
        $item_uuid = null,
        $sub_item_type = null
    ) {
        $url = array();
        if ($item_type != null) {
            $url['item_type'] = $item_type;
        }
        if ($group_uuid != null) {
            $url['group_uuid'] = $group_uuid;
        }
        if ($item_uuid != null) {
            $url['item_uuid'] = $item_uuid;
        }
        if ($sub_item_type != null) {
            $url['sub_item_type'] = $sub_item_type;
        }
        if ($this->partner_id != null) {
            $url['partner'] = $this->partner_id;
        }

        $url = self::PARTNER_MAPPING_URL_BASE . '?' . http_build_query($url);
        $client = new Client(
            $url,
            array(
                'curl.CURLOPT_SSL_VERIFYPEER' => false,
                'curl.CURLOPT_CERTINFO' => false,
            )
        );
        $client->addSubscriber($this->cookie);

        // send API request and return response
        $response = $client->delete(
            $client->getBaseUrl(),
            array_merge(
                array('Content-Type' => 'application/json'),
                $this->api_headers
            )
        )->send();
        $response = $this->processJsonResponse($response);

        return $response;
    }
}
