<?php
/**
 * @file Custom.php
 *
 * Provides the Custom Webhooks plugin definition. The Custom Webhook is a
 * simpleton Webhook for sending all data to a single, non-authenticated, url.
 */

namespace AllPlayers\Webhooks;

/**
 * Base Custom Webhook definition, to send data to a custom URL.
 */
class Custom extends Webhook
{
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
     * Use custom url as domain.
     */
    public function __construct(array $subscriber = array(), array $data = array())
    {
		$this->domain = $subscriber['url'];
		parent::__construct($subscriber, $data);
    }
}
