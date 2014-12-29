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

        // Make/get a location resource.
        $location = $this->getLocationResource(
            $data['group']['uuid'],
            isset($data['event']['location']) ? $data['event']['location'] : null
        );

        // Build the webhook payload.
        $send = array(
            'eventname' => $data['event']['title'],
            'division_id' => $this->webhook->subscriber['division_id'],
            'event_date_start' => $data['event']['start'],
            'event_date_end' => $data['event']['end'],
            'location_id' => $location,
        );

        // Add additional information to the payload.
        if (isset($data['event']['description']) && !empty($data['event']['description'])) {
            $send['notes'] = $data['event']['description'];
        }

        // Determine if this is a game or an event.
        if (isset($data['event']['competitor']) && !empty($data['event']['competitor'])) {
            $original_domain = $this->domain;
            $original_send = $send;

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

                // Get OpponentID from the partner-mapping API.
                $opponent = $this->getOpponentResource(
                    $group['uuid'],
                    $team,
                    $data['event']['competitor']
                );

                // Get the score data from webhook.
                $score = $this->getGameScores(
                    $group['uuid'],
                    $data['event']['competitor']
                );

                // Add additional information to the payload.
                $send['opponent_id'] = $opponent;
                if (isset($score['score_for']) && !empty($score['score_for'])) {
                    $send['score_for'] = $score['score_for'];
                }
                if (isset($score['score_against']) && !empty($score['score_against'])) {
                    $send['score_against'] = $score['score_against'];
                }
                if (isset($score['home_or_away']) && !empty($score['home_or_away'])) {
                    $send['home_or_away'] = $score['home_or_away'];
                }

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
        } else {
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
        }

        // Update the request and let PostWebhooks complete.
        $this->setData(array('practice' => $send));
        parent::post();
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
}
