<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Teamsnap/SimpleWebhook.
 */

namespace AllPlayers\Webhooks\Teamsnap;

use AllPlayers\Webhooks\Webhook;
use AllPlayers\Utilities\Helper;
use AllPlayers\Utilities\PartnerMap;

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
     * The PartnerMapping object to make Partner-Mapping API calls with.
     *
     * @var PartnerMap|null
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
            // Determing if we have a defined organization.
            $org = isset($data['group']['organization_id'][0]) && array_key_exists(
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
            $this->partner_mapping = new PartnerMap(
                'teamsnap',
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
     */
    public function getHelper()
    {
        return $this->helper;
    }

    /**
     * Get the PartnerMap utility object.
     *
     * @return \AllPlayers\Utilities\PartnerMap.
     */
    public function getPartnerMap()
    {
        return $this->partner_mapping;
    }

    /**
     * Process the webhook data and manage the partner-mapping API calls.
     */
    public function process()
    {
        // Set the original webhook data.
        $data = $this->getData();
        $this->setOriginalData($data);

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
     *   The sport id corresponding to the available sports in the TeamSnap API.
     */
    public function getSport($data)
    {
        if (isset(self::$sports[$data])) {
            // The sport is supported and present.
            return self::$sports[$data];
        } else {
            // Return 'Non-Sport Group' for the TeamSnap group type.
            return 52;
        }
    }

    /**
     * Get the TeamSnap supported location and timezone.
     *
     * @param string $timezone
     *   A PHP supported timezone: http://php.net/manual/en/timezones.php
     *
     * @return array
     *   An associative keyed array with the location and timezone information.
     */
    public function getRegion($timezone)
    {
        $user_timezone = timezone_offset_get(
            new \DateTimeZone($timezone),
            (new \DateTime(null, new \DateTimeZone('UTC')))
        )/(3600);
        if (date_format(new \DateTime($timezone), 'I') == 1) {
            // Adjust time for DST.
            $user_timezone += -1;
        }

        // Convert to UTC (non-DST) format.
        $user_timezone = str_replace(
            '.',
            ':',
            number_format((floor($user_timezone) + ($user_timezone - floor($user_timezone)) * 60), 2)
        );

        if (isset(self::$regions[$user_timezone])) {
            // The region is supported and present.
            return self::$regions[$user_timezone];
        } else {
            // return default UTC region for the region information.
            return array(
                'timezone' => 'UTC',
                'location' => 'United Kingdom',
            );
        }
    }

    /**
     * Get the scores for a given group, for an event.
     *
     * @param string $group_uuid
     *   The group receiving an event update from the webhook.
     * @param array $competitors
     *   The list of competitors from the AllPlayers Webhook.
     *
     * @return array
     *   The score specifics for a game event, keyed by TeamSnap qualities.
     */
    public function getGameScores($group_uuid, array $competitors)
    {
        $score = array();

        foreach ($competitors as $competitor) {
            if ($competitor['uuid'] == $group_uuid) {
                $score['score_for'] = $competitor['score'];

                // Determine team status.
                if (strcasecmp($competitor['label'], 'Home') == 0) {
                    // The group is home.
                    $score['home_or_away'] = 1;
                } elseif (strcasecmp($competitor['label'], 'Away') == 0) {
                    // The group is away.
                    $score['home_or_away'] = 2;
                }
            } else {
                $score['score_against'] = $competitor['score'];
            }
        }

        return $score;
    }

    /**
     * Dynamically build the email data to send for a TeamSnap user/contact.
     *
     * @param string $email_address
     *   The email address to map.
     * @param string $user_uuid
     *   The AllPlayers UUID to make/search the partner-mapping.
     * @param string $group_uuid
     *   The AllPlayers Group UUID to make/search the partner-mapping.
     *
     * @return array
     *   The data to send to create/update the TeamSnap email resource.
     */
    public function getEmailResource(
        $email_address,
        $user_uuid,
        $group_uuid
    ) {
        $email = '';
        $email_id = $this->partner_mapping->readPartnerMap(
            PartnerMap::PARTNER_MAP_USER,
            $user_uuid,
            $group_uuid,
            PartnerMap::PARTNER_MAP_SUBTYPE_USER_EMAIL
        );

        // Build the email payload.
        if (is_array($email_id) && array_key_exists('external_resource_id', $email_id)) {
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
     * Get the partner-mapped location resource ID for TeamSnap.
     *
     * This will create the resource if it does not already exist.
     *
     * @param string $group_uuid
     *   The Group UUID to make the location mapping with.
     * @param array $event_location
     *   The Event location information from the AllPlayers Webhook.
     *
     * @return string
     *   The resource ID for TeamSnap API calls.
     */
    public function getLocationResource($group_uuid, array $event_location = null)
    {
        // Store the old domain.
        $original_domain = $this->domain;
        $location = '';

        // Get TeamID from the partner-mapping API.
        $team = $this->partner_mapping->readPartnerMap(
            PartnerMap::PARTNER_MAP_GROUP,
            $group_uuid,
            $group_uuid
        );
        $team = $team['external_resource_id'];

        if (isset($event_location) && !empty($event_location)) {
            // Get LocationID from the partner-mapping API.
            $location = $this->partner_mapping->readPartnerMap(
                PartnerMap::PARTNER_MAP_RESOURCE,
                $event_location['uuid'],
                $group_uuid
            );
        } else {
            $event_location = array();

            // Read the default location mapping if the location isnt specified.
            $location = $this->partner_mapping->readPartnerMap(
                PartnerMap::PARTNER_MAP_GROUP,
                $group_uuid,
                $group_uuid,
                'default_location'
            );
        }

        // Set the default data, if required information is missing.
        if (!isset($event_location['title']) || empty($event_location['title'])) {
            $event_location['title'] = '(TBD)';
        }
        if (!isset($event_location['street']) || empty($event_location['street'])) {
            $event_location['street'] = '(TBD)';
        }

        // Build the webhook payload.
        $send = array(
            'location_name' => $event_location['title'],
            'address' => $event_location['street'],
        );

        // Add additional information to the payload.
        if (isset($event_location['additional']) && !empty($event_location['additional'])) {
            $send['address'] .= ', ' . $event_location['additional'];
        }
        if (isset($event_location['city']) && !empty($event_location['city'])) {
            $send['address'] .= '. ' . $event_location['city'];
        }
        if (isset($event_location['province']) && !empty($event_location['province'])) {
            $send['address'] .= ', ' . $event_location['province'];
        }
        if (isset($event_location['postal_code']) && !empty($event_location['postal_code'])) {
            $send['address'] .= '. ' . $event_location['postal_code'];
        }
        if (isset($event_location['country']) && !empty($event_location['country'])) {
            $send['address'] .= '. ' . strtoupper($event_location['country']) . '.';
        }

        // Update the webhook request.
        $this->setData(array('location' => $send));

        if (array_key_exists('external_resource_id', $location)) {
            // Update the existing partner-mapping resource.
            $location = $location['external_resource_id'];
            $this->domain = $original_domain . '/teams/' . $team . '/as_roster/'
                . $this->webhook->subscriber['commissioner_id'] . '/locations/'
                . $location;

            parent::put();
            $this->send();
        } else {
            // Create a new partner-mapping resource.
            $this->domain = $original_domain . '/teams/' . $team . '/as_roster/'
                . $this->webhook->subscriber['commissioner_id'] . '/locations';

            parent::post();
            $response = $this->send();
            $response = $this->helper->processJsonResponse($response);

            if (isset($event_location['uuid']) && !empty($event_location['uuid'])) {
                // Associate an AllPlayers resource UUID with the TeamSnap
                // LocationID.
                $this->partner_mapping->createPartnerMap(
                    $response['location']['id'],
                    PartnerMap::PARTNER_MAP_RESOURCE,
                    $event_location['uuid'],
                    $group_uuid
                );
            } else {
                // Create the default location, work around for above.
                $this->partner_mapping->createPartnerMap(
                    $response['location']['id'],
                    PartnerMap::PARTNER_MAP_GROUP,
                    $group_uuid,
                    $group_uuid,
                    'default_location'
                );
            }

            $location = $response['location']['id'];
        }

        // Restore the old domain, return the LocationID.
        $this->domain = $original_domain;
        return $location;
    }

    /**
     * Get the partner-mapped opponent resource ID for TeamSnap.
     *
     * This will create the resource if it does not already exist.
     *
     * @param string $group_uuid
     *   The group receiving an event update from the webhook.
     * @param string $group_partner_id
     *   The Team ID for the corresponding TeamSnap team.
     * @param array $competitors
     *   The list of competitors from the AllPlayers Webhook.
     *
     * @return string
     *   The resource ID for TeamSnap API calls.
     */
    public function getOpponentResource($group_uuid, $group_partner_id, array $competitors)
    {
        // Store the old domain.
        $original_domain = $this->domain;
        $opponent = '';

        foreach ($competitors as $competitor) {
            if ($competitor['uuid'] != $group_uuid) {
                // Get OpponentID from the partner-mapping API.
                $opponent = $this->partner_mapping->readPartnerMap(
                    PartnerMap::PARTNER_MAP_GROUP,
                    $competitor['uuid'],
                    $group_uuid
                );

                // Set the default data, if required information is missing.
                if (!isset($competitor['name']) || empty($competitor['name'])) {
                    $competitor['name'] = '(TBD)';
                }

                // Build the webhook payload.
                $send = array('opponent_name' => $competitor['name']);

                // Update the webhook request.
                $this->setData(array('opponent' => $send));

                if (isset($opponent['external_resource_id'])) {
                    // Update the existing partner-mapping resource.
                    $opponent = $opponent['external_resource_id'];
                    $this->domain = $original_domain . '/teams/'
                        . $group_partner_id . '/as_roster/'
                        . $this->webhook->subscriber['commissioner_id']
                        . '/opponents/' . $opponent;

                    parent::put();
                    $this->send();
                } else {
                    // Create a partner-mapping resource.
                    $this->domain = $original_domain . '/teams/'
                        . $group_partner_id . '/as_roster/'
                        . $this->webhook->subscriber['commissioner_id']
                        . '/opponents';

                    parent::post();
                    $response = $this->send();
                    $response = $this->getHelper()->processJsonResponse($response);

                    if (isset($competitor['uuid']) && !empty($competitor['uuid'])) {
                        // Associate an AllPlayers group UUID with a TeamSnap
                        // TeamID.
                        $this->partner_mapping->createPartnerMap(
                            $response['opponent']['id'],
                            PartnerMap::PARTNER_MAP_GROUP,
                            $competitor['uuid'],
                            $group_uuid
                        );
                    }

                    $opponent = $response['opponent']['id'];
                }
            }

            // Reset the temporary variables for the next iteration.
            $this->domain = $original_domain;
        }

        // Restore the old domain, return the OpponentID.
        $this->domain = $original_domain;
        return $opponent;
    }
}
