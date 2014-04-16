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
		$this->preprocess($this->webhook->subscriber['url']);
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
		if($this->method === 'form-urlencoded')
		{
			$this->request = $this->client->post($this->webhook->subscriber['url'], $this->headers);
			$this->request->addPostFields($this->webhook['data']);
		}
		else
		{
			$this->request = $this->client->post($this->webhook->subscriber['url'], $this->headers, $this->webhook['data']);
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
		if($this->method === 'form-urlencoded')
		{
			$this->request = $this->client->put($this->webhook->subscriber['url'], $this->headers);
			$this->request->addPostFields($this->webhook['data']);
		}
		else
		{
			$this->request = $this->client->put($this->webhook->subscriber['url'], $this->headers, $this->webhook['data']);
		}
	}
	
	/**
	 * Perform any additional processing on the webhook before sending it.
	 * This is to avoid passing multiple parameters to the constructor,
	 * and is called before send().
	 */
	public function preprocess($url)
	{
		if(isset($url) && $url != '')
		{
			$this->webhook['data']['original_url'] = $this->webhook['domain'];
			$this->webhook['domain'] = $url;
		}	
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
