<?php
/**
 * @file Teamsnap.php
 *
 * Provides the TeamSnap Webhooks plugin definition. The TeanSnap Webhook sends
 * data to various API Endpoints, using a custom token based authentication.
 */

namespace AllPlayers\Webhooks;

/**
 * Base TeamSnap Webhook definition.
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
     * Authenticate using teamsnap_auth.
     */
    public function __construct(array $subscriber = array(), array $data = array())
    {
        parent::__construct(array('user' => $subscriber['user'], 'pass' => $subscriber['token']), $data);
		$this->process();
    }
	
	/**
	 * Process the webhook data and set the domain to the appropriate URL
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
