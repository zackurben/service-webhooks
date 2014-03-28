<?php
/**
 * @file
 *
 * Provides the Custom webhooks plugin definition.
 */

namespace AllPlayers\Webhooks;

/**
 * Defines a custom url webhook that will push events to an external app.
 */
class Custom extends Webhook
{
    /**
     * The URL of the webhook.
     *
     * @var string
     */
    public $domain;

    /**
     * The authentication method used in the post request.
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
    }
}
