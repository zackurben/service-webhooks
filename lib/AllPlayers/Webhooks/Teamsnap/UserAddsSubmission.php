<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Teamsnap/UserAddsSubmission.
 */

namespace AllPlayers\Webhooks\Teamsnap;

use AllPlayers\Webhooks\ProcessInterface;
use AllPlayers\Utilities\PartnerMap;
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

        // Stop processing if this webhook isn't being sent.
        $send = $this->checkSend();
        if (!$send) {
            return;
        }

        // Get the data from the AllPlayers webhook.
        $data = $this->getData();

        // Get RosterID from the partner-mapping API.
        $roster = $method = '';
        $roster = $this->partner_mapping->readPartnerMap(
            PartnerMap::PARTNER_MAP_USER,
            $data['member']['uuid'],
            $data['group']['uuid']
        );

        // Determine if we are making a new resource or updating an existing.
        if (isset($roster['message'])) {
            $method = self::HTTP_POST;
        } elseif (isset($roster['external_resource_id'])) {
            $method = self::HTTP_PUT;
            $roster = $roster['external_resource_id'];
        }

        // Get TeamID from the partner-mapping API.
        $team = $this->partner_mapping->readPartnerMap(
            PartnerMap::PARTNER_MAP_GROUP,
            $data['group']['uuid'],
            $data['group']['uuid']
        );
        $team = $team['external_resource_id'];

        // Build the webhook payload.
        $send = $this->addTeamsnapUserData($method);

        // Cancel webhook if data is not present.
        if (empty($send)) {
            $this->setSend(Webhook::WEBHOOK_CANCEL);
            return;
        }

        // Update the request and let PostWebhooks complete.
        $this->setData(array('roster' => $send));
        $this->domain .= '/teams/' . $team . '/as_roster/'
            . $this->webhook->subscriber['commissioner_id']
            . '/rosters';

        if ($method == self::HTTP_POST) {
            parent::post();
        } else {
            $this->domain .= '/' . $roster;
            parent::put();
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

        // Get UserID from the partner-mapping API.
        $query = $this->partner_mapping->readPartnerMap(
            PartnerMap::PARTNER_MAP_USER,
            $original_data['member']['uuid'],
            $original_data['group']['uuid']
        );

        // Add/update the mapping of an email with a Roster.
        $this->partner_mapping->createPartnerMap(
            $response['roster']['roster_email_addresses'][0]['id'],
            PartnerMap::PARTNER_MAP_USER,
            $original_data['member']['uuid'],
            $original_data['group']['uuid'],
            PartnerMap::PARTNER_MAP_SUBTYPE_USER_EMAIL
        );

        // Add/update the mapping of phones with a Roster.
        $phones = $response['roster']['roster_telephone_numbers'];
        if (count($phones) > 0) {
            // Determine which phones are present.
            foreach ($phones as $entry) {
                switch ($entry['label']) {
                    case 'Home':
                        $this->partner_mapping->createPartnerMap(
                            $entry['id'],
                            PartnerMap::PARTNER_MAP_USER,
                            $original_data['member']['uuid'],
                            $original_data['group']['uuid'],
                            PartnerMap::PARTNER_MAP_SUBTYPE_USER_PHONE
                        );
                        break;
                    case 'Cell':
                        $this->partner_mapping->createPartnerMap(
                            $entry['id'],
                            PartnerMap::PARTNER_MAP_USER,
                            $original_data['member']['uuid'],
                            $original_data['group']['uuid'],
                            PartnerMap::PARTNER_MAP_SUBTYPE_USER_PHONE_CELL
                        );
                        break;
                    case 'Work':
                        $this->partner_mapping->createPartnerMap(
                            $entry['id'],
                            PartnerMap::PARTNER_MAP_USER,
                            $original_data['member']['uuid'],
                            $original_data['group']['uuid'],
                            PartnerMap::PARTNER_MAP_SUBTYPE_USER_PHONE_WORK
                        );
                        break;
                }
            }
        }

        // The user is registering, send an email invite.
        if (isset($query['message'])) {
            // Add a mapping of the user UUID with a TeamSnap RosterID.
            $this->partner_mapping->createPartnerMap(
                $response['roster']['id'],
                PartnerMap::PARTNER_MAP_USER,
                $original_data['member']['uuid'],
                $original_data['group']['uuid']
            );

            // Send the TeamSnap account invite.
            $team = $this->partner_mapping->readPartnerMap(
                PartnerMap::PARTNER_MAP_GROUP,
                $original_data['group']['uuid'],
                $original_data['group']['uuid']
            );

            $this->domain = 'https://api.teamsnap.com/v2/teams/'
                . $team['external_resource_id'] . '/as_roster/'
                . $this->webhook->subscriber['commissioner_id']
                . '/invitations';
            $send = array(
                $response['roster']['id'],
            );

            // Update the request and send.
            $this->setData(array('rosters' => $send));
            parent::post();
            $this->send();
        }
    }

    /**
     * Build the webhook payload to update/create the user account.
     *
     * @param integer $method
     *   The HTTP verb for this webhook as defined in Webhook.
     *
     * @see Webhook::HTTP_POST
     * @see Webhook::HTTP_PUT
     *
     * @return array
     *   The user data to send to update/create the user account.
     */
    protected function addTeamsnapUserData($method)
    {
        // Build the webhook payload; defaults to account information
        // for missing elements that are required.
        $send = array();
        $this->addName($send, $method);
        $this->addEmailAddress($send, $method);
        $this->addBirthday($send);
        $this->addGender($send);
        $this->addPhoneNumber($send);
        $this->addAddress($send);

        return $send;
    }

    /**
     * Add the Users Name to their TeamSnap profile.
     *
     * @param array $send
     *   The array to append the new user data.
     * @param integer $method
     *   The HTTP verb for this webhook as defined in Webhook.
     *
     * @see Webhook::HTTP_POST
     * @see Webhook::HTTP_PUT
     */
    protected function addName(&$send, $method)
    {
        $data = $this->getData();
        $webform = $data['webform']['data'];

        // Update/Add the users First Name.
        if (isset($webform['profile__field_firstname__profile'])) {
            $send['first'] = $webform['profile__field_firstname__profile'];
        } elseif ($method == self::HTTP_POST && !isset($webform['profile__field_firstname__profile'])) {
            // Required element missing, use profile info.
            $send['first'] = $data['member']['first_name'];
        }

        // Update/Add the users Last Name.
        if (isset($webform['profile__field_lastname__profile'])) {
            $send['last'] = $webform['profile__field_lastname__profile'];
        } elseif ($method == self::HTTP_POST && !isset($webform['profile__field_lastname__profile'])) {
            // Required element missing, use profile info.
            $send['last'] = $data['member']['last_name'];
        }
    }

    /**
     * Add the Users Email Address to their TeamSnap profile.
     *
     * @param array $send
     *   The array to append the new user data.
     * @param integer $method
     *   The HTTP verb for this webhook as defined in Webhook.
     *
     * @see Webhook::HTTP_POST
     * @see Webhook::HTTP_PUT
     */
    protected function addEmailAddress(&$send, $method)
    {
        $data = $this->getData();
        $webform = $data['webform']['data'];

        // Override email address with guardians email, if present.
        if (isset($data['member']['guardian'])) {
            $send['roster_email_addresses_attributes'] = $this->getEmailResource(
                $data['member']['guardian']['email'],
                $data['member']['uuid'],
                $data['group']['uuid']
            );
        } else {
            if (isset($webform['profile__field_email__profile'])) {
                $send['roster_email_addresses_attributes'] = $this->getEmailResource(
                    $webform['profile__field_email__profile'],
                    $data['member']['uuid'],
                    $data['group']['uuid']
                );
            } elseif ($method == self::HTTP_POST && !isset($webform['profile__field_email__profile'])) {
                // Required element missing, use profile info.
                $send['roster_email_addresses_attributes'] = $this->getEmailResource(
                    $data['member']['email'],
                    $data['member']['uuid'],
                    $data['group']['uuid']
                );
            }
        }
    }

    /**
     * Add the Users Birthday to their TeamSnap profile.
     *
     * @param array $send
     *   The array to append the new user data.
     */
    protected function addBirthday(&$send)
    {
        $data = $this->getData();
        $webform = $data['webform']['data'];

        if (isset($webform['profile__field_birth_date__profile'])) {
            $send['birthdate'] = $webform['profile__field_birth_date__profile'];
        }
    }

    /**
     * Add the Users Gender to their TeamSnap profile.
     *
     * @param array $send
     *   The array to append the new user data.
     */
    protected function addGender(&$send)
    {
        $data = $this->getData();
        $webform = $data['webform']['data'];

        if (isset($webform['profile__field_user_gender__profile'])) {
            $send['gender'] = $webform['profile__field_user_gender__profile'] == 1 ? 'Male' : 'Female';
        }
    }

    /**
     * Add the Users Phone Numbers to their TeamSnap profile.
     *
     * @param array $send
     *   The array to append the new user data.
     */
    protected function addPhoneNumber(&$send)
    {
        $roster_telephones_attributes = array();
        $data = $this->getData();
        $webform = $data['webform']['data'];

        // Add the Home Phone if present.
        if (isset($webform['profile__field_phone__profile'])) {
            // Check for an existing phone number.
            $query = $this->partner_mapping->readPartnerMap(
                PartnerMap::PARTNER_MAP_USER,
                $data['member']['uuid'],
                $data['group']['uuid'],
                PartnerMap::PARTNER_MAP_SUBTYPE_USER_PHONE
            );

            // Dynamically adjust payload label/id.
            $key = $value = null;
            if (array_key_exists('external_resource_id', $query)) {
                $key = 'id';
                $value = $query['external_resource_id'];
            } else {
                $key = 'label';
                $value = 'Home';
            }

            // Add home phone info to payload.
            $roster_telephones_attributes[] = array(
                $key => $value,
                'phone_number' => $webform['profile__field_phone__profile'],
            );
        }

        // Add the Cell Phone if present.
        if (isset($webform['profile__field_phone_cell__profile'])) {
            // Check for existing cell phone number.
            $query = $this->partner_mapping->readPartnerMap(
                PartnerMap::PARTNER_MAP_USER,
                $data['member']['uuid'],
                $data['group']['uuid'],
                PartnerMap::PARTNER_MAP_SUBTYPE_USER_PHONE_CELL
            );

            // Dynamically adjust payload label/id.
            $key = $value = null;
            if (array_key_exists('external_resource_id', $query)) {
                $key = 'id';
                $value = $query['external_resource_id'];
            } else {
                $key = 'label';
                $value = 'Cell';
            }

            // Add cell phone info to payload.
            $roster_telephones_attributes[] = array(
                $key => $value,
                'phone_number' => $webform['profile__field_phone_cell__profile'],
            );
        }

        // Add the Work Phone if present.
        if (isset($webform['profile__field_work_number__profile'])) {
            // Check for existing work phone number.
            $query = $this->partner_mapping->readPartnerMap(
                PartnerMap::PARTNER_MAP_USER,
                $data['member']['uuid'],
                $data['group']['uuid'],
                PartnerMap::PARTNER_MAP_SUBTYPE_USER_PHONE_WORK
            );

            // Dynamically adjust payload label/id.
            $key = $value = null;
            if (array_key_exists('external_resource_id', $query)) {
                $key = 'id';
                $value = $query['external_resource_id'];
            } else {
                $key = 'label';
                $value = 'Work';
            }

            // Add work phone info to payload.
            $roster_telephones_attributes[] = array(
                $key => $value,
                'phone_number' => $webform['profile__field_work_number__profile'],
            );
        }

        // Only include the Phone Numbers, if one exists.
        if (count($roster_telephones_attributes) > 0) {
            $send['roster_telephones_attributes'] = $roster_telephones_attributes;
        }
    }

    /**
     * Add the Users Address to their TeamSnap profile.
     *
     * @param array $send
     *   The array to append the new user data.
     */
    protected function addAddress(&$send)
    {
        $data = $this->getData();
        $webform = $data['webform']['data'];

        // Add address fields, if present.
        if (isset($webform['profile__field_home_address_street__profile'])) {
            $send['address'] = $webform['profile__field_home_address_street__profile'];
        }
        if (isset($webform['profile__field_home_address_additional__profile'])) {
            $send['address2'] = $webform['profile__field_home_address_additional__profile'];
        }
        if (isset($webform['profile__field_home_address_city__profile'])) {
            $send['city'] = $webform['profile__field_home_address_city__profile'];
        }
        if (isset($webform['profile__field_home_address_province__profile'])) {
            $send['state'] = $webform['profile__field_home_address_province__profile'];
        }
        if (isset($webform['profile__field_home_address_postal_code__profile'])) {
            $send['zip'] = $webform['profile__field_home_address_postal_code__profile'];
        }
        if (isset($webform['profile__field_home_address_country__profile'])) {
            $send['country'] = $webform['profile__field_home_address_country__profile'];
        }
    }
}
