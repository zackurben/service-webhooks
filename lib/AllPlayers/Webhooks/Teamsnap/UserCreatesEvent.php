<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Teamsnap/UserCreatesEvent.
 */

namespace AllPlayers\Webhooks\Teamsnap;

use AllPlayers\Webhooks\ProcessInterface;
use Guzzle\Http\Message\Response;

/**
 * The unique TeamSnap implementation of the user_creates_event webhook.
 */
class UserCreatesEvent extends SimpleWebhook implements ProcessInterface
{
    /**
     * Process the webhook data and manage the partner-mapping API calls.
     */
    public function process()
    {
        parent::process();

        // Stop processing if this webhook isn't being sent.
        $send = $this->checkSend();
        if (!$send) {
            return;
        }

        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getAllplayersData();

        // Build the request payload.
        $send = array();
        $this->addEvent($send);
        $this->addEventLocation($send);
        $this->addEventDescription($send);

        // Determine if this is a game or an event.
        if (isset($data['event']['competitor'])
            && !empty($data['event']['competitor'])
        ) {
            $this->createGame($send);
        } else {
            $this->createPractice($send);
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
        // Convert the raw Guzzle Response object to an array.
        $response = $this->helper->processJsonResponse($response);

        // Get the original data sent from the AllPlayers webhook.
        $original_data = $this->getAllplayersData();

        // Create/Update the event in the partner-mapping API.
        $this->partner_mapping->setEventId(
            $response['practice']['id'],
            $original_data['event']['uuid'],
            $original_data['group']['uuid']
        );
    }

    /**
     * Finish processing the event as a game for TeamSnap.
     *
     * @param $send
     *   The array to append new event data.
     */
    protected function createGame(&$send)
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getAllplayersData();

        // Store the core variables for each iteration of adding competitors.
        $original_domain = $this->domain;
        $original_send = $send;

        // Iterate each available competitor and send a request to TeamSnap to
        // create the mapping for each competitor to the game event.
        foreach ($data['event']['competitor'] as $group) {
            // Check if the event already exists.
            $event = $this->partner_mapping->getEventId(
                $data['event']['uuid'],
                $group['uuid']
            );
            if (isset($event['external_resource_id'])) {
                // Skip creating this event because it already exists, it is not
                // applicable to update here because the event was just made in
                // another request (this is the other teams webhook event).
                continue;
            }

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

            // Attach the opponent for the event.
            $this->addEventOpponent($send, $team, $group);

            // Attach the scores to the event if they are present.
            $this->addEventScores($send, $group);

            // Update the domain to make a game event.
            $this->domain = $original_domain . '/teams/' . $team . '/as_roster/'
                . $this->webhook->subscriber['commissioner_id'] . '/games';

            // Set the payload data.
            $this->setRequestData(array('game' => $send));

            // Update the request type.
            parent::post();

            // Manually send the request and process the response.
            $response = $this->send();
            $response = $this->helper->processJsonResponse($response);

            // Manually create an event partner-mapping.
            $this->partner_mapping->setEventId(
                $response['game']['id'],
                $data['event']['uuid'],
                $group['uuid']
            );

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
    protected function createPractice(&$send)
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

        // Update the domain to make a practice event.
        $this->domain .= '/teams/' . $team . '/as_roster/'
            . $this->webhook->subscriber['commissioner_id'] . '/practices';

        // Set the payload data.
        $this->setRequestData(array('practice' => $send));

        // Update the request type and let PostWebhooks complete.
        parent::post();
    }
}
