<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Teamsnap/UserAddsRole.
 */

namespace AllPlayers\Webhooks\Teamsnap;

use AllPlayers\Webhooks\ProcessInterface;
use Guzzle\Http\Message\Response;

/**
 * The unique TeamSnap implementation of the user_removed_from_group webhook.
 */
class UserRemovedFromGroup extends SimpleWebhook implements ProcessInterface
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
            // If the team is null, requeue this webhook, for another attempt,
            // and discard after maximum number of attempts.
            $this->requeueWebhook();
            return;
        }

        // Get the RosterID from the partner-mapping API.
        $roster = $this->partner_mapping->getRosterId(
            $data['member']['uuid'],
            $data['group']['uuid']
        );
        if (isset($roster['external_resource_id'])) {
            $roster = $roster['external_resource_id'];
        } else {
            // Skip this request because the User was not found.
            parent::setSend(parent::WEBHOOK_CANCEL);
            return;
        }

        // Update the payload domain.
        $this->domain .= '/teams/' . $team . '/as_roster/'
            . $this->webhook->subscriber['commissioner_id'] . '/rosters/'
            . $roster;

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

        // Delete the User from the partner-mapping API.
        $this->partner_mapping->deleteUser(
            $original_data['member']['uuid'],
            $original_data['group']['uuid']
        );

        // Delete the Users Email from the partner-mapping API.
        $this->partner_mapping->deleteUserEmail(
            $original_data['member']['uuid'],
            $original_data['group']['uuid']
        );
    }
}
