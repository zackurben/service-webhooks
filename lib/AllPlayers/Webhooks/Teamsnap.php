<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Teamsnap.
 *
 * Provides the TeamSnap Webhook definitions.
 */

namespace AllPlayers\Webhooks;

use AllPlayers\Utilities\PartnerMap;
use AllPlayers\Utilities\Helper;
use Guzzle\Http\Message\Response;

/**
 * Base TeamSnap Webhook definition.
 */
class Teamsnap extends Webhook implements ProcessInterface
{
    /**
     * The list of supported Sports from TeamSnap and their ID numbers.
     *
     * @var array $sports
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
     * @var array $regions
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
     * The method used for Client authentication.
     *
     * @see AUTHENTICATION_NONE
     * @see AUTHENTICATION_BASIC
     * @see AUTHENTICATION_OAUTH
     *
     * @var integer
     */
    protected $authentication = self::AUTHENTICATION_NONE;

    /**
     * The method of data transmission.
     *
     * This establishes the method of transmission between the AllPlayers
     * webhook and the third-party webhook.
     *
     * @see TRANSMISSION_URLENCODED
     * @see TRANSMISSION_JSON
     *
     * @var string
     */
    protected $method = self::TRANSMISSION_JSON;

    /**
     * The PartnerMapping object to make Partner-Mapping API calls with.
     *
     * @var PartnerMap
     */
    protected $partner_mapping = null;

    /**
     * The Helper object used to format Webhook response data.
     *
     * @var Helper
     */
    protected $helper = null;

    /**
     * Create a TeamSnap Webhook object.
     *
     * @param array $subscriber
     *   The Subscriber variable provided by the Resque Job.
     * @param array $data
     *   The Event Data variable provided by the Resque Job.
     * @param array $preprocess
     *   Additional data used for pre-processing, defined in PostWebhooks.
     */
    public function __construct(
        array $subscriber = array(),
        array $data = array(),
        array $preprocess = array()
    ) {
        include 'config/config.php';
        if (isset($config['teamsnap'])) {
            parent::__construct(
                array(
                    'token' => $config['teamsnap']['token'],
                    'commissioner_id' => $config['teamsnap']['commissioner_id'],
                    'division_id' => $config['teamsnap']['division_id'],
                ),
                $data,
                $preprocess
            );

            // Set the TeamSnap webhook variables.
            $this->helper = new Helper();
            $this->partner_mapping = new PartnerMap(
                'teamsnap',
                $config['teamsnap']['api_username'],
                $config['teamsnap']['api_password']
            );
            $this->headers['X-Teamsnap-Token'] = $this->webhook->subscriber['token'];
            $this->process();
        }
    }

    /**
     * Process the webhook data and set the domain to the appropriate URL.
     */
    protected function process()
    {
        // Set the original webhook data.
        $data = $this->getData();
        $this->setOriginalData($data);

        switch ($data['webhook_type']) {
            case self::WEBHOOK_CREATE_GROUP:
                // Cancel the webhook if this is not a team being registered.
                if ($data['group']['group_type'] != 'Team') {
                    $this->setSend(self::WEBHOOK_CANCEL);
                    break;
                }

                $this->domain .= '/teams';

                // Build the webhook payload.
                $geographical = $this->getRegion($data['group']['timezone']);
                $send = array(
                    'team_name' => $data['group']['name'],
                    'team_league' => 'All Players',
                    'division_id' => intval($this->webhook->subscriber['division_id']),
                    'sport_id' => $this->getSport($data['group']['group_category']),
                    'timezone' => $geographical['timezone'],
                    'country' => $geographical['location'],
                    'zipcode' => $data['group']['postalcode'],
                );

                // Add additional information to the payload.
                if (isset($data['group']['logo'])) {
                    $send['logo_url'] = $data['group']['logo'];
                }

                // Create a team on TeamSnap.
                $this->setData(array('team' => $send));
                parent::post();
                $response = $this->send();

                // Manually invoke the partner-mapping.
                $this->processResponse($response);

                // Manipulate the webhook for the PostWebhooks#perform() call.
                $temp = $this->getOriginalData();
                $temp['webhook_type'] = self::WEBHOOK_ADD_ROLE;
                $this->setOriginalData($temp);

                // Add creator to the TeamSnap team.
                include 'config/config.php';
                if (isset($config['test_url'])) {
                    // Cancel sending the webhook if testing variable is set.
                    $this->setSend(self::WEBHOOK_CANCEL);
                } else {
                    $response = $this->helper->processJsonResponse($response);
                    $this->domain .= '/' . $response['team']['id']
                        . '/as_roster/'
                        . $this->webhook->subscriber['commissioner_id']
                        . '/rosters';
                }

                // Build the webhook payload.
                $send = array(
                    'first' => $data['member']['first_name'],
                    'last' => $data['member']['last_name'],
                    'roster_email_addresses_attributes' => $this->getEmailResource(
                        $data['member']['email'],
                        $data['member']['uuid'],
                        $data['group']['uuid']
                    ),
                    'non_player' => 1,
                    'is_manager' => 1,
                    'is_commissioner' => 0,
                    'is_owner' => 1,
                );

                // Update the request and let PostWebhooks complete.
                $this->setData(array('roster' => $send));
                parent::post();
                break;
            case self::WEBHOOK_UPDATE_GROUP:
                // Get TeamID from the partner-mapping API.
                $team = $this->partner_mapping->readPartnerMap(
                    PartnerMap::PARTNER_MAP_GROUP,
                    $data['group']['uuid'],
                    $data['group']['uuid']
                );
                $team = $team['external_resource_id'];
                $this->domain .= '/teams/' . $team;

                // Build the webhook payload.
                $geographical = $this->getRegion($data['group']['timezone']);
                $send = array(
                    'team_name' => $data['group']['name'],
                    'sport_id' => $this->getSport($data['group']['group_category']),
                    'timezone' => $geographical['timezone'],
                    'country' => $geographical['location'],
                    'zipcode' => $data['group']['postalcode'],
                );

                // Add additional information to the payload.
                if (isset($data['group']['logo'])) {
                    $send['logo_url'] = $data['group']['logo'];
                }

                // Update the request and let PostWebhooks complete.
                $this->setData(array('team' =>$send));
                parent::put();
                break;
            case self::WEBHOOK_DELETE_GROUP:
                // Get TeamID from partner-mapping API.
                $team = $this->partner_mapping->readPartnerMap(
                    PartnerMap::PARTNER_MAP_GROUP,
                    $data['group']['uuid'],
                    $data['group']['uuid']
                );
                $team = $team['external_resource_id'];
                $this->domain .= '/teams/' . $team;

                // Update the request and let PostWebhooks complete.
                $this->headers['X-Teamsnap-Features'] = '{"partner.delete_team": 1}';
                parent::delete();
                break;
            case self::WEBHOOK_ADD_ROLE:
                // Get the UserID from partner-mapping API.
                $method = $roster = '';
                $roster = $this->partner_mapping->readPartnerMap(
                    PartnerMap::PARTNER_MAP_USER,
                    $data['member']['uuid'],
                    $data['group']['uuid']
                );

                // Create a new user on TeamSnap, if the resource was not found.
                if (isset($roster['message'])) {
                    $method = self::HTTP_POST;
                } elseif (isset($roster['external_resource_id'])) {
                    $method = self::HTTP_PUT;
                    $roster = $roster['external_resource_id'];
                }

                // Get the TeamID from partner-mapping API.
                $team = $this->partner_mapping->readPartnerMap(
                    PartnerMap::PARTNER_MAP_GROUP,
                    $data['group']['uuid'],
                    $data['group']['uuid']
                );
                $team = $team['external_resource_id'];

                // Build the webhook payload.
                $send = array();
                switch ($data['member']['role_name']) {
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

                // Add email information to the webhook payload.
                if (isset($data['member']['guardian'])) {
                    $send['roster_email_addresses_attributes'] = $this->getEmailResource(
                        $data['member']['guardian']['email'],
                        $data['member']['uuid'],
                        $data['group']['uuid']
                    );
                } else {
                    $send['roster_email_addresses_attributes'] = $this->getEmailResource(
                        $data['member']['email'],
                        $data['member']['uuid'],
                        $data['group']['uuid']
                    );
                }

                $this->domain .= '/teams/' . $team . '/as_roster/'
                    . $this->webhook->subscriber['commissioner_id']
                    . '/rosters';

                // Create/update partner-mapping information.
                if ($method == self::HTTP_POST) {
                    // Add additional information to the payload.
                    $send['first'] = $data['member']['first_name'];
                    $send['last'] = $data['member']['last_name'];

                    // Update the request and let PostWebhooks complete.
                    $this->setData(array('roster' => $send));
                    parent::post();
                } else {
                    $this->domain .= '/' . $roster;

                    // Update the request and let PostWebhooks complete.
                    $this->setData(array('roster' => $send));
                    parent::put();
                }
                break;
            case self::WEBHOOK_REMOVE_ROLE:
                // Get RosterID from the partner-mapping API.
                $roster = $this->partner_mapping->readPartnerMap(
                    PartnerMap::PARTNER_MAP_USER,
                    $data['member']['uuid'],
                    $data['group']['uuid']
                );
                $roster = $roster['external_resource_id'];

                // Get TeamID from the partner-mapping API.
                $team = $this->partner_mapping->readPartnerMap(
                    PartnerMap::PARTNER_MAP_GROUP,
                    $data['group']['uuid'],
                    $data['group']['uuid']
                );
                $team = $team['external_resource_id'];

                $this->domain .= '/teams/' . $team . '/as_roster/'
                    . $this->webhook->subscriber['commissioner_id']
                    . '/rosters/' . $roster;

                // Build the webhook payload.
                $send = array();
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

                // Update the request and let PostWebhooks complete, only if the
                // update data is included.
                if (empty($send)) {
                    $this->setSend(self::WEBHOOK_CANCEL);
                } else {
                    $this->setData(array('roster' => $send));
                }
                parent::put();
                break;
            case self::WEBHOOK_ADD_SUBMISSION:
                // Get RosterID from the partner-mapping API.
                $roster = $method = '';
                $roster = $this->partner_mapping->readPartnerMap(
                    PartnerMap::PARTNER_MAP_USER,
                    $data['member']['uuid'],
                    $data['group']['uuid']
                );

                // Create a new user on TeamSnap, if the resource was not found.
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

                // Build the webhook payload; defaults to account information
                // for missing elements that are required.
                $send = array();
                $webform = $data['webform']['data'];
                if (isset($webform['profile__field_firstname__profile'])) {
                    $send['first'] = $webform['profile__field_firstname__profile'];
                } elseif ($method == self::HTTP_POST && !isset($webform['profile__field_firstname__profile'])) {
                    // Required element missing, use profile info.
                    $send['first'] = $data['member']['first_name'];
                }
                if (isset($webform['profile__field_lastname__profile'])) {
                    $send['last'] = $webform['profile__field_lastname__profile'];
                } elseif ($method == self::HTTP_POST && !isset($webform['profile__field_lastname__profile'])) {
                    // Required element missing, use profile info.
                    $send['last'] = $data['member']['last_name'];
                }

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

                if (isset($webform['profile__field_birth_date__profile'])) {
                    $send['birthdate'] = $webform['profile__field_birth_date__profile'];
                }
                if (isset($webform['profile__field_user_gender__profile'])) {
                    $send['gender'] = $webform['profile__field_user_gender__profile'] == 1 ? 'Male' : 'Female';
                }

                // Add roster phone numbers, if present.
                $roster_telephones_attributes = array();
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
                if (count($roster_telephones_attributes) > 0) {
                    $send['roster_telephones_attributes'] = $roster_telephones_attributes;
                }

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
                break;
            case self::WEBHOOK_DELETE_USER:
                // Get TeamID from the partner-mapping API.
                $team = $this->partner_mapping->readPartnerMap(
                    PartnerMap::PARTNER_MAP_GROUP,
                    $data['group']['uuid'],
                    $data['group']['uuid']
                );
                $team = $team['external_resource_id'];

                $roster = $this->partner_mapping->readPartnerMap(
                    PartnerMap::PARTNER_MAP_USER,
                    $data['member']['uuid'],
                    $data['group']['uuid']
                );
                $roster = $roster['external_resource_id'];

                // Delete the user from the team.
                $this->domain .= '/teams/' . $team . '/as_roster/'
                    . $this->webhook->subscriber['commissioner_id']
                    . '/rosters/' . $roster;

                parent::delete();
                break;
            case self::WEBHOOK_CREATE_EVENT:
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
                break;
            case self::WEBHOOK_UPDATE_EVENT:
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

                        // Make/get the location resource.
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

                    // Make/get the location resource.
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
                break;
            case self::WEBHOOK_DELETE_EVENT:
                // Determine if this is a game or an event.
                if (isset($data['event']['competitor']) && !empty($data['event']['competitor'])) {
                    $original_domain = $this->domain;

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

                        // Update the request payload and send.
                        $this->domain = $original_domain . '/teams/' . $team
                            . '/as_roster/'
                            . $this->webhook->subscriber['commissioner_id']
                            . '/games/' . $event;
                        parent::delete();
                        $this->send();

                        // Reset the temp variables for the next iteration.
                        $this->domain = $original_domain;
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

                    // Get EventID from partner-mapping API.
                    $event = $this->partner_mapping->readPartnerMap(
                        PartnerMap::PARTNER_MAP_EVENT,
                        $data['event']['uuid'],
                        $data['group']['uuid']
                    );
                    $event = $event['external_resource_id'];

                    // Update the request and let PostWebhooks complete.
                    $this->domain .= '/teams/' . $team . '/as_roster/'
                        . $this->webhook->subscriber['commissioner_id']
                        . '/practices/' . $event;
                    parent::delete();
                }

                break;
            default:
                // Cancel the webhook if not supported/defined above.
                $this->setSend(self::WEBHOOK_CANCEL);
                break;
        }
    }

    /**
     * Process the webhook data returned from sending the webhook.
     *
     * This function should relate a piece of AllPlayers data to a piece of
     * third-party data; This information relationship will be made via the
     * AllPlayers Public PHP API.
     *
     * @param \Guzzle\Http\Message\Response $response
     *   The Guzzle Response from the webhook being processed/called.
     */
    public function processResponse(Response $response)
    {
        include 'config/config.php';
        if (isset($config['test_url'])) {
            // Account for the extra JSON wrapper from requestbin (if testing).
            $response = $this->helper->processJsonResponse($response);
            $response = json_decode($response['body'], true);
        } else {
            $response = $this->helper->processJsonResponse($response);
        }

        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getData();
        $original_data = $this->getOriginalData();

        switch ($original_data['webhook_type']) {
            case self::WEBHOOK_CREATE_GROUP:
                // Associate an AllPlayers group UUID with a TeamSnap TeamID.
                $create = $this->partner_mapping->createPartnerMap(
                    $response['team']['id'],
                    PartnerMap::PARTNER_MAP_GROUP,
                    $original_data['group']['uuid'],
                    $original_data['group']['uuid']
                );

                break;
            case self::WEBHOOK_DELETE_GROUP:
                // Delete partner-mapping with the group UUID.
                $this->partner_mapping->deletePartnerMap(
                    null,
                    $original_data['group']['uuid']
                );
                break;
            case self::WEBHOOK_ADD_ROLE:
            case self::WEBHOOK_ADD_SUBMISSION:
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
                break;
            case self::WEBHOOK_DELETE_USER:
                // Delete the partner-mapping for a user.
                $this->partner_mapping->deletePartnerMap(
                    PartnerMap::PARTNER_MAP_USER,
                    $original_data['group']['uuid'],
                    $original_data['member']['uuid']
                );

                // Delete the partner-mapping for a user email id.
                $this->partner_mapping->deletePartnerMap(
                    PartnerMap::PARTNER_MAP_RESOURCE,
                    $original_data['group']['uuid'],
                    $original_data['member']['uuid'],
                    PartnerMap::PARTNER_MAP_SUBTYPE_USER_EMAIL
                );
                break;
            case self::WEBHOOK_CREATE_EVENT:
                // Associate an AllPlayers event UUID with a TeamSnap EventID.
                $this->partner_mapping->createPartnerMap(
                    $response['practice']['id'],
                    PartnerMap::PARTNER_MAP_EVENT,
                    $original_data['event']['uuid'],
                    $original_data['group']['uuid']
                );
                break;
            case self::WEBHOOK_DELETE_EVENT:
                // Delete the partner-mapping with an event UUID.
                $this->partner_mapping->deletePartnerMap(
                    PartnerMap::PARTNER_MAP_EVENT,
                    $original_data['group']['uuid'],
                    $original_data['event']['uuid']
                );
                break;
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
     *   The group recieving an event update from the webhook.
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
                // Associate an AllPlayers resouce UUID with the TeamSnap
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
     *   The group recieving an event update from the webhook.
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
        $original_domain = $this->domain; // store old domain
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
                    $response = $this->processJsonResponse($response);

                    if (isset($competitor['uuid']) && !empty($competitor['uuid'])) {
                        // Assiciate an AllPlayers group UUID with a TeamSnap
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
