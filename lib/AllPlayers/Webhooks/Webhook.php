<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Webhook.
 *
 * Provides the basic Webhooks plugin definition.
 *
 * Every custom Webhook should extend this skeleton, and be throughly
 * documented. Any custom authentication methods should be communicated to
 * AllPlayers to be included into our Webhook#authenticate() method.
 */

namespace AllPlayers\Webhooks;

use Guzzle\Http\Client;

/**
 * The base Webhook definition; provides structure to all child Webhooks.
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
     * A string value for the name of the class corresponding to a webhook type.
     *
     * @var array
     */
    public static $classes = array(
        self::WEBHOOK_CREATE_GROUP => "UserCreatesGroup",
        self::WEBHOOK_UPDATE_GROUP => "UserUpdatesGroup",
        self::WEBHOOK_DELETE_GROUP => "UserDeletesGroup",
        self::WEBHOOK_ADD_ROLE => "UserAddsRole",
        self::WEBHOOK_REMOVE_ROLE => "UserRemovesRole",
        self::WEBHOOK_ADD_SUBMISSION => "UserAddsSubmission",
        self::WEBHOOK_DELETE_USER => "UserRemovedFromGroup",
        self::WEBHOOK_CREATE_EVENT => "UserCreatesEvent",
        self::WEBHOOK_UPDATE_EVENT => "UserUpdatesEvent",
        self::WEBHOOK_DELETE_EVENT => "UserDeletesEvent",
    );

    /**
     * The top object of a webhook.
     *
     * @var stdClass
     */
    protected $webhook;

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
     * Determines if the webhook should be sent or not.
     *
     * @var integer
     *
     * @see WEBHOOK_SEND
     * @see WEBHOOK_CANCEL
     */
    protected $send = self::WEBHOOK_SEND;

    /**
     * The method used for Client authentication.
     *
     * @var integer
     *
     * @see Webhook::AUTHENTICATION_NONE
     * @see Webhook::AUTHENTICATION_BASIC
     * @see Webhook::AUTHENTICATION_OAUTH
     */
    protected $authentication = Webhook::AUTHENTICATION_NONE;

    /**
     * The method of data transmission.
     *
     * This establishes the method of transmission between the AllPlayers
     * webhook and the third-party webhook.
     *
     * @var string
     *
     * @see Webhook::TRANSMISSION_URLENCODED
     * @see Webhook::TRANSMISSION_JSON
     */
    protected $method = Webhook::TRANSMISSION_JSON;

    /**
     * Initialize the webhook object.
     *
     * @param array $subscriber
     *   The Subscriber variable provided by the Resque Job.
     * @param array $data
     *   The Event Data variable provided by the Resque Job.
     * @param array $preprocess
     *   Additional data used for pre-processing, defined in PostWebhooks.
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
     * This function is not abstract due to the possibility that many partners
     * will need to use either basic_auth or oauth; those who do not can have a
     * custom auth definition here.
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
     * Perform processing on the webhook before preparing to send it.
     *
     * @param array $data
     *   An array of data to be processed before the webhook data is sent.
     */
    protected function preprocess(array $data)
    {
        // Set the redirect url, so we can swap domains before sending the data.
        if (isset($data['test_url']) && $data['test_url'] != '') {
            $this->test_domain = $data['test_url'];
        }
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
     * Get the webhook send flag.
     *
     * @return integer
     *
     * @see WEBHOOK_SEND
     * @see WEBHOOK_CANCEL
     */
    public function getSend()
    {
        return $this->send;
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
     * Wrapper function to redirect the webhook if testing is enabled.
     */
    protected function setDomain()
    {
        // Swap the destination and add the original URL to the webhook payload.
        if (isset($this->test_domain) && $this->test_domain != '') {
            $this->webhook->data['original_url'] = $this->domain;
            $this->client->setBaseUrl($this->test_domain);
        } else {
            $this->client->setBaseUrl($this->domain);
        }
    }

    /**
     * Set the webhook send flag.
     *
     * @param integer $send
     *   The send value to set.
     *
     * @see WEBHOOK_SEND
     * @see WEBHOOK_CANCEL
     */
    protected function setSend($send)
    {
        $this->send = $send;
    }

    /**
     * Makes a POST request to send to the external service.
     *
     * @return \Guzzle\Http\Message\Request
     *   Returns the Guzzle request object, ready to send.
     */
    protected function post()
    {
        $this->setDomain();

        // Encode the webhook data using the requested method.
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

        // Encode the webhook data using the requested method.
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

        // Encode the webhook data using the requested method.
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
     * @return \Guzzle\Http\Message\Response
     *   The Guzzle response object with data about the request.
     */
    public function send()
    {
        // Send an additional debug request if enabled.
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
}
