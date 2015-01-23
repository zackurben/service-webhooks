<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Teamsnap/UserAddsSubmission.
 */

namespace AllPlayers\Webhooks\Teamsnap;

use AllPlayers\Webhooks\ProcessInterface;
use AllPlayers\Webhooks\Webhook;
use Guzzle\Http\Message\Response;

/**
 * The unique TeamSnap implementation of the user_adds_submission webhook.
 */
class UserAddsSubmission extends SimpleWebhook implements ProcessInterface
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

        // Get the RosterID from partner-mapping API.
        $roster = $this->partner_mapping->getRosterId(
            $data['member']['uuid'],
            $data['group']['uuid']
        );

        // Create a new user on TeamSnap, if the resource was not found.
        if (isset($roster['external_resource_id'])) {
            $method = self::HTTP_PUT;
            $roster = $roster['external_resource_id'];
        } else {
            $method = self::HTTP_POST;
        }

        // Get TeamID from the partner-mapping API.
        $team = $this->partner_mapping->getTeamId($data['group']['uuid']);
        if (isset($team['external_resource_id'])) {
            $team = $team['external_resource_id'];
        } else {
            // If the team is null, requeue this webhook, for another attempt,
            // and discard after maximum number of attempts.
            $this->requeueWebhook();
            return;
        }

        // Build the request payload.
        $send = array();
        $this->addRosterName($send, $method);
        $this->addRosterEmail($send);
        $this->addRosterBirthday($send);
        $this->addRosterGender($send);
        $this->addRosterPhoneNumber($send);
        $this->addRosterAddress($send);

        // Cancel this request if payload is empty.
        if (empty($send)) {
            $this->setSend(Webhook::WEBHOOK_CANCEL);
            return;
        }

        // Update the payload domain.
        $this->domain .= '/teams/' . $team . '/as_roster/'
            . $this->webhook->subscriber['commissioner_id'] . '/rosters';

        // Set the payload data.
        $this->setRequestData(array('roster' => $send));

        // Create/update partner-mapping information.
        if ($method == self::HTTP_POST) {
            // Update the request type and let PostWebhooks complete.
            parent::post();
        } else {
            // Update the domain to account for a user update.
            $this->domain .= '/' . $roster;

            // Update the request type and let PostWebhooks complete.
            parent::put();
        }
    }

    /**
     * Process the payload response and manage the partner-mapping API calls.
     *
     * @param \Guzzle\Http\Message\Response $response
     *   Response from the request being processed and sent.
     */
    public function processResponse(Response $response)
    {
        // Convert the raw Guzzle Response object to an array.
        $response = $this->helper->processJsonResponse($response);

        // Create/Update the roster entry in the Partner mapping API.
        $this->mapRoster($response);

        // Create/Update the roster phone entries in the partner-mapping API.
        $this->mapRosterPhone($response);
    }
}
