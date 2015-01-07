<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Teamsnap/UserUpdatesEvent.
 */

namespace AllPlayers\Webhooks\Teamsnap;

use AllPlayers\Webhooks\ProcessInterface;
use AllPlayers\Utilities\PartnerMap;
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

        // Stop processing if this webhook isn't being sent.
        $send = $this->checkSend();
        if (!$send) {
            return;
        }

        // Get the data from the AllPlayers webhook.
        $data = $this->getData();

        // Build the webhook payload.
        $send = $this->updateTeamsnapEvent();

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

    }

    /**
     * Build the webhook payload to update the event.
     *
     * @return array
     *   The event data to send to update the TeamSnap event.
     */
    private function updateTeamsnapEvent()
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getOriginalData();

        // Build the webhook payload.
        $send = array(
            'eventname' => $data['event']['title'],
            'division_id' => $this->webhook->subscriber['division_id'],
            'event_date_start' => $data['event']['start'],
            'event_date_end' => $data['event']['end'],
        );
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
    private function addLocation(&$send)
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getOriginalData();

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
    private function addNotes(&$send)
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getOriginalData();

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
     *   The AllPlayers group that owns the event.
     */
    private function addScores(&$send, $group)
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
    private function addOpponent(&$send, $team, $opponent)
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
    private function finishGame(&$send)
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getOriginalData();

        $original_domain = $this->domain;
        $original_send = $send;

        foreach ($data['event']['competitor'] as $group) {
            // Get TeamID from the partner-mapping API.
            $team = $this->partner_mapping->readPartnerMap(
                PartnerMap::PARTNER_MAP_GROUP,
                $group['uuid'],
                $group['uuid']
            );
            $team = $team['external_resource_id'];

            // Get EventID from the partner-mapping API.
            $event = $this->partner_mapping->readPartnerMap(
                PartnerMap::PARTNER_MAP_EVENT,
                $data['event']['uuid'],
                $group['uuid']
            );
            $event = $event['external_resource_id'];

            // The event does not exist, cancel this webhook and create it.
            if (!isset($event)) {
                $this->createEvent();
            } else {
                // Attach the opponent for the event.
                $this->addOpponent($send, $team, $group);

                // Attach the scores to the event if they are present.
                $this->addScores($send, $group);

                // Update the request payload and send.
                $this->domain = $original_domain . '/teams/' . $team
                    . '/as_roster/'
                    . $this->webhook->subscriber['commissioner_id']
                    . '/games/' . $event;
                $this->setData(array('game' => $send));
                parent::put();
                $this->send();

                // Reset the temp variables for the next iteration.
                $this->domain = $original_domain;
                $send = $original_send;
            }
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
    private function finishPractice(&$send)
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

        // Get EventID from the partner-mapping API.
        $event = $this->partner_mapping->readPartnerMap(
            PartnerMap::PARTNER_MAP_EVENT,
            $data['event']['uuid'],
            $data['group']['uuid']
        );
        $event = $event['external_resource_id'];

        if (!isset($event)) {
            // The event does not exist, cancel this webhook and create it.
            $this->createEvent();
        } else {
            // Update the request and let PostWebhooks complete.
            $this->domain .= '/teams/' . $team . '/as_roster/'
                . $this->webhook->subscriber['commissioner_id']
                . '/practices/' . $event;
            $this->setData(array('practice' => $send));
            parent::put();
        }
    }

    /**
     * Create an event rather than attempt to update a non-existant one.
     */
    private function createEvent()
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getOriginalData();

        // Stop the call PostWebhooks#send() because this is an
        // incorrect webhook.
        $this->setSend(self::WEBHOOK_CANCEL);

        // Create a UserCreatesEvent webhook with the contents given.
        $temp_data = $data;
        $temp_data['webhook_type'] = self::WEBHOOK_CREATE_EVENT;

        $temp = new \AllPlayers\Webhooks\Teamsnap\UserCreatesEvent(
            array(),
            $temp_data
        );
        $temp->process();

        if ($temp->getSend() == self::WEBHOOK_SEND) {
            $temp_response = $temp->send();
            $temp->processResponse($temp_response);
            $temp->setSend(self::WEBHOOK_CANCEL);
        }
    }
}
