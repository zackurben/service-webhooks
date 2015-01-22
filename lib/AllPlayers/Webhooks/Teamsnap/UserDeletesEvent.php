<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Teamsnap/UserDeletesEvent.
 */

namespace AllPlayers\Webhooks\Teamsnap;

use AllPlayers\Webhooks\ProcessInterface;
use Guzzle\Http\Message\Response;

/**
 * The unique TeamSnap implementation of the user_deletes_event webhook.
 */
class UserDeletesEvent extends SimpleWebhook implements ProcessInterface
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

        // Determine if this is a game or an event, and remove its resources.
        if (isset($data['event']['competitor'])
            && !empty($data['event']['competitor'])
        ) {
            $this->deleteGame();
        } else {
            $this->deletePractice();
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
        // Get the original data sent from the AllPlayers webhook.
        $original_data = $this->getAllplayersData();

        // Delete the partner-mapping with an event UUID.
        $this->partner_mapping->deleteEvent(
            $original_data['event']['uuid'],
            $original_data['group']['uuid']
        );
    }

    /**
     * Deletes all the resources on TeamSnap/AllPlayers for a game event.
     */
    private function deleteGame()
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getAllplayersData();

        // Store the base domain to revert back to for each iteration.
        $original_domain = $this->domain;

        foreach ($data['event']['competitor'] as $group) {
            // Get the TeamID from the partner-mapping API.
            $team = $this->partner_mapping->getTeamId($group['uuid']);
            if (isset($team['external_resource_id'])) {
                $team = $team['external_resource_id'];
            } else {
                // If the team is null, requeue this webhook, for another
                // attempt, and discard after maximum number of attempts.
                $this->requeueWebhook();
                return;
            }

            // Get the EventID from the partner-mapping API.
            $event = $this->partner_mapping->getEventId(
                $data['event']['uuid'],
                $group['uuid']
            );
            if (isset($event['external_resource_id'])) {
                $event = $event['external_resource_id'];
            } else {
                // Skip this request because the Event was not found.
                continue;
            }

            // Update the domain to delete a game event.
            $this->domain = $original_domain . '/teams/' . $team . '/as_roster/'
                . $this->webhook->subscriber['commissioner_id'] . '/games/'
                . $event;

            // Update the request type.
            parent::delete();

            // Manually send the request
            $this->send();

            // Reset the temp variables for the next iteration.
            $this->domain = $original_domain;
        }

        // Cancel this primary request (manually processed).
        parent::setSend(parent::WEBHOOK_CANCEL);
    }

    /**
     * Deletes all the resources on TeamSnap/AllPlayers for a non-game event.
     */
    private function deletePractice()
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getAllplayersData();

        // Get the TeamID from the partner-mapping API.
        $team = $this->partner_mapping->getTeamId($data['group']['uuid']);
        if (isset($team['external_resource_id'])) {
            $team = $team['external_resource_id'];
        } else {
            // If the team is null, requeue this webhook, for another attempt,
            // and discard after maximum number of attempts.
            $this->requeueWebhook();
            return;
        }

        // Get the EventID from the partner-mapping API.
        $event = $this->partner_mapping->getEventId(
            $data['event']['uuid'],
            $data['group']['uuid']
        );
        if (isset($event['external_resource_id'])) {
            $event = $event['external_resource_id'];
        } else {
            // Skip this request because the Event was not found.
            parent::setSend(parent::WEBHOOK_CANCEL);
            return;
        }

        // Update the domain to delete a game event.
        $this->domain .= '/teams/' . $team . '/as_roster/'
            . $this->webhook->subscriber['commissioner_id'] . '/practices/'
            . $event;

        // Update the request type.
        parent::delete();
    }
}
