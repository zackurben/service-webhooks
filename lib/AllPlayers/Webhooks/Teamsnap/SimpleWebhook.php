<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Teamsnap/SimpleWebhook.
 */

namespace AllPlayers\Webhooks\Teamsnap;

use AllPlayers\Webhooks\Webhook;
use AllPlayers\Utilities\Helper;
use AllPlayers\Utilities\TeamsnapPartnerMap;

/**
 * A simple webhook that provides common structure to each webhook type.
 */
class SimpleWebhook extends Webhook
{
    /**
     * The list of supported Sports from TeamSnap and their ID numbers.
     *
     * @var array
     */
    public static $sports = array(
        'Archery' => 59,
        'Australian Football' => 26,
        'Badminton' => 27,
        'Bandy' => 28,
        'Baseball' => 5,
        'Basketball' => 1,
        'Bocce' => 29,
        'Bowling' => 13,
        'Broomball' => 30,
        'Cheerleading' => 31,
        'Chess' => 32,
        'Cow Tipping' => 54,
        'Cricket' => 8,
        'Croquet' => 33,
        'Curling' => 34,
        'Cycling' => 35,
        'Dodgeball' => 14,
        'Dragon Boat' => 25,
        'Fencing' => 36,
        'Field Hockey' => 15,
        'Floor Hockey' => 60,
        'Floorball' => 44,
        'Foosball' => 37,
        'Football' => 7,
        'Golf' => 46,
        'Gymnastics-Men' => 56,
        'Gymnastics-Women' => 57,
        'Hurling' => 38,
        'Ice Hockey' => 16,
        'Indoor Soccer' => 39,
        'Inline Hockey' => 17,
        'Ki-O-Rahi' => 50,
        'Kickball' => 18,
        'Lacrosse' => 10,
        'Netball' => 40,
        'Non-Sport Group' => 52,
        'Other Sport' => 24,
        'Outrigger' => 53,
        'Paintball' => 19,
        'Petanque' => 45,
        'Polo' => 20,
        'Racquetball' => 55,
        'Ringette' => 51,
        'Roller Derby' => 48,
        'Rowing' => 21,
        'Rugby' => 9,
        'Running' => 41,
        'Sailing' => 47,
        'Slo-pitch' => 61,
        'Soccer' => 2,
        'Softball' => 4,
        'Street Hockey' => 62,
        'Swimming' => 42,
        'Tennis' => 43,
        'Track And Field' => 58,
        'Ultimate' => 22,
        'Volleyball' => 6,
        'Water Polo' => 23,
        'Wiffleball' => 11,
        'Wrestling' => 49,
    );

    /**
     * The list of TeamSnap supported locations and timezones.
     *
     * @var array
     */
    public static $regions = array(
        '13:00' => array(
            'timezone' => 'Samoa',
            'location' => 'Samoa',
        ),
        '12:00' => array(
            'timezone' => 'Auckland',
            'location' => 'New Zealand',
        ),
        '11:00' => array(
            'timezone' => 'Vladivostok',
            'location' => 'Russia',
        ),
        '10:00' => array(
            'timezone' => 'Sydney',
            'location' => 'Australia',
        ),
        '9:30' => array(
            'timezone' => 'Adelaide',
            'location' => 'Australia',
        ),
        '9:00' => array(
            'timezone' => 'Osaka',
            'location' => 'Japan',
        ),
        '8:00' => array(
            'timezone' => 'Chongqing',
            'location' => 'China',
        ),
        '7:00' => array(
            'timezone' => 'Jakarta',
            'location' => 'Indonesia',
        ),
        '6:30' => array(
            'timezone' => 'Rangoon',
            'location' => 'Myanmar',
        ),
        '6:00' => array(
            'timezone' => 'Dhaka',
            'location' => 'Bangladesh',
        ),
        '5:45' => array(
            'timezone' => 'Kathmandu',
            'location' => 'Nepal',
        ),
        '5:30' => array(
            'timezone' => 'Mumbai',
            'location' => 'India',
        ),
        '5:00' => array(
            'timezone' => 'Karachi',
            'location' => 'Pakistan',
        ),
        '4:30' => array(
            'timezone' => 'Kabul',
            'location' => 'Afghanistan',
        ),
        '4:00' => array(
            'timezone' => 'Moscow',
            'location' => 'Russia',
        ),
        '3:30' => array(
            'timezone' => 'Tehran',
            'location' => 'Iran',
        ),
        '3:00' => array(
            'timezone' => 'Riyadh',
            'location' => 'Saudi Arabia',
        ),
        '2:00' => array(
            'timezone' => 'Athens',
            'location' => 'Greece',
        ),
        '1:00' => array(
            'timezone' => 'Berlin',
            'location' => 'Germany',
        ),
        '-1:00' => array(
            'timezone' => 'Cape Verde Is.',
            'location' => 'Republic of Cabo Verde',
        ),
        '-2:00' => array(
            'timezone' => 'Mid-Atlantic',
            'location' => 'United Kingdom',
        ),
        '-3:00' => array(
            'timezone' => 'Brasilia',
            'location' => 'Brazil',
        ),
        '-3:30' => array(
            'timezone' => 'Newfoundland',
            'location' => 'Canada',
        ),
        '-4:00' => array(
            'timezone' => 'Atlantic Time (Canada)',
            'location' => 'Canada',
        ),
        '-5:00' => array(
            'timezone' => 'Eastern Time (US & Canada)',
            'location' => 'United States',
        ),
        '-6:00' => array(
            'timezone' => 'Central Time (US & Canada)',
            'location' => 'United States',
        ),
        '-7:00' => array(
            'timezone' => 'Mountain Time (US & Canada)',
            'location' => 'United States',
        ),
        '-8:00' => array(
            'timezone' => 'Pacific Time (US & Canada)',
            'location' => 'United States',
        ),
        '-9:00' => array(
            'timezone' => 'Alaska',
            'location' => 'United States',
        ),
        '-10:00' => array(
            'timezone' => 'Hawaii',
            'location' => 'United States',
        ),
        '-11:00' => array(
            'timezone' => 'American Samoa',
            'location' => 'United States',
        ),
    );

    /**
     * The URL to post the webhook.
     *
     * @var string
     */
    protected $domain = 'https://api.teamsnap.com/v2';

    /**
     * The TeamsnapPartnerMapping object to make Partner-Mapping API calls with.
     *
     * @var TeamsnapPartnerMap|null
     */
    protected $partner_mapping = null;

    /**
     * The Helper object used to format Webhook response data.
     *
     * @var Helper|null
     */
    protected $helper = null;

    /**
     * Create a TeamSnap Webhook object.
     *
     * @param array $subscriber
     *   The Subscriber variable provided by the Resque Job.
     * @param array $data
     *   The Event Data variable provided by the Resque Job.
     */
    public function __construct(
        array $subscriber = array(),
        array $data = array()
    ) {
        include __DIR__ . '/../../../../resque/config/config.php';
        if (isset($config['teamsnap'])) {
            // Determine if we have a defined organization.
            $org = isset($data['group']['organization_id'][0])
                && array_key_exists(
                    $data['group']['organization_id'][0],
                    $config['teamsnap']
                );

            // Define webhook specific variables for the organization.
            if ($org) {
                $group = $data['group']['organization_id'][0];
                $group_token = $config['teamsnap'][$group]['token'];
                $group_commissioner = $config['teamsnap'][$group]['commissioner_id'];
                $group_division = $config['teamsnap'][$group]['division_id'];
                $api_user = $config['teamsnap'][$group]['api_username'];
                $api_pass = $config['teamsnap'][$group]['api_password'];
            } else {
                $group_token = $config['teamsnap']['default']['token'];
                $group_commissioner = $config['teamsnap']['default']['commissioner_id'];
                $group_division = $config['teamsnap']['default']['division_id'];
                $api_user = $config['teamsnap']['default']['api_username'];
                $api_pass = $config['teamsnap']['default']['api_password'];
            }

            parent::__construct(
                array(
                    'token' => $group_token,
                    'commissioner_id' => $group_commissioner,
                    'division_id' => $group_division,
                ),
                $data
            );

            // Set the TeamSnap webhook variables.
            $this->helper = new Helper();
            $this->partner_mapping = new TeamsnapPartnerMap(
                $api_user,
                $api_pass
            );
            $this->headers['X-Teamsnap-Token'] = $this->webhook->subscriber['token'];
        }
    }

    /**
     * Get the Helper utility object.
     *
     * @return \AllPlayers\Utilities\Helper
     *   The Helper object used to format response data.
     */
    protected function getHelper()
    {
        return $this->helper;
    }

    /**
     * Get the PartnerMap utility object.
     *
     * @return \AllPlayers\Utilities\TeamsnapPartnerMap.
     *   The Teamsnap abstraction for the partner-mapping API.
     */
    protected function getPartnerMap()
    {
        return $this->partner_mapping;
    }

    /**
     * Process the webhook data and manage the partner-mapping API calls.
     */
    public function process()
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getAllplayersData();

        // Determine if the current webhook is for a Team, cancel it otherwise,
        // because TeamSnap does not currently support any hierarchy of groups.
        if ($data['group']['group_type'] != 'Team') {
            $this->setSend(self::WEBHOOK_CANCEL);
        }
    }

    /**
     * Get the TeamSnap Sport from the list of supported Sports.
     *
     * @param string $data
     *   Name of the sport selected on AllPlayers.
     *
     * @return integer
     *   The SportID corresponding to the available sports on TeamSnap.
     */
    public function getSport($data)
    {
        // Return the TeamSnap SportID or the 'Non-Sport Group' default.
        if (isset(self::$sports[$data])) {
            return self::$sports[$data];
        } else {
            return 52;
        }
    }

    /**
     * Convert the timezone to the TeamSnap supported location and timezone.
     *
     * @param string $timezone
     *   A PHP supported timezone: http://php.net/manual/en/timezones.php
     *
     * @return array
     *   An associative keyed array with the location and timezone information.
     */
    public function getRegion($timezone)
    {
        // Calculate the users timezone from UTC.
        $user_timezone = timezone_offset_get(
            new \DateTimeZone($timezone),
            new \DateTime(null, new \DateTimeZone('UTC'))
        )/(3600);

        // Adjust time for DST.
        if (date_format(new \DateTime($timezone), 'I') == 1) {
            $user_timezone += -1;
        }

        // Convert to UTC (non-DST) format.
        $floor = floor($user_timezone);
        $offset = (($floor + ($user_timezone - $floor)) * 60);
        $user_timezone = str_replace('.', ':', number_format($offset, 2));

        // Return the TeamSnap timezone and region, or the UTC default.
        if (isset(self::$regions[$user_timezone])) {
            return self::$regions[$user_timezone];
        } else {
            return array(
                'timezone' => 'UTC',
                'location' => 'United Kingdom',
            );
        }
    }

    /**
     * Build the payload for updating or creating a TeamSnap users email.
     *
     * @param string $email_address
     *   The email address to map.
     * @param string $user_uuid
     *   The AllPlayers UUID to make/search the partner-mapping.
     * @param string $group_uuid
     *   The AllPlayers Group UUID to make/search the partner-mapping.
     *
     * @return array
     *   The payload to send to create/update the TeamSnap email resource.
     */
    protected function getEmailResource(
        $email_address,
        $user_uuid,
        $group_uuid
    ) {
        // Get the EmailID from the partner-mapping API.
        $email_id = $this->partner_mapping->getRosterEmailId(
            $user_uuid,
            $group_uuid
        );

        // Build the email payload.
        if (is_array($email_id) && isset($email_id['external_resource_id'])) {
            // Partner mapping exists, build the PUT request data.
            $email = array(
                array(
                    'id' => $email_id['external_resource_id'],
                    'email' => $email_address,
                ),
            );
        } else {
            // Partner mapping does not exist, build the POST request data.
            $email = array(
                array(
                    'label' => 'Contact',
                    'email' => $email_address,
                ),
            );
        }

        return $email;
    }

    /**
     * Convert an AllPlayers location resource to a TeamSnap LocationID.
     *
     *   If the location resource exists in the partner-mapping API, it will use
     * that id.
     *   If the location resource does not exist in the partner-mapping API, but
     * a location resource is given, it will be added to the partner-mapping API
     * and return the correct payload for attaching a location to an event.
     *   If no resource is given, it will use or create the default location,
     * unique to each TeamSnap group; AllPlayers events do not require a
     * location, while TeamSnap events do.
     *
     * @param string $group
     *   The Group UUID to make the location mapping with.
     * @param array $location
     *   The Event location information from the AllPlayers Webhook.
     *
     * @return string
     *   The LocationID for TeamSnap API calls.
     */
    protected function getLocationResource(
        $group,
        array $location = array()
    ) {
        // Store the old domain.
        $original_domain = $this->domain;

        // Get TeamID from the partner-mapping API.
        $team = $this->partner_mapping->getTeamId($group);
        if (isset($team['external_resource_id'])) {
            $team = $team['external_resource_id'];
        } else {
            // The team should be in the partner-mapping API before this point.
            throw new \Exception(
                'The given group is not present in the partner-mapping API: '
                . $group
            );
        }

        // Get the LocationID for the event or the default LocationID for the
        // Team on TeamSnap.
        if (!empty($location)) {
            // Get LocationID from the partner-mapping API.
            $id = $this->partner_mapping->getLocationId(
                $location['uuid'],
                $group
            );
        } else {
            // Read the default location mapping if the location isnt specified.
            $id = $this->partner_mapping->getDefaultLocationId($group);
        }

        // Set the default data, if required information is missing.
        if (!isset($location['title']) || empty($location['title'])) {
            $location['title'] = '(TBD)';
        }
        if (!isset($location['street']) || empty($location['street'])) {
            $location['street'] = '(TBD)';
        }

        // Build the request payload for making a location resource.
        $send = array(
            'location_name' => $location['title'],
            'address' => $location['street'],
        );

        // Add additional information to the payload.
        if (isset($location['additional']) && !empty($location['additional'])) {
            $send['address'] .= ', ' . $location['additional'];
        }
        if (isset($location['city']) && !empty($location['city'])) {
            $send['address'] .= '. ' . $location['city'];
        }
        if (isset($location['province']) && !empty($location['province'])) {
            $send['address'] .= ', ' . $location['province'];
        }
        if (isset($location['postal_code']) && !empty($location['postal_code'])) {
            $send['address'] .= '. ' . $location['postal_code'];
        }
        if (isset($location['country']) && !empty($location['country'])) {
            $send['address'] .= '. ' . strtoupper($location['country']) . '.';
        }

        // Set the payload data.
        $send = array('location' => $send);
        $this->setRequestData($send);

        // Create or update the Location resource on TeamSnap.
        if (isset($id['external_resource_id'])) {
            $id = $id['external_resource_id'];

            // Update the payload domain.
            $this->domain = $original_domain . '/teams/' . $team . '/as_roster/'
                . $this->webhook->subscriber['commissioner_id'] . '/locations/'
                . $id;

            // Update the request type.
            parent::put();

            // Manually send the request.
            $this->send();
        } else {
            // Update the payload domain.
            $this->domain = $original_domain . '/teams/' . $team . '/as_roster/'
                . $this->webhook->subscriber['commissioner_id'] . '/locations';

            // Update the request type.
            parent::post();

            // Manually send the request and process the response.
            $response = $this->send();
            $response = $this->helper->processJsonResponse($response);

            // Add the new location resource to the partner-mapping. This will
            // also handle the default team LocationID if an AllPlayers resource
            // is not included.
            if (isset($location['uuid']) && !empty($location['uuid'])) {
                // Associate an AllPlayers resource UUID with the TeamSnap
                // LocationID.
                $this->partner_mapping->setLocationId(
                    $response['location']['id'],
                    $location['uuid'],
                    $group
                );
            } else {
                // Create the default location, a work around for the AllPlayers
                // event not having a location (TeamSnap requires an event
                // location for a new game event).
                $this->partner_mapping->setDefaultLocationId(
                    $response['location']['id'],
                    $group
                );
            }

            $id = $response['location']['id'];
        }

        // Restore the old domain, return the LocationID.
        $this->domain = $original_domain;
        return $id;
    }

    /**
     * Convert an AllPlayers competitor to a TeamSnap OpponentID.
     *
     * Note: This is made to only handle events with 1 competitor (TeamSnap).
     *
     *   If the opponent resource exists in the partner-mapping API, it will use
     * that id.
     *   If the opponent resource does not exist in the partner-mapping api, but
     * is given, it will be added to the partner-mapping API and return the
     * OpponentID
     *
     * @param string $group
     *   The Group UUID for the team on AllPlayers.
     * @param string $team_id
     *   The TeamID for team on TeamSnap.
     * @param array $competitors
     *   The list of competitors from the AllPlayers Webhook.
     *
     * @return string
     *   The OpponentID for TeamSnap API calls.
     */
    protected function getOpponentResource(
        $group,
        $team_id,
        array $competitors
    ) {
        // Store the old domain.
        $original_domain = $this->domain;
        $id = '';

        // Iterate each available competitor and only process the groups that
        // dont share a uuid with the given AllPlayers group.
        foreach ($competitors as $competitor) {
            if ($competitor['uuid'] != $group) {
                // Get OpponentID from the partner-mapping API.
                $id = $this->partner_mapping->getOpponentId(
                    $competitor['uuid'],
                    $group
                );

                // Set the default data, if required information is missing.
                if (!isset($competitor['name']) || empty($competitor['name'])) {
                    $competitor['name'] = '(TBD)';
                }

                // Build the request payload to Update/Create a resource.
                $send = array(
                    'opponent' => array('opponent_name' => $competitor['name']),
                );

                // Update the request payload.
                $this->setRequestData($send);

                if (isset($id['external_resource_id'])) {
                    // Update the existing partner-mapping resource.
                    $id = $id['external_resource_id'];
                    $this->domain = $original_domain . '/teams/' . $team_id
                        . '/as_roster/'
                        . $this->webhook->subscriber['commissioner_id']
                        . '/opponents/' . $id;

                    parent::put();
                    $this->send();
                } else {
                    // Create a new partner-mapping resource.
                    $this->domain = $original_domain . '/teams/' . $team_id
                        . '/as_roster/'
                        . $this->webhook->subscriber['commissioner_id']
                        . '/opponents';

                    parent::post();
                    $response = $this->send();
                    $response = $this->getHelper()->processJsonResponse($response);

                    // If an Opponent resource was just created, map it here.
                    if (isset($competitor['uuid'])
                        && !empty($competitor['uuid'])
                    ) {
                        // Associate an AllPlayers Group UUID with the
                        // Competitor UUID and its TeamSnap TeamID.
                        $this->partner_mapping->setOpponentId(
                            $response['opponent']['id'],
                            $competitor['uuid'],
                            $group
                        );
                    }

                    $id = $response['opponent']['id'];
                }
            }

            // Reset the temporary variables for the next iteration.
            $this->domain = $original_domain;
        }

        // Restore the old domain, return the OpponentID.
        $this->domain = $original_domain;
        return $id;
    }

    /**
     * Add the Event details, using the given webhook data.
     *
     * @param array $send
     *   The array to append the new user data.
     */
    protected function addEvent(&$send)
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getAllplayersData();

        // Set the event information contained in the webhook payload.
        $send['eventname'] = $data['event']['title'];
        $send['division_id'] = $this->webhook->subscriber['division_id'];
        $send['event_date_start'] = $data['event']['start'];
        $send['event_date_end'] = $data['event']['end'];
    }

    /**
     * Add the Event Description, using the given webhook data.
     *
     * @param array $send
     *   The array to append new event data.
     */
    protected function addEventDescription(&$send)
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getAllplayersData();

        // Add the event description, if present.
        if (isset($data['event']['description'])
            && !empty($data['event']['description'])
        ) {
            $send['notes'] = $data['event']['description'];
        }
    }

    /**
     * Add the Event Location details, using the given webhook data.
     *
     * @param array $send
     *   The array to append the new user data.
     */
    protected function addEventLocation(&$send)
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getAllplayersData();

        // Set the event location using the partner-mapping API.
        $send['location_id'] = $this->getLocationResource(
            $data['group']['uuid'],
            isset($data['event']['location'])
            ? $data['event']['location']
            : array()
        );
    }

    /**
     * Add the Event OpponentID, using the given webhook data.
     *
     * @param $send
     *   The array to append new event data.
     * @param $team
     *   The TeamSnap TeamID for the group.
     * @param $opponent
     *   The AllPlayers team to attach as the competitor.
     */
    protected function addEventOpponent(&$send, $team, $opponent)
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getAllplayersData();

        // Set the event opponent using the partner-mapping API.
        $send['opponent_id'] = $this->getOpponentResource(
            $opponent['uuid'],
            $team,
            $data['event']['competitor']
        );
    }

    /**
     * Add the scores of an event, if present, in reference to a specific group.
     *
     * @param $send
     *   The array to append new score data.
     * @param array $group
     *   The group to add the scores in reference to.
     */
    protected function addEventScores(&$send, array $group)
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getAllplayersData();

        // Add additional information to the payload.
        foreach ($data['event']['competitor'] as $competitor) {
            if ($competitor['uuid'] == $group['uuid']) {
                $send['score_for'] = $competitor['score'];

                // Determine the teams status.
                if (strcasecmp($competitor['label'], 'Home') == 0) {
                    // The group is home.
                    $send['home_or_away'] = 1;
                } elseif (strcasecmp($competitor['label'], 'Away') == 0) {
                    // The group is away.
                    $send['home_or_away'] = 2;
                }
            } else {
                $send['score_against'] = $competitor['score'];
            }
        }
    }

    /**
     * Add the Users Address, using the given webhook data.
     *
     * @param array $send
     *   The array to append the new user data.
     */
    protected function addRosterAddress(&$send)
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getAllplayersData();

        // Use the webform data.
        $webform = isset($data['webform']['data'])
            ? $data['webform']['data']
            : null;

        // Add address fields, if defined in the AllPlayers webform.
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

    /**
     * Add the Users Birthday, using the given webhook data.
     *
     * @param array $send
     *   The array to append the new user data.
     */
    protected function addRosterBirthday(&$send)
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getAllplayersData();

        // Use the webform data.
        $webform = isset($data['webform']['data'])
            ? $data['webform']['data']
            : null;

        // Set the users birthday, if defined in the AllPlayers webform.
        if (isset($webform['profile__field_birth_date__profile'])) {
            $send['birthdate'] = $webform['profile__field_birth_date__profile'];
        }
    }

    /**
     * Add the Users email address, using the given webhook data.
     *
     * Note: If a guardian is present, their email will be used.
     *
     * @param array $send
     *   The array to append the new user data.
     */
    protected function addRosterEmail(&$send)
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getAllplayersData();

        // Determine if we are using the guardian or users email address.
        $email_address = isset($data['member']['guardian'])
            ? $data['member']['guardian']['email']
            : $data['member']['email'];

        // Set the email information for this roster based on the given data.
        $send['roster_email_addresses_attributes'] = $this->getEmailResource(
            $email_address,
            $data['member']['uuid'],
            $data['group']['uuid']
        );
    }

    /**
     * Add the Users Gender, using the given webhook data.
     *
     * @param array $send
     *   The array to append the new user data.
     */
    protected function addRosterGender(&$send)
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getAllplayersData();

        // Use the webform data.
        $webform = isset($data['webform']['data'])
            ? $data['webform']['data']
            : null;

        // Set the users gender, if defined in the AllPlayers webform.
        if (isset($webform['profile__field_user_gender__profile'])) {
            $send['gender']
                = $webform['profile__field_user_gender__profile'] == 1
                ? 'Male'
                : 'Female';
        }
    }

    /**
     * Add the Users Name, using the given webhook data.
     *
     * Note: This will use webform data over the profile, if present.
     *
     * @param array $send
     *   The array to append the new user data.
     * @param integer $method
     *   The HTTP verb for this webhook as defined in Webhook.
     *
     * @see Webhook::HTTP_POST
     * @see Webhook::HTTP_PUT
     */
    protected function addRosterName(&$send, $method)
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getAllplayersData();

        // Use the webform data.
        $webform = isset($data['webform']['data'])
            ? $data['webform']['data']
            : null;

        // Update/Add the users First Name.
        if (isset($webform['profile__field_firstname__profile'])) {
            $send['first'] = $webform['profile__field_firstname__profile'];
        } elseif ($method == self::HTTP_POST
            && !isset($webform['profile__field_firstname__profile'])
        ) {
            // Required element missing, use profile info.
            $send['first'] = $data['member']['first_name'];
        }

        // Update/Add the users Last Name.
        if (isset($webform['profile__field_lastname__profile'])) {
            $send['last'] = $webform['profile__field_lastname__profile'];
        } elseif ($method == self::HTTP_POST
            && !isset($webform['profile__field_lastname__profile'])
        ) {
            // Required element missing, use profile info.
            $send['last'] = $data['member']['last_name'];
        }
    }

    /**
     * Add the Users Phone Numbers, using the given webhook data.
     *
     * @param array $send
     *   The array to append the new user data.
     */
    protected function addRosterPhoneNumber(&$send)
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getAllplayersData();

        // Use the webform data.
        $webform = isset($data['webform']['data'])
            ? $data['webform']['data']
            : null;

        // Store phone numbers to add.
        $payload = array();

        // Add the Home Phone if present.
        if (isset($webform['profile__field_phone__profile'])) {
            // Check for an existing phone number.
            $query = $this->partner_mapping->getRosterHomePhoneId(
                $data['member']['uuid'],
                $data['group']['uuid']
            );

            // Dynamically adjust payload label/id.
            if (isset($query['external_resource_id'])) {
                $key = 'id';
                $value = $query['external_resource_id'];
            } else {
                $key = 'label';
                $value = 'Home';
            }

            // Add home phone info to payload.
            $payload[] = array(
                $key => $value,
                'phone_number' => $webform['profile__field_phone__profile'],
            );
        }

        // Add the Cell Phone if present.
        if (isset($webform['profile__field_phone_cell__profile'])) {
            // Check for existing cell phone number.
            $query = $this->partner_mapping->getRosterCellPhoneId(
                $data['member']['uuid'],
                $data['group']['uuid']
            );

            // Dynamically adjust payload label/id.
            if (isset($query['external_resource_id'])) {
                $key = 'id';
                $value = $query['external_resource_id'];
            } else {
                $key = 'label';
                $value = 'Cell';
            }

            // Add cell phone info to payload.
            $payload[] = array(
                $key => $value,
                'phone_number' => $webform['profile__field_phone_cell__profile'],
            );
        }

        // Add the Work Phone if present.
        if (isset($webform['profile__field_work_number__profile'])) {
            // Check for existing work phone number.
            $query = $this->partner_mapping->getRosterWorkPhoneId(
                $data['member']['uuid'],
                $data['group']['uuid']
            );

            // Dynamically adjust payload label/id.
            if (isset($query['external_resource_id'])) {
                $key = 'id';
                $value = $query['external_resource_id'];
            } else {
                $key = 'label';
                $value = 'Work';
            }

            // Add work phone info to payload.
            $payload[] = array(
                $key => $value,
                'phone_number' => $webform['profile__field_work_number__profile'],
            );
        }

        // Only include the Phone Numbers, if one exists.
        if (count($payload) > 0) {
            $send['roster_telephones_attributes'] = $payload;
        }
    }

    /**
     * Add the Users roles, using the given webhook data.
     *
     * @param array $send
     *   The array to append the new user data.
     * @param integer $method
     *   The HTTP verb for this webhook as defined in Webhook.
     *
     * @see Webhook::HTTP_POST
     * @see Webhook::HTTP_PUT
     */
    protected function addRosterRole(&$send, $method)
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getAllplayersData();

        // Update the roles by using the data sent in the webhook.
        switch ($data['member']['role_name']) {
            case 'Owner':
                // Owner role does not exist by default, but it is injected when
                // the UserAddsRole webhook is created during the
                // UserCreatesGroup webhook. This is used to avoid further hacks
                // inside the UserCreatesGroup webhook for adding the creator.
                $send['non_player'] = 1;
                $send['is_manager'] = 1;
                $send['is_commissioner'] = 0;
                $send['is_owner'] = 1;
                break;
            case 'Player':
                $send['non_player'] = 0;
                break;
            case 'Admin':
            case 'Manager':
            case 'Coach':
                $send['is_manager'] = 1;
                break;
            case 'Fan':
                // If the user is being created, specify non-player
                // role, otherwise disregard to avoid overwriting
                // a pre-existing player status.
                if ($method == self::HTTP_POST) {
                    $send['non_player'] = 1;
                }
                break;
            case 'Guardian':
                // Ignore AllPlayers guardian changes.
                $this->setSend(self::WEBHOOK_CANCEL);
                break;
        }
    }

    /**
     * Add the Users removed roles, using the given webhook data.
     *
     * @param array $send
     *   The array to append the new user data.
     */
    protected function addRosterRoleRemoved(&$send)
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getAllplayersData();

        // Update the roles by negating the data sent in the webhook.
        switch ($data['member']['role_name']) {
            case 'Player':
                $send['non_player'] = 1;
                break;
            case 'Admin':
            case 'Manager':
            case 'Coach':
                $send['is_manager'] = 0;
                break;
            case 'Guardian':
                // Ignore AllPlayers guardian changes.
                $this->setSend(self::WEBHOOK_CANCEL);
                break;
        }
    }

    /**
     * Add the Teams details, using the given webhook data.
     *
     * @param array $send
     *   The array to append the new user data.
     */
    protected function addTeam(&$send)
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getAllplayersData();

        // Set the Team information contained in the webhook payload.
        $send['team_name'] = $data['group']['name'];
        $send['team_league'] = 'All Players';
        $send['division_id'] = intval(
            $this->webhook->subscriber['division_id']
        );
        $send['zipcode'] = $data['group']['postalcode'];
    }

    /**
     * Add the Teams logo, using the given webhook data, if present.
     *
     * @param array $send
     *   The array to append the new user data.
     */
    protected function addTeamLogo(&$send)
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getAllplayersData();

        // Add additional information to the payload.
        if (isset($data['group']['logo'])) {
            $send['logo_url'] = $data['group']['logo'];
        }
    }

    /**
     * Add the Teams Region information, using the given webhook data.
     *
     * @param array $send
     *   The array to append the new user data.
     */
    protected function addTeamRegion(&$send)
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getAllplayersData();

        // Get the region information for the given timezone.
        $region = $this->getRegion($data['group']['timezone']);

        // Set the region information using the timezone.
        $send['timezone'] = $region['timezone'];
        $send['country'] = $region['location'];
    }

    /**
     * Add the Teams Sport information, using the given webhook data.
     *
     * @param array $send
     *   The array to append the new user data.
     */
    protected function addTeamSport(&$send)
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getAllplayersData();

        // Set the Sport ID, using the AllPlayers group category.
        $send['sport_id'] = $this->getSport($data['group']['group_category']);
    }

    /**
     * Add or Update the users Roster mapping for TeamSnap.
     *
     * Note: If the mapping is created, this will send an email invitation.
     *
     * @param array $response
     *   An array representation of the response data.
     */
    protected function mapRoster(array $response)
    {
        // Get the original data sent from the AllPlayers webhook.
        $original_data = $this->getAllplayersData();

        // Get the RosterID from the partner-mapping API.
        $query = $this->partner_mapping->getRosterId(
            $original_data['member']['uuid'],
            $original_data['group']['uuid']
        );

        // Add/update the mapping of an email with a Roster.
        $this->partner_mapping->setRosterEmailId(
            $response['roster']['roster_email_addresses'][0]['id'],
            $original_data['member']['uuid'],
            $original_data['group']['uuid']
        );

        // If the partner mapping resource does not exist, create the user
        // partner mapping, and send an email invitation.
        if (isset($query['message'])) {
            // Add the users RosterID to the partner-mapping API.
            $this->partner_mapping->setRosterId(
                $response['roster']['id'],
                $original_data['member']['uuid'],
                $original_data['group']['uuid']
            );

            // Get the users TeamID.
            $team = $this->partner_mapping->getTeamId(
                $original_data['group']['uuid']
            );
            if (isset($team['external_resource_id'])) {
                $team = $team['external_resource_id'];
            } else {
                // The team should be in the partner-mapping API before this
                // point.
                throw new \Exception(
                    'The given group is not present in the partner-mapping API:'
                    . ' ' . $original_data['group']['uuid']
                );
            }

            // Build the request payload.
            $send = array($response['roster']['id']);

            // Update the payload domain, to send an email invite.
            $this->domain = 'https://api.teamsnap.com/v2/teams/' . $team
                . '/as_roster/' . $this->webhook->subscriber['commissioner_id']
                . '/invitations';

            // Set the payload data.
            $this->setRequestData(array('rosters' => $send));

            // Update the request and send the TeamSnap account an invitation.
            parent::post();
            $this->send();
        }
    }

    /**
     * Add or Update the users Roster phone mapping for TeamSnap, if present.
     *
     * @param array $response
     *   An array representation of the response data.
     */
    protected function mapRosterPhone(array $response)
    {
        // Get the original data sent from the AllPlayers webhook.
        $original_data = $this->getAllplayersData();

        // Skip checking for phone numbers, if they are not included.
        if (!isset($response['roster']['roster_telephone_numbers'])) {
            return;
        }

        // Add/update the mapping of phones with a Roster.
        $phones = $response['roster']['roster_telephone_numbers'];
        if (count($phones) > 0) {
            // Determine which phones are present.
            foreach ($phones as $entry) {
                switch ($entry['label']) {
                    case 'Home':
                        $this->partner_mapping->setRosterHomePhoneId(
                            $entry['id'],
                            $original_data['member']['uuid'],
                            $original_data['group']['uuid']
                        );
                        break;
                    case 'Cell':
                        $this->partner_mapping->setRosterCellPhoneId(
                            $entry['id'],
                            $original_data['member']['uuid'],
                            $original_data['group']['uuid']
                        );
                        break;
                    case 'Work':
                        $this->partner_mapping->setRosterWorkPhoneId(
                            $entry['id'],
                            $original_data['member']['uuid'],
                            $original_data['group']['uuid']
                        );
                        break;
                }
            }
        }
    }
}
