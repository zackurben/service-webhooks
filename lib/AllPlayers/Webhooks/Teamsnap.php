<?php
/**
 * @file
 * Provides the Teamsnap webhooks plugin definition.
 */

namespace AllPlayers\Webhooks;

/**
 * Defines teamsnap app that will push events to external test app.
 */
class Teamsnap extends Webhook
{
    /**
     * The URL to post the webhook.
     *
     * @var string
     */
    public $domain = 'https://api.teamsnap.com/v2';

    /**
     * The authentication method used in the post requests.
     *
     * @var string
     */
    public $authentication = 'teamsnap_auth';

    /**
     * Authenticate using basic auth.
     */
    public function __construct(array $subscriber = array(), array $data = array())
    {
        parent::__construct(array('user' => $subscriber['user'], 'pass' => $subscriber['token']), $data);
    }
	
	/**
	 * Process the webhook and set the domain to the appropriate URL
	 */
	public function process()
	{
		$webhook = $this->getWebhook();
		switch($webhook['webhook_type'])
		{
			case "user_creates_group":
				$this->domain .= '/teams';
				break;
			case "user_adds_role":
				$this->domain .= '';
				break;
		}
	}
}
