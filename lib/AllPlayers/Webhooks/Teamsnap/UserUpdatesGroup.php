<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Teamsnap/UserUpdatesGroup.
 */

namespace AllPlayers\Webhooks\Teamsnap;

use AllPlayers\Webhooks\ProcessInterface;
use Guzzle\Http\Message\Response;

/**
 * The unique TeamSnap implementation of the user_updates_group webhook.
 */
class UserUpdatesGroup extends SimpleWebhook implements ProcessInterface
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

        // Get the TeamID from the partner-mapping API.
        $team = $this->partner_mapping->getTeamId($data['group']['uuid']);
        if (isset($team['external_resource_id'])) {
            $team = $team['external_resource_id'];
        } else {
            // This can occur for a few reasons: The queue is backed up, the
            // group webhook is being processed by another worker, or the team
            // exists on TeamSnap but not in the partner-mapping API.

            // TODO: If the team is null, requeue this webhook, for another
            // TODO: attempt, and discard after maximum number of attempts.
            // Skip this request because the Team was not found.
            parent::setSend(parent::WEBHOOK_CANCEL);
            return;
        }

        // Build the request payload.
        $send = array();
        $this->addTeam($send);
        $this->addTeamRegion($send);
        $this->addTeamSport($send);
        $this->addTeamLogo($send);

        // Update the payload domain.
        $this->domain .= '/teams/' . $team;

        // Set the payload data.
        $this->setRequestData(array('team' => $send));

        // Update the request type.
        parent::put();
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
