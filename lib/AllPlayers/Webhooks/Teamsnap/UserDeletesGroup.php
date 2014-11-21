<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Teamsnap/UserDeletesGroup.
 */

namespace AllPlayers\Webhooks\Teamsnap;

use AllPlayers\Webhooks\ProcessInterface;
use AllPlayers\Utilities\PartnerMap;
use Guzzle\Http\Message\Response;

/**
 * The unique TeamSnap implementation of the user_deletes_group webhook.
 */
class UserDeletesGroup extends SimpleWebhook implements ProcessInterface
{
    /**
     * Process the webhook data and manage the partner-mapping API calls.
     */
    public function process()
    {
        parent::process();

        // Cancel the continued processing of this webhook, if this was canceled
        // in the parent:process() call.
        if ($this->getSend() != \AllPlayers\Webhooks\Webhook::WEBHOOK_SEND) {
            return;
        }

        // Get the data from the AllPlayers webhook.
        $data = $this->getData();

        // Get TeamID from partner-mapping API.
        $team = $this->partner_mapping->readPartnerMap(
            PartnerMap::PARTNER_MAP_GROUP,
            $data['group']['uuid'],
            $data['group']['uuid']
        );
        $team = $team['external_resource_id'];
        $this->domain .= '/teams/' . $team;

        // Update the request and let PostWebhooks complete.
        $this->headers['X-Teamsnap-Features'] = '{"partner.delete_team": 1}';
        parent::delete();
    }

    /**
     * Process the payload response and manage the partner-mapping API calls.
     *
     * @param \Guzzle\Http\Message\Response $response
     *   Response from the webhook being processed/called.
     */
    public function processResponse(Response $response)
    {
        $response = $this->helper->processJsonResponse($response);

        // Get the original data sent from the AllPlayers webhook.
        $original_data = $this->getOriginalData();

        // Delete partner-mapping with the group UUID.
        $this->partner_mapping->deletePartnerMap(
            null,
            $original_data['group']['uuid']
        );
    }
}
