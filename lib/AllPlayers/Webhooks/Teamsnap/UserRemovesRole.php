<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Teamsnap/UserRemovesRole.
 */

namespace AllPlayers\Webhooks\Teamsnap;

use AllPlayers\Webhooks\ProcessInterface;
use Guzzle\Http\Message\Response;

/**
 * The unique TeamSnap implementation of the user_removes_role webhook.
 */
class UserRemovesRole extends SimpleWebhook implements ProcessInterface
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

        // Get the RosterID from the partner-mapping API.
        $roster = $this->partner_mapping->getRosterId(
            $data['member']['uuid'],
            $data['group']['uuid']
        );
        if (isset($roster['external_resource_id'])) {
            $roster = $roster['external_resource_id'];
        } else {
            // @TODO: We need to confirm this entity does not exist on TeamSnap.
            // Skip this request because the User was not found.
            parent::setSend(parent::WEBHOOK_CANCEL);
            return;
        }

        // Get the TeamID from the partner-mapping API.
        $team = $this->partner_mapping->getTeamId($data['group']['uuid']);
        if (isset($team['external_resource_id'])) {
            $team = $team['external_resource_id'];
        } else {
            // @TODO: We need to confirm this entity does not exist on TeamSnap.
            // Skip this request because the Team was not found.
            parent::setSend(parent::WEBHOOK_CANCEL);
            return;
        }

        // Build the request payload.
        $send = array();
        $this->addRosterRoleRemoved($send);
        $send = array('roster' => $send);

        // Update the payload domain.
        $this->domain .= '/teams/' . $team . '/as_roster/'
            . $this->webhook->subscriber['commissioner_id'] . '/rosters/'
            . $roster;

        // Set the payload data.
        $this->setRequestData($send);

        // Only continue if update data is present.
        if (empty($send)) {
            // Skip this request because the role removed is not supported.
            $this->setSend(self::WEBHOOK_CANCEL);
        } else {
            // Update the request type and let PostWebhooks complete.
            parent::put();
        }
    }

    /**
     * Process the payload response and manage the partner-mapping API calls.
     *
     * @param \Guzzle\Http\Message\Response $response
     *   Response from the webhook being processed/called.
     */
    public function processResponse(Response $response)
    {

    }
}
