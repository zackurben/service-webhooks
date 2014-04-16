<?php
/**
 * @file Quickscores.php
 *
 * Provides the Quickscores Webhooks plugin definition. The Quickscores Webhook
 * sends AllPlayers hook data to a single endpoint for processing the data;
 * this webhook uses basic authentication.
 */

namespace AllPlayers\Webhooks;

/**
 * Base Quickscores Webhook definition.
 */
class Quickscores extends Webhook
{
	/**
	 * The URL to post the webhook.
	 *
	 * @var string
	 */
	public $domain = 'http://www.quickscores.com/API/SynchEvents.php';

	/**
	 * The authentication method used in the post requests.
	 *
	 * @var string
	 */
	public $authentication = 'basic_auth';
	
	/**
	 * The method of data transmission.
	 *
	 * @var string
	 */
	public $method = 'form-urlencoded';

	/**
	 * Authenticate using basic auth.
	 */
	public function __construct(array $subscriber = array(), array $data = array(), array $preprocess = array())
	{
		parent::__construct(array('user' => $subscriber['user'], 'pass' => $subscriber['token']), $data, $preprocess);
		$this->process();
	}
	
	/**
	 * Process the webhook and set the domain to the appropriate URL
	 */
	public function process()
	{
		/**
		 * Do nothing here because, QuickScores has a single API endpoint for
		 * processing our data. 
		 */
		parent::post();
	}
}
