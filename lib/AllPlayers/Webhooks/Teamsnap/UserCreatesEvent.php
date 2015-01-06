<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Teamsnap/UserCreatesEvent.
 */

namespace AllPlayers\Webhooks\Teamsnap;

use AllPlayers\Webhooks\ProcessInterface;
use AllPlayers\Utilities\PartnerMap;
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

        // Get the data from the AllPlayers webhook.
        $data = $this->getData();

        // Build the webhook payload.
        $send = $this->addTeamsnapEventData();

        // Determine if this is a game or an event.
        if (isset($data['event']['competitor']) && !empty($data['event']['competitor'])) {
            $this->finishGame($send);
        } else {
            $this->finishPractice($send);
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
        $response = $this->helper->processJsonResponse($response);

        // Get the original data sent from the AllPlayers webhook.
        $original_data = $this->getOriginalData();

        // Associate an AllPlayers event UUID with a TeamSnap EventID.
        $this->partner_mapping->createPartnerMap(
            $response['practice']['id'],
            PartnerMap::PARTNER_MAP_EVENT,
            $original_data['event']['uuid'],
            $original_data['group']['uuid']
        );
    }

    /**
     * Build the webhook payload and manage the partner-mapping API calls.
     *
     * @return array
     *   The event data to send to create an event resource on TeamSnap.
     */
    protected function addTeamsnapEventData()
    {
        // Get the data from the AllPlayers webhook.
        $data = $this->getData();

        // Build the initial payload information.
        $send = array(
            'eventname' => $data['event']['title'],
            'division_id' => $this->webhook->subscriber['division_id'],
            'event_date_start' => $data['event']['start'],
            'event_date_end' => $data['event']['end'],
        );

        // Add additional information if present.
        $this->addLocation($send);
        $this->addNotes($send);

        return $send;
    }

    /**
     * Add the TeamSnap LocationID to the event creation payload.
     *
     * @param array $send
     *   The array to append new event data.
     */
    protected function addLocation(&$send)
    {
        // Get the data from the AllPlayers webhook.
        $data = $this->getData();

        // Make/get a location resource.
        $location = $this->getLocationResource(
            $data['group']['uuid'],
            isset($data['event']['location']) ? $data['event']['location'] : null
        );

        $send['location_id'] = $location;
    }

    /**
     * Add an event description to the event creation payload.
     *
     * @param array $send
     *   The array to append new event data.
     */
    protected function addNotes(&$send)
    {
        // Get the data from the AllPlayers webhook.
        $data = $this->getData();

        // Add additional information to the payload.
        if (isset($data['event']['description']) && !empty($data['event']['description'])) {
            $send['notes'] = $data['event']['description'];
        }
    }

    /**
     * Add the scores of an event to the event creation payload, if present.
     *
     * @param $send
     *   The array to append new event data.
     * @param $group
     *   The group
     */
    protected function addScores(&$send, $group)
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getOriginalData();

        // Get the score data from webhook.
        $score = $this->getGameScores(
            $group['uuid'],
            $data['event']['competitor']
        );

        // Add additional information to the payload.
        if (isset($score['score_for']) && !empty($score['score_for'])) {
            $send['score_for'] = $score['score_for'];
        }
        if (isset($score['score_against']) && !empty($score['score_against'])) {
            $send['score_against'] = $score['score_against'];
        }
        if (isset($score['home_or_away']) && !empty($score['home_or_away'])) {
            $send['home_or_away'] = $score['home_or_away'];
        }
    }

    /**
     * Fetch and attach the TeamSnap OpponentID for the given team and opponent.
     *
     * @param $send
     *   The array to append new event data.
     * @param $team
     *   The TeamSnap TeamID for the group.
     * @param $opponent
     *   The AllPlayers team to attach as the competitor.
     */
    protected function addOpponent(&$send, $team, $opponent)
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getOriginalData();

        $send['opponent_id'] = $this->getOpponentResource(
            $opponent['uuid'],
            $team,
            $data['event']['competitor']
        );
    }

    /**
     * Finish processing the event as a game for TeamSnap.
     *
     * @param $send
     *   The array to append new event data.
     */
    protected function finishGame(&$send)
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getOriginalData();

        // Store the core variables for each iteration of adding competitors.
        $original_domain = $this->domain;
        $original_send = $send;

        // Iterate each available competitor and send a request to TeamSnap to
        // create the mapping for each competitor to the game event.
        foreach ($data['event']['competitor'] as $group) {
            // Check if the event already exists.
            $event = $this->partner_mapping->readPartnerMap(
                PartnerMap::PARTNER_MAP_EVENT,
                $data['event']['uuid'],
                $group['uuid']
            );
            $event = $event['external_resource_id'];

            // Skip creating this event because it already exists, it is not
            // applicable to update here because the event was just made in
            // another webhook (this is the other teams webhook event).
            if (isset($event)) {
                continue;
            }

            // Get TeamID from the partner-mapping API.
            $team = $this->partner_mapping->readPartnerMap(
                PartnerMap::PARTNER_MAP_GROUP,
                $group['uuid'],
                $group['uuid']
            );
            $team = $team['external_resource_id'];

            // Attach the opponent for the event.
            $this->addOpponent($send, $team, $group);

            // Attach the scores to the event if they are present.
            $this->addScores($send, $group);

            // Update the payload and process the response.
            $this->domain = $original_domain . '/teams/' . $team
                . '/as_roster/'
                . $this->webhook->subscriber['commissioner_id']
                . '/games';
            $this->setData(array('game' => $send));
            parent::post();
            $response = $this->send();
            $response = $this->helper->processJsonResponse($response);

            // Manually create an event partner-mapping.
            $this->partner_mapping->createPartnerMap(
                $response['game']['id'],
                PartnerMap::PARTNER_MAP_EVENT,
                $data['event']['uuid'],
                $group['uuid']
            );

            // Reset the temp variables for the next iteration.
            $this->domain = $original_domain;
            $send = $original_send;
        }

        // Cancel the webhook for game events (manually processed).
        parent::setSend(parent::WEBHOOK_CANCEL);
    }

    /**
     * Finish processing the event as a practice for TeamSnap.
     *
     * @param $send
     *   The array to append new event data.
     */
    protected function finishPractice(&$send)
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getOriginalData();

        // Get TeamID from the partner-mapping API.
        $team = $this->partner_mapping->readPartnerMap(
            PartnerMap::PARTNER_MAP_GROUP,
            $data['group']['uuid'],
            $data['group']['uuid']
        );
        $team = $team['external_resource_id'];

        $this->domain .= '/teams/' . $team . '/as_roster/'
            . $this->webhook->subscriber['commissioner_id']
            . '/practices';

        // Update the request and let PostWebhooks complete.
        $this->setData(array('practice' => $send));
        parent::post();
    }
}
