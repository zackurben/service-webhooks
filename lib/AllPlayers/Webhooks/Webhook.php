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
	 *   ['data'] => Webhook data.
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
     * The authentication method used in the post requests.
     *
     * @var string
     */
    public $authentication = 'no_authentication';

	/**
	 * Force the processing of the Webhook to be defined. If no processing is
	 * needed, this function can remain empty; this is where the send data should
	 * be manipulated, as well as the URL to send the data.
	 */
	abstract public function process();

    /**
     * Initialize the webhook object.
     *
     * @param array $subscriber
     *   The parameters (subscriber variables) for the request.
	 *
	 * @param array $data
	 *   The event data from the webhook.
     */
    public function __construct(array $subscriber = array(), array $data = array())
    {
        $this->webhook->subscriber = $subscriber;
		$this->webhook->data = $data;

        $this->client = new Client($this->domain);
        if ($this->authentication != 'no_authentication')
		{
            $this->authenticate();
        }
    }

    /**
     * Authenticate client based on the webhooks authentication method.
	 *
	 * This function is not abstrat due to the possibility that many partners will
	 * need to use either basic_auth or oauth; those who do not can have a custom
	 * auth definition here.
     */
    public function authenticate()
    {
        switch ($this->authentication)
		{
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
			case 'teamsnap_auth':
				// Custom auth for TeamSnap, TODO make more generic.
				// make post to login to get auth token for following requests.
				/*$response = $this->client->post('/authentication/login', array(),
					array('auth' =>
						array(
							$this->webhook->subscriber['user'], 
							$this->webhook->subscriber['pass'],
						)
					)
				)->send();
				
				// set 'X-Teamsnap-Token' header to response, add to default calls
				if($response->isSuccessful())
				{
					//$this->client->setDefaultOption('headers', array('X-Teamsnap-Token' => $response->getHeader('X-Teamsnap-Token')));
					array_push($this->headers, array('X-Teamsnap-Token' => $response->getHeader('X-Teamsnap-Token')));
				}*/
				$auth = array (
					'token' => $this->webhook->subscriber['token'],
					'commissioner_id' => $this->webhook->subscriber['commissioner_id'],
					'division_id' => $this->webhook->subscriber['division_id'],
				);
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
		return $this->webhook['data'];
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
     * @return \Guzzle\Http\Message\Request
     *   Returns the service request object.
     */
    public function post()
    {
        $this->request = $this->client->post($this->webhook->subscriber['url'], $this->headers, $this->webhook['data']);
    }
	
	/**
	 * Makes a PUT request to the external service
	 *
     * @return \Guzzle\Http\Message\Request
     *   Returns the service request object.
	 */
	public function put()
	{
		$this->request = $this->client->post($this->webhook->subscriber['url'], $this->headers, $this->webhook['data']);
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
        return $this->request->send();
    }
}
