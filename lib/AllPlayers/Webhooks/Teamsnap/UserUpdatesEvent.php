<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Teamsnap/UserUpdatesEvent.
 */

namespace AllPlayers\Webhooks\Teamsnap;

use AllPlayers\Webhooks\ProcessInterface;
use Guzzle\Http\Message\Response;

/**
 * The unique TeamSnap implementation of the user_updates_event webhook.
 */
class UserUpdatesEvent extends SimpleWebhook implements ProcessInterface
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

        // Build the request payload.
        $send = array();
        $this->addEvent($send);
        $this->addEventDescription($send);
        $this->addEventLocation($send);

        // Determine if this is a game or an event.
        if (isset($data['event']['competitor'])
            && !empty($data['event']['competitor'])
        ) {
            $this->updateGame($send);
        } else {
            $this->updatePractice($send);
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

    /**
     * Finish processing the event as a game for TeamSnap.
     *
     * @param $send
     *   The array to append new event data.
     */
    private function updateGame(&$send)
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getAllplayersData();

        // Store the core variables for each iteration of updating events.
        $original_domain = $this->domain;
        $original_send = $send;

        // Iterate each available competitor and send a request to TeamSnap to
        // update the mapping for each competitor to the game event.
        foreach ($data['event']['competitor'] as $group) {
            // Get the TeamID from the partner-mapping API.
            $team = $this->partner_mapping->getTeamId($group['uuid']);
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
                $group['uuid']
            );
            if (isset($event['external_resource_id'])) {
                $event = $event['external_resource_id'];
            } else {
                // The event does not exist, cancel this webhook and create it.
                $this->changeWebhook(self::WEBHOOK_CREATE_EVENT);
                return;
            }

            // Attach the opponent for the event.
            $this->addEventOpponent($send, $team, $group);

            // Attach the scores to the event if they are present.
            $this->addEventScores($send, $group);

            // Update the domain to update our event.
            $this->domain = $original_domain . '/teams/' . $team . '/as_roster/'
                . $this->webhook->subscriber['commissioner_id'] . '/games/'
                . $event;

            // Set the payload data.
            $this->setRequestData(array('game' => $send));

            // Update the request type.
            parent::put();

            // Manually send the request.
            $this->send();

            // Reset the temp variables for the next iteration.
            $this->domain = $original_domain;
            $send = $original_send;
        }


        // Cancel this primary request (manually processed).
        parent::setSend(parent::WEBHOOK_CANCEL);
    }

    /**
     * Finish processing the event as a practice for TeamSnap.
     *
     * @param $send
     *   The array to append new event data.
     */
    private function updatePractice(&$send)
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

        // Get EventID from the partner-mapping API.
        $event = $this->partner_mapping->getEventId(
            $data['event']['uuid'],
            $data['group']['uuid']
        );
        if (isset($event['external_resource_id'])) {
            $event = $event['external_resource_id'];
        } else {
            // The event does not exist, cancel this request and create it.
            $this->changeWebhook(self::WEBHOOK_CREATE_EVENT);
            return;
        }

        // Update the request and let PostWebhooks complete.
        $this->domain .= '/teams/' . $team . '/as_roster/'
            . $this->webhook->subscriber['commissioner_id'] . '/practices/'
            . $event;

        // Set the payload data.
        $this->setRequestData(array('practice' => $send));

        // Update the request type.
        parent::put();
    }
}
