<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Teamsnap/UserDeletesGroup.
 */

namespace AllPlayers\Webhooks\Teamsnap;

use AllPlayers\Webhooks\ProcessInterface;
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

        // Stop processing if this request isn't being sent.
        $send = $this->checkSend();
        if (!$send) {
            return;
        }

        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getAllplayersData();

        // Get the TeamID from partner-mapping API.
        $team = $this->partner_mapping->getTeamId($data['group']['uuid']);
        if (isset($team['external_resource_id'])) {
            $team = $team['external_resource_id'];
        } else {
            // Skip this request because the Team was not found.
            parent::setSend(parent::WEBHOOK_CANCEL);
            return;
        }

        // Update the payload domain.
        $this->domain .= '/teams/' . $team;

        // Update the request headers.
        $this->headers['X-Teamsnap-Features'] = '{"partner.delete_team": 1}';

        // Update the request type and let PostWebhooks complete.
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
        // Get the original data sent from the AllPlayers webhook.
        $original_data = $this->getAllplayersData();

        // Delete partner-mapping with the group UUID.
        $this->partner_mapping->deleteGroup($original_data['group']['uuid']);
    }
}
