<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Teamsnap/UserAddsRole.
 */

namespace AllPlayers\Webhooks\Teamsnap;

use AllPlayers\Webhooks\ProcessInterface;
use Guzzle\Http\Message\Response;

/**
 * The unique TeamSnap implementation of the user_adds_role webhook.
 */
class UserAddsRole extends SimpleWebhook implements ProcessInterface
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

        // Get the TeamID from partner-mapping API.
        $team = $this->partner_mapping->getTeamId($data['group']['uuid']);
        if (isset($team['external_resource_id'])) {
            $team = $team['external_resource_id'];
        } else {
            // @TODO: If the team is null, requeue this webhook.
            $team = null;
        }

        // Build the request payload.
        $send = array();
        $this->addName($send, $method);
        $this->addRole($send, $method);
        $this->addEmail($send);
        $send = array('roster' => $send);

        // Update the payload domain.
        $this->domain .= '/teams/' . $team . '/as_roster/'
            . $this->webhook->subscriber['commissioner_id']
            . '/rosters';

        // Set the payload data.
        $this->setRequestData($send);

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
