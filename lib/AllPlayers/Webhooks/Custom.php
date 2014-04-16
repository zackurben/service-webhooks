<?php
/**
 * @file Custom.php
 *
 * Provides the Custom Webhooks plugin definition. The Custom Webhook is a
 * simplex Webhook for sending all data to a single, non-authenticated, url.
 */

namespace AllPlayers\Webhooks;

/**
 * Base Custom Webhook definition, to send data to a custom URL.
 */
class Custom extends Webhook
{
	/**
	 * The URL to post the webhook
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
	 * The method of data transmission.
	 *
	 * @var string
	 */
	public $method = 'json';

	/**
	 * Use custom url as domain.
	 */
	public function __construct(array $subscriber = array(), array $data = array(), array $preprocess = array())
	{
		$this->domain = $subscriber['url'];
		parent::__construct($subscriber, $data, $preprocess);
		$this->process();
	}
	
	/**
	 * Process the webhook data and set the domain to the appropriate URL
	 */
	public function process()
	{
		/**
		 * Set domain to custom url.
		 *
		 * Do no processing here, because this is a simplex webhook that dumps
		 * all raw data to a single URL.
		 */
		$this->domain = $this->webhook->subscriber['url'];
		 
		parent::post();
	}
}
