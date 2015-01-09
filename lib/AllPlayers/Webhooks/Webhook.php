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
use Guzzle\Http\Plugin\CurlAuthPlugin;
use Guzzle\Http\Plugin\OauthPlugin;

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
     * An enumerated value to confirm sending the request.
     *
     * @var integer
     */
    const WEBHOOK_SEND = 7;

    /**
     * An enumerated value to cancel sending the request.
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
     * The URL to send the request to.
     *
     * @var string
     */
    protected $domain;

    /**
     * The URL to redirect request data, if testing.
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
     * Determines if the request should be sent or not.
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
     * webhook and the third-party request.
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
     */
    public function __construct(
        array $subscriber = array(),
        array $data = array()
    ) {
        $this->webhook = new \stdClass();
        $this->setSubscriber($subscriber);
        $this->setRequestData($data);

        $this->client = new Client($this->domain);
        if ($this->authentication != self::AUTHENTICATION_NONE) {
            $this->authenticate();
        }
        $this->preprocess();
    }

    /**
     * Authenticate client based on the request authentication method.
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
     * Perform processing on the webhook before preparing to send requests.
     */
    protected function preprocess()
    {
        $config = null;
        include __DIR__ . '/../../../resque/config/config.php';

        // Check if the test_url was defined.
        $config_url = (array_key_exists('test_url', $config)
            && $config['test_url'] != '');

        // Get the name of the webhook processor and check if we should send.
        $webhook_processor = explode('\\', get_class($this));
        $webhook_processor = strtolower($webhook_processor[2]);

        // Check if the webhook processor is defined in the config.
        $config_dev = array_key_exists($webhook_processor, $config);

        // Get the send settings for the partner.
        if ($config_dev) {
            $data = $this->getRequestData();
            if (isset($data['group']['organization_id'][0]) && array_key_exists(
                $data['group']['organization_id'][0],
                $config[$webhook_processor]
            )) {
                // Use the send setting for the partner and organization.
                $config_dev = !$config[$webhook_processor][$data['group']['organization_id'][0]]['send'];
            } else {
                // The organization was not set, default to partner settings.
                $config_dev = !$config[$webhook_processor]['default']['send'];
            }
        }

        // Set the redirect url, so we can swap domains before sending the data.
        if ($config_url && !$config_dev) {
            $this->test_domain = $config['test_url'];
        }
    }

    /**
     * Get the webhook data from this webhook object.
     *
     * @return stdClass
     *   The Webhook data of this webhook object.
     */
    public function getWebhook()
    {
        return $this->webhook;
    }

    /**
     * Get the data from the original AllPlayers Webhook.
     *
     * @return array
     *   The data from the original AllPlayers Webhook.
     */
    public function getAllplayersData()
    {
        return $this->webhook->allplayers_data;
    }

    /**
     * Get the data that will be sent in the request.
     *
     * @return array
     *   Returns the data to be transmitted in the post request.
     */
    public function getRequestData()
    {
        return $this->webhook->request_data;
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
     * Get the request send flag.
     *
     * @return integer
     *   The Webhook constant for send status.
     *
     * @see WEBHOOK_SEND
     * @see WEBHOOK_CANCEL
     */
    public function getSend()
    {
        return $this->send;
    }

    /**
     * Confirm that the request is supposed to be sent.
     *
     * @return bool
     *   Whether to send the request or not.
     */
    public function checkSend()
    {
        if ($this->getSend() != self::WEBHOOK_SEND) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get the request authentication type.
     *
     * @return int
     *
     * @see AUTHENTICATION_NONE
     * @see AUTHENTICATION_BASIC
     * @see AUTHENTICATION_OAUTH
     */
    public function getAuthentication()
    {
        return $this->authentication;
    }

    /**
     * Get the request transmission method.
     *
     * @return int
     *
     * @see TRANSMISSION_URLENCODED
     * @see TRANSMISSION_JSON
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Get the domain of the request.
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Get the array of headers for the request.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Get the array of api headers for the api requests.
     *
     * @return array
     */
    public function getApiHeaders()
    {
        return $this->api_headers;
    }

    /**
     * Get the Guzzle request object for the webhook.
     *
     * @return \Guzzle\Http\Message\EntityEnclosingRequest
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set the subscriber for the webhook.
     *
     * @param array $subscriber
     *   The subscriber data from the Resque_Job.
     */
    public function setSubscriber(array $subscriber)
    {
        $this->webhook->subscriber = $subscriber;
    }

    /**
     * Set the data that will be sent in the request.
     *
     * @param array $data
     *   The data to send in the Guzzle request.
     */
    protected function setRequestData(array $data)
    {
        $this->webhook->request_data = $data;
    }

    /**
     * Store the data from the AllPlayers webhook.
     *
     * @param array $data
     *   Set the original webhook data, used in processing and making requests.
     */
    protected function setAllplayersData(array $data)
    {
        $this->webhook->allplayers_data = $data;
    }

    /**
     * Wrapper function to redirect the request if testing is enabled.
     */
    protected function setDomain()
    {
        include __DIR__ . '/../../../resque/config/config.php';

        // Redirect if we are in test env and we have a test domain.
        if (isset($config['test_env'], $this->test_domain) && $config['test_env'] && $this->test_domain != '') {
            $this->webhook->request_data['original_url'] = $this->domain;
            $this->client->setBaseUrl($this->test_domain);
        } else {
            $this->client->setBaseUrl($this->domain);
        }
    }

    /**
     * Set the request send flag.
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
     * Update the request type to be a POST request.
     */
    public function post()
    {
        $this->setDomain();

        // Encode the webhook data using the requested method.
        if ($this->method === self::TRANSMISSION_URLENCODED) {
            $this->request = $this->client->post(
                $this->client->getBaseUrl(),
                $this->headers
            );
            $this->request->addPostFields($this->getRequestData());
        } else {
            $this->headers['Content-Type'] = 'application/json';
            $this->request = $this->client->post(
                $this->client->getBaseUrl(),
                $this->headers,
                json_encode($this->getRequestData())
            );
        }
    }

    /**
     * Update the request type to be a PUT request.
     */
    public function put()
    {
        $this->setDomain();

        // Encode the webhook data using the requested method.
        if ($this->method === self::TRANSMISSION_URLENCODED) {
            $this->request = $this->client->put(
                $this->client->getBaseUrl(),
                $this->headers
            );
            $this->request->addPostFields($this->getRequestData());
        } else {
            $this->headers['Content-Type'] = 'application/json';
            $this->request = $this->client->put(
                $this->client->getBaseUrl(),
                $this->headers,
                json_encode($this->getRequestData())
            );
        }
    }

    /**
     * Update the request type to be a DELETE request.
     */
    public function delete()
    {
        $this->setDomain();

        // Encode the webhook data using the requested method.
        if ($this->method === self::TRANSMISSION_URLENCODED) {
            $this->request = $this->client->delete(
                $this->client->getBaseUrl(),
                $this->headers
            );
            $this->request->addPostFields($this->getRequestData());
        } else {
            $this->headers['Content-Type'] = 'application/json';
            $this->request = $this->client->delete(
                $this->client->getBaseUrl(),
                $this->headers,
                json_encode($this->getRequestData())
            );
        }
    }

    /**
     * Send the prepared Guzzle request to the external service.
     *
     * @return \Guzzle\Http\Message\Response|null
     *   The Guzzle response object with data about the request.
     */
    public function send()
    {
        // Send an additional debug request if enabled.
        include __DIR__ . '/../../../resque/config/config.php';
        if ($this->getSend() == self::WEBHOOK_SEND) {
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
        } else {
            return null;
        }
    }
}
