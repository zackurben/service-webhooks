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

        // Cancel the continued processing of this webhook, if this was canceled
        // in the parent:process() call.
        if ($this->getSend() != \AllPlayers\Webhooks\Webhook::WEBHOOK_SEND) {
            return;
        }

        // Get the data from the AllPlayers webhook.
        $data = $this->getData();

        // Build the webhook payload.
        $send = array(
            'eventname' => $data['event']['title'],
            'division_id' => $this->webhook->subscriber['division_id'],
            'event_date_start' => $data['event']['start'],
            'event_date_end' => $data['event']['end'],
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
                } else {
                    // Continue processing the UserUpdatesEvent webhook,
                    // make/get the location resource.
                    $event_location = isset($data['event']['location']) ? $data['event']['location'] : null;
                    $location = $this->getLocationResource(
                        $group['uuid'],
                        $event_location
                    );

                    // Get the opponent resource.
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
                    $send['location_id'] = $location;
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
        } else {
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

            // The event does not exist, cancel this webhook and create it.
            if (!isset($event)) {
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
            } else {
                // Continue processing the UserUpdatesEvent webhook,
                // make/get the location resource.
                $event_location = isset($data['event']['location']) ? $data['event']['location'] : null;
                $location = $this->getLocationResource(
                    $data['group']['uuid'],
                    $event_location
                );

                // Update the request and let PostWebhooks complete.
                $send['location_id'] = $location;
                $this->domain .= '/teams/' . $team . '/as_roster/'
                    . $this->webhook->subscriber['commissioner_id']
                    . '/practices/' . $event;
                $this->setData(array('practice' => $send));
                parent::put();
            }
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
