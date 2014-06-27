<?php

/**
 * @file Teamsnap.php
 *
 * Provides the TeamSnap Webhooks plugin definition. The TeanSnap Webhook sends
 * data to various API Endpoints, using a custom token based authentication.
 */

namespace AllPlayers\Webhooks;

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
     * Create Teamsnap webhook
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

            $this->headers['X-Teamsnap-Token'] = $this->webhook->subscriber['token'];
            $this->process();
        }
    }

    /**
     * Process the webhook data and set the domain to the appropriate URL.
     */
    protected function process()
    {
        // set original webhook data
        $data = $this->getData();
        $this->setOriginalData($data);

        switch ($data['webhook_type']) {
            case self::WEBHOOK_CREATE_GROUP:
                // Cancel the webhook if this is not a team being registered
                if ($data['group']['group_type'] != 'Team') {
                    $this->setSend(self::WEBHOOK_CANCEL);
                    break;
                }

                $this->domain .= '/teams';

                // build payload
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

                // add additional information to payload
                if (isset($data['group']['logo'])) {
                    $send['logo_url'] = $data['group']['logo'];
                }

                // create team on TeamSnap
                $this->setData(array('team' => $send));
                parent::post();
                $response = $this->send();

                // Manually invoke the partner-mapping
                $this->processResponse($response);

                // add creator to TeamSnap team
                include 'config/config.php';
                if (isset($config['test_url'])) {
                    // cancel webhook if testing
                    $this->setSend(self::WEBHOOK_CANCEL);
                } else {
                    $response = $this->processJsonResponse($response);

                    $this->domain .= '/' . $response['team']['id']
                        . '/as_roster/'
                        . $this->webhook->subscriber['commissioner_id']
                        . '/rosters';
                }

                // build payload
                $send = array(
                    'first' => $data['member']['first_name'],
                    'last' => $data['member']['last_name'],
                    'roster_email_addresses_attributes' => array(
                        array(
                            'label' => 'Profile',
                            'email' => $data['member']['email'],
                        ),
                    ),
                    'non_player' => 1,
                    'is_manager' => 1,
                    'is_commissioner' => 0,
                    'is_owner' => 1,
                );

                // update request and let PostWebhooks complete
                $this->setData(array('roster' => $send));
                parent::post();

                // manipulate the webhook for PostWebhooks#perform()
                $temp = $this->getOriginalData();
                $temp['webhook_type'] = self::WEBHOOK_ADD_ROLE;
                $this->setOriginalData($temp);
                break;
            case self::WEBHOOK_UPDATE_GROUP:
                // get TeamID from partner-mapping
                $team = parent::readPartnerMap(
                    self::PARTNER_MAP_GROUP,
                    $data['group']['uuid'],
                    $data['group']['uuid']
                );
                $team = $team['external_resource_id'];
                $this->domain .= '/teams/' . $team;

                // build payload
                $geographical = $this->getRegion($data['group']['timezone']);
                $send = array(
                    'team_name' => $data['group']['name'],
                    'sport_id' => $this->getSport($data['group']['group_category']),
                    'timezone' => $geographical['timezone'],
                    'country' => $geographical['location'],
                    'zipcode' => $data['group']['postalcode'],
                );

                // add additional information to payload
                if (isset($data['group']['logo'])) {
                    $send['logo_url'] = $data['group']['logo'];
                }

                // update request and let PostWebhooks complete
                $this->setData(array('team' =>$send));
                parent::put();
                break;
            case self::WEBHOOK_DELETE_GROUP:
                // get TeamID from partner-mapping
                $team = parent::readPartnerMap(
                    self::PARTNER_MAP_GROUP,
                    $data['group']['uuid'],
                    $data['group']['uuid']
                );
                $team = $team['external_resource_id'];
                $this->domain .= '/teams/' . $team;

                // update request and let PostWebhooks complete
                $this->headers['X-Teamsnap-Features'] = '{"partner.delete_team": 1}';
                parent::delete();
                break;
            case self::WEBHOOK_ADD_ROLE:
                // get UserID from partner-mapping
                $method = $roster = '';
                $roster = parent::readPartnerMap(
                    self::PARTNER_MAP_USER,
                    $data['member']['uuid'],
                    $data['group']['uuid']
                );

                // create new user on TeamSnap, if resource was not found
                if (isset($roster['message'])) {
                    $method = self::HTTP_POST;
                } elseif (isset($roster['external_resource_id'])) {
                    $method = self::HTTP_PUT;
                    $roster = $roster['external_resource_id'];
                }

                // get TeamID from partner-mapping
                $team = parent::readPartnerMap(
                    self::PARTNER_MAP_GROUP,
                    $data['group']['uuid'],
                    $data['group']['uuid']
                );
                $team = $team['external_resource_id'];

                // build payload
                $send = array();
                if ($data['member']['role_name'] == 'Player') {
                    $send['non_player'] = 0;
                }
                if ($data['member']['is_admin'] == 1) {
                    $send['is_manager'] = 1;
                }

                if ($method == self::HTTP_POST) {
                    // create new partner-mapping
                    $this->domain .= '/teams/' . $team . '/as_roster/'
                        . $this->webhook->subscriber['commissioner_id']
                        . '/rosters';

                    // add additional information to payload
                    $send['first'] = $data['member']['first_name'];
                    $send['last'] = $data['member']['last_name'];

                    if (isset($data['member']['guardian'])) {
                        $send['roster_email_addresses_attributes'] = array(
                            array(
                                'label' => 'Guardian Profile',
                                'email' => $data['member']['guardian']['email'],
                            ),
                        );
                    } else {
                        $send['roster_email_addresses_attributes'] = array(
                            array(
                                'label' => 'Profile',
                                'email' => $data['member']['email'],
                            ),
                        );
                    }

                    // update request and let PostWebhooks complete
                    $this->setData(array('roster' => $send));
                    parent::post();
                } else {
                    // cancel if roles were not changed
                    if (empty($send)) {
                        $this->setSend(self::WEBHOOK_CANCEL);
                        break;
                    }

                    // update existing partner-mapping
                    $this->domain .= '/teams/' . $team . '/as_roster/'
                        . $this->webhook->subscriber['commissioner_id']
                        . '/rosters/' . $roster;

                    // update request and let PostWebhooks complete
                    $this->setData(array('roster' => $send));
                    parent::put();
                }
                break;
            case self::WEBHOOK_REMOVE_ROLE:
                // get RosterID from partner-mapping
                $roster = parent::readPartnerMap(
                    self::PARTNER_MAP_USER,
                    $data['member']['uuid'],
                    $data['group']['uuid']
                );
                $roster = $roster['external_resource_id'];

                // get TeamID from partner-mapping
                $team = parent::readPartnerMap(
                    self::PARTNER_MAP_GROUP,
                    $data['group']['uuid'],
                    $data['group']['uuid']
                );
                $team = $team['external_resource_id'];

                $this->domain .= '/teams/' . $team . '/as_roster/'
                    . $this->webhook->subscriber['commissioner_id']
                    . '/rosters/' . $roster;

                // build payload
                $send = array(
                    'non_player' => $data['member']['role_name'] == 'Player' ? 1 : 0,
                    'is_manager' => $data['member']['is_admin'] ? 1 : 0,
                );

                // update request and let PostWebhooks complete
                $this->setData(array('roster' => $send));
                parent::put();
                break;
            case self::WEBHOOK_ADD_SUBMISSION:
                // get RosterID from partner-mapping
                $roster = $method = '';
                $roster = parent::readPartnerMap(
                    self::PARTNER_MAP_USER,
                    $data['member']['uuid'],
                    $data['group']['uuid']
                );

                // create new user on TeamSnap, if resource was not found
                if (isset($roster['message'])) {
                    $method = self::HTTP_POST;
                } elseif (isset($roster['external_resource_id'])) {
                    $method = self::HTTP_PUT;
                    $roster = $roster['external_resource_id'];
                }

                // get TeamID from partner-mapping
                $team = parent::readPartnerMap(
                    self::PARTNER_MAP_GROUP,
                    $data['group']['uuid'],
                    $data['group']['uuid']
                );
                $team = $team['external_resource_id'];

                // build payload, default to acct info for missing items
                $send = array();
                $webform = $data['webform']['data'];
                if (isset($webform['profile__field_firstname__profile'])) {
                    $send['first'] = $webform['profile__field_firstname__profile'];
                } elseif ($method == self::HTTP_POST && !isset($webform['profile__field_firstname__profile'])) {
                    // required element missing, use profile info
                    $send['first'] = $data['member']['first_name'];
                }
                if (isset($webform['profile__field_lastname__profile'])) {
                    $send['last'] = $webform['profile__field_lastname__profile'];
                } elseif ($method == self::HTTP_POST && !isset($webform['profile__field_lastname__profile'])) {
                    // required element missing, use profile info
                    $send['last'] = $data['member']['last_name'];
                }

                // override email with guardian email, if present
                if (isset($data['member']['guardian'])) {
                    $send['roster_email_addresses_attributes'] = array(
                        array(
                            'label' => 'Guardian Profile',
                            'email' => $data['member']['guardian']['email'],
                        ),
                    );
                } else {
                    if (isset($webform['profile__field_email__profile'])) {
                        $send['roster_email_addresses_attributes'] = array(
                            array(
                                'label' => 'Webform',
                                'email' => $webform['profile__field_email__profile'],
                            ),
                        );
                    } elseif ($method == self::HTTP_POST && !isset($webform['profile__field_email__profile'])) {
                        // required element missing, use profile info
                        $send['roster_email_addresses_attributes'] = array(
                            array(
                                'label' => 'Profile',
                                'email' => $data['member']['email'],
                            ),
                        );
                    }
                }

                if (isset($webform['profile__field_birth_date__profile'])) {
                    $send['birthdate'] = $webform['profile__field_birth_date__profile'];
                }
                if (isset($webform['profile__field_user_gender__profile'])) {
                    $send['gender'] = $webform['profile__field_user_gender__profile'] == 1 ? 'Male' : 'Female';
                }

                // add roster phone numbers, if present
                $roster_telephones_attributes = array();
                if (isset($webform['profile__field_phone__profile'])) {
                    $roster_telephones_attributes[] = array(
                        'label' => 'Home',
                        'phone_number' => $webform['profile__field_phone__profile'],
                    );
                }
                if (isset($webform['profile__field_phone_cell__profile'])) {
                    $roster_telephones_attributes[] = array(
                        'label' => 'Cell',
                        'phone_number' => $webform['profile__field_phone_cell__profile'],
                    );
                }
                if (isset($webform['profile__field_work_number__profile'])) {
                    $roster_telephones_attributes[] = array(
                        'label' => 'Work',
                        'phone_number' => $webform['profile__field_work_number__profile'],
                    );
                }
                if (count($roster_telephones_attributes) > 0) {
                    $send['roster_telephones_attributes'] = $roster_telephones_attributes;
                }

                // add address fields, if present
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

                // update request and let PostWebhooks complete
                $this->setData(array('roster' => $send));
                if ($method == self::HTTP_POST) {
                    $this->domain .= '/teams/' . $team . '/as_roster/'
                        . $this->webhook->subscriber['commissioner_id']
                        . '/rosters';
                    parent::post();
                } else {
                    $this->domain .= '/teams/' . $team . '/as_roster/'
                        . $this->webhook->subscriber['commissioner_id']
                        . '/rosters/' . $roster;
                    parent::put();
                }
                break;
            case self::WEBHOOK_CREATE_EVENT:
                // make/get location resource
                $location = $this->getLocationResource(
                    $data['group']['uuid'],
                    isset($data['event']['location']) ? $data['event']['location'] : null
                );

                // build payload
                $send = array(
                    'eventname' => $data['event']['title'],
                    'division_id' => $this->webhook->subscriber['division_id'],
                    'league_controlled' => false,
                    'event_date_start' => $data['event']['start'],
                    'event_date_end' => $data['event']['end'],
                    'location_id' => $location,
                );

                // add additional information to payload
                if (isset($data['event']['description']) && !empty($data['event']['description'])) {
                    $send['notes'] = $data['event']['description'];
                }

                // determine if game or event
                if (isset($data['event']['competitor']) && !empty($data['event']['competitor'])) {
                    $original_domain = $this->domain;
                    $original_send = $send;

                    foreach ($data['event']['competitor'] as $group) {
                        // get TeamID from partner-mapping
                        $team = parent::readPartnerMap(
                            self::PARTNER_MAP_GROUP,
                            $group['uuid'],
                            $group['uuid']
                        );
                        $team = $team['external_resource_id'];

                        // get OpponentID from partner-mapping
                        $opponent = $this->getOpponentResource(
                            $group['uuid'],
                            $team,
                            $data['event']['competitor']
                        );

                        // update payload to make a game event
                        $send = $original_send;
                        $send['opponent_id'] = $opponent;
                        $this->domain = $original_domain . '/teams/' . $team
                            . '/as_roster/'
                            . $this->webhook->subscriber['commissioner_id']
                            . '/games';

                        // update payload and process the response
                        $this->setData(array('game' => $send));
                        parent::post();
                        $response = $this->send();
                        $response = $this->processJsonResponse($response);

                        // manually create event partner-mapping
                        parent::createPartnerMap(
                            $response['game']['id'],
                            self::PARTNER_MAP_EVENT,
                            $data['event']['uuid'],
                            $group['uuid']
                        );

                        // reset temp variables for next iteration
                        $this->domain = $original_domain;
                    }

                    // cancel webhook for game events (manually processed)
                    parent::setSend(parent::WEBHOOK_CANCEL);
                } else {
                    // get TeamID from partner-mapping
                    $team = parent::readPartnerMap(
                        self::PARTNER_MAP_GROUP,
                        $data['group']['uuid'],
                        $data['group']['uuid']
                    );

                    $team = $team['external_resource_id'];
                    $this->domain .= '/teams/' . $team . '/as_roster/'
                        . $this->webhook->subscriber['commissioner_id']
                        . '/practices';
                }

                // update request and let PostWebhooks complete
                $this->setData(array('practice' => $send));
                parent::post();
                break;
            case self::WEBHOOK_UPDATE_EVENT:
                // build payload
                $send = array(
                    'eventname' => $data['event']['title'],
                    'division_id' => $this->webhook->subscriber['division_id'],
                    'event_date_start' => $data['event']['start'],
                    'event_date_end' => $data['event']['end'],
                );

                // add additional information to payload
                if (isset($data['event']['description']) && !empty($data['event']['description'])) {
                    $send['notes'] = $data['event']['description'];
                }

                // determine if game or event
                if (isset($data['event']['competitor']) && !empty($data['event']['competitor'])) {
                    $original_domain = $this->domain;
                    $original_send = $send;

                    foreach ($data['event']['competitor'] as $group) {
                        // get TeamID from partner-mapping
                        $team = parent::readPartnerMap(
                            self::PARTNER_MAP_GROUP,
                            $group['uuid'],
                            $group['uuid']
                        );
                        $team = $team['external_resource_id'];

                        // get EventID from partner-mapping
                        $event = parent::readPartnerMap(
                            self::PARTNER_MAP_EVENT,
                            $data['event']['uuid'],
                            $group['uuid']
                        );
                        $event = $event['external_resource_id'];

                        // make/get location resource
                        $event_location = isset($data['event']['location']) ? $data['event']['location'] : null;
                        $location = $this->getLocationResource(
                            $group['uuid'],
                            $event_location
                        );

                        // get opponent resource
                        $opponent = $this->getOpponentResource(
                            $group['uuid'],
                            $team,
                            $data['event']['competitor']
                        );

                        // update request payload and send
                        $send = $original_send;
                        $send['location_id'] = $location;
                        $send['opponent_id'] = $opponent;
                        $this->domain = $original_domain . '/teams/' . $team
                            . '/as_roster/'
                            . $this->webhook->subscriber['commissioner_id']
                            . '/games/' . $event;
                        $this->setData(array('game' => $send));
                        parent::put();
                        $this->send();

                        // reset temp variables for next iteration
                        $this->domain = $original_domain;
                    }

                    // cancel webhook for game events (manually processed)
                    parent::setSend(parent::WEBHOOK_CANCEL);
                } else {
                    // get TeamID from partner-mapping
                    $team = parent::readPartnerMap(
                        self::PARTNER_MAP_GROUP,
                        $data['group']['uuid'],
                        $data['group']['uuid']
                    );
                    $team = $team['external_resource_id'];

                    // get EventID from partner-mapping
                    $event = parent::readPartnerMap(
                        self::PARTNER_MAP_EVENT,
                        $data['event']['uuid'],
                        $data['group']['uuid']
                    );
                    $event = $event['external_resource_id'];

                    // make/get location resource
                    $event_location = isset($data['event']['location']) ? $data['event']['location'] : null;
                    $location = $this->getLocationResource(
                        $data['group']['uuid'],
                        $event_location
                    );

                    // update request and let PostWebhooks complete
                    $send['location_id'] = $location;
                    $this->domain .= '/teams/' . $team . '/as_roster/'
                        . $this->webhook->subscriber['commissioner_id']
                        . '/practices/' . $event;
                    $this->setData(array('practice' => $send));
                    parent::put();
                }
                break;
            case self::WEBHOOK_DELETE_EVENT:
                // determine if game or event
                if (isset($data['event']['competitor']) && !empty($data['event']['competitor'])) {
                    $original_domain = $this->domain;

                    foreach ($data['event']['competitor'] as $group) {
                        // get TeamID from partner-mapping
                        $team = parent::readPartnerMap(
                            self::PARTNER_MAP_GROUP,
                            $group['uuid'],
                            $group['uuid']
                        );
                        $team = $team['external_resource_id'];

                        // get EventID from partner-mapping
                        $event = parent::readPartnerMap(
                            self::PARTNER_MAP_EVENT,
                            $data['event']['uuid'],
                            $group['uuid']
                        );
                        $event = $event['external_resource_id'];

                        // update request payload and send
                        $this->domain = $original_domain . '/teams/' . $team
                            . '/as_roster/'
                            . $this->webhook->subscriber['commissioner_id']
                            . '/games/' . $event;
                        parent::delete();
                        $this->send();

                        // reset temp variables for next iteration
                        $this->domain = $original_domain;
                    }

                    // cancel webhook for game events (manually processed)
                    parent::setSend(parent::WEBHOOK_CANCEL);
                } else {
                    // get TeamID from partner-mapping
                    $team = parent::readPartnerMap(
                        self::PARTNER_MAP_GROUP,
                        $data['group']['uuid'],
                        $data['group']['uuid']
                    );
                    $team = $team['external_resource_id'];

                    // get EventID from partner-mapping
                    $event = parent::readPartnerMap(
                        self::PARTNER_MAP_EVENT,
                        $data['event']['uuid'],
                        $data['group']['uuid']
                    );
                    $event = $event['external_resource_id'];

                    // update request and let PostWebhooks complete
                    $this->domain .= '/teams/' . $team . '/as_roster/'
                        . $this->webhook->subscriber['commissioner_id']
                        . '/practices/' . $event;
                    parent::delete();
                }

                break;
            default:
                // Cancel webhook if not supported
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
            // if testing, account for extra json wrapper from requestbin
            $response = $this->processJsonResponse($response);
            $response = json_decode($response['body'], true);
        } else {
            $response = $this->processJsonResponse($response);
        }

        // get original data sent from AllPlayers webhook
        $data = $this->getData();
        $original_data = $this->getOriginalData();
        switch ($original_data['webhook_type']) {
            case self::WEBHOOK_CREATE_GROUP:
                // associate AllPlayers group UUID with TeamSnap TeamID
                parent::createPartnerMap(
                    $response['team']['id'],
                    self::PARTNER_MAP_GROUP,
                    $original_data['group']['uuid'],
                    $original_data['group']['uuid']
                );
                break;
            case self::WEBHOOK_DELETE_GROUP:
                // delete partner-mapping with group UUID
                parent::deletePartnerMap(
                    self::PARTNER_MAP_GROUP,
                    $original_data['group']['uuid']
                );
                break;
            case self::WEBHOOK_ADD_ROLE:
            case self::WEBHOOK_ADD_SUBMISSION:
                // get UserID from partner-mapping
                $query = parent::readPartnerMap(
                    self::PARTNER_MAP_USER,
                    $original_data['member']['uuid'],
                    $original_data['group']['uuid']
                );

                if (isset($query['message'])) {
                    // associate AllPlayers user UUID with TeamSnap RosterID
                    parent::createPartnerMap(
                        $response['roster']['id'],
                        self::PARTNER_MAP_USER,
                        $original_data['member']['uuid'],
                        $original_data['group']['uuid']
                    );

                    // create partner-mapping for ContactID
                    if (isset($original_data['member']['guardian'])) {
                        $this->domain = 'https://api.teamsnap.com/v2';
                        $this->getContactResource(
                            $original_data['group']['uuid'],
                            $original_data['member']['uuid'],
                            $original_data['member']['guardian']
                        );
                    }

                    // get TeamID from partner-mapping
                    $team = parent::readPartnerMap(
                        self::PARTNER_MAP_GROUP,
                        $original_data['group']['uuid'],
                        $original_data['group']['uuid']
                    );
                    // send TeamSnap account invite
                    $this->domain = 'https://api.teamsnap.com/v2/teams/'
                        . $team['external_resource_id'] . '/as_roster/'
                        . $this->webhook->subscriber['commissioner_id']
                        . '/invitations';
                    $send = array(
                        $response['roster']['id'],
                    );

                    // update request and send
                    $this->setData(array('rosters' => $send));
                    parent::post();
                    $this->send();
                }
                break;
            case self::WEBHOOK_CREATE_EVENT:
                // associate AllPlayers event UUID with TeamSnap EventID
                parent::createPartnerMap(
                    $response['practice']['id'],
                    self::PARTNER_MAP_EVENT,
                    $original_data['event']['uuid'],
                    $original_data['group']['uuid']
                );
                break;
            case self::WEBHOOK_DELETE_EVENT:
                // delete partner-mapping with event UUID
                parent::deletePartnerMap(
                    self::PARTNER_MAP_EVENT,
                    $original_data['event']['uuid'],
                    $original_data['group']['uuid']
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
            // if sport is supported
            return self::$sports[$data];
        } else {
            // return Non-Sport Group
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
            $user_timezone += -1; //adjust for DST
        }

        // convert to UTC (non-DST) format
        $user_timezone = str_replace(
            '.',
            ':',
            number_format((floor($user_timezone) + ($user_timezone - floor($user_timezone)) * 60), 2)
        );

        if (isset(self::$regions[$user_timezone])) {
            // if region is supported
            return self::$regions[$user_timezone];
        } else {
            // return default UTC region
            return array(
                'timezone' => 'UTC',
                'location' => 'United Kingdom',
            );
        }
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
        $original_domain = $this->domain; // store old domain
        $location = '';

        // get TeamID from partner-mapping
        $team = parent::readPartnerMap(
            self::PARTNER_MAP_GROUP,
            $group_uuid,
            $group_uuid
        );
        $team = $team['external_resource_id'];

        if (isset($event_location) && !empty($event_location)) {
            // get LocationID from partner-mapping
            $location = parent::readPartnerMap(
                self::PARTNER_MAP_RESOURCE,
                $event_location['uuid'],
                $group_uuid
            );
        }

        // set default data if something is missing
        if (!isset($event_location['title']) || empty($event_location['title'])) {
            $event_location['title'] = '(TBD)';
        }
        if (!isset($event_location['street']) || empty($event_location['street'])) {
            $event_location['street'] = '(TBD)';
        }

        // build payload
        $send = array(
            'location_name' => $event_location['title'],
            'address' => $event_location['street'],
        );

        // add additional information to payload
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

        // update request
        $this->setData(array('location' => $send));

        if (isset($location['external_resource_id'])) {
            // update existing partner-mapping
            $location = $location['external_resource_id'];
            $this->domain = $original_domain . '/teams/' . $team . '/as_roster/'
                . $this->webhook->subscriber['commissioner_id'] . '/locations/'
                . $location;

            parent::put();
            $this->send();
        } else {
            // create new partner-mapping
            $this->domain = $original_domain . '/teams/' . $team . '/as_roster/'
                . $this->webhook->subscriber['commissioner_id'] . '/locations';

            parent::post();
            $response = $this->send();
            $response = $this->processJsonResponse($response);

            if (isset($event_location['uuid']) && !empty($event_location['uuid'])) {
                // associate AllPlayers resouce UUID with TeamSnap LocationID
                parent::createPartnerMap(
                    $response['location']['id'],
                    parent::PARTNER_MAP_RESOURCE,
                    $event_location['uuid'],
                    $group_uuid
                );
            }

            $location = $response['location']['id'];
        }

        // restore the old domain, return LocationID
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
                // get OpponentID
                $opponent = parent::readPartnerMap(
                    self::PARTNER_MAP_GROUP,
                    $competitor['uuid'],
                    $group_uuid
                );

                // set default data if something is missing
                if (!isset($competitor['name']) || empty($competitor['name'])) {
                    $competitor['name'] = '(TBD)';
                }

                // build payload
                $send = array(
                    'opponent_name' => $competitor['name'],
                    'opponent_contact_name' => '(TBD)',
                );

                // update request
                $this->setData(array('opponent' => $send));

                if (isset($opponent['external_resource_id'])) {
                    // update existing partner-mapping
                    $opponent = $opponent['external_resource_id'];
                    $this->domain = $original_domain . '/teams/'
                        . $group_partner_id . '/as_roster/'
                        . $this->webhook->subscriber['commissioner_id']
                        . '/opponents/' . $opponent;

                    parent::put();
                    $this->send();
                } else {
                    // create partner-mapping
                    $this->domain = $original_domain . '/teams/'
                        . $group_partner_id . '/as_roster/'
                        . $this->webhook->subscriber['commissioner_id']
                        . '/opponents';

                    parent::post();
                    $response = $this->send();
                    $response = $this->processJsonResponse($response);

                    if (isset($competitor['uuid']) && !empty($competitor['uuid'])) {
                        // assiciate AllPlayers group UUID with TeamSnap TeamID
                        parent::createPartnerMap(
                            $response['opponent']['id'],
                            parent::PARTNER_MAP_GROUP,
                            $competitor['uuid'],
                            $group_uuid
                        );
                    }

                    $opponent = $response['opponent']['id'];
                }
            }

            // reset temp variables for next iteration
            $this->domain = $original_domain;
        }

        // restore old domain, return OpponentID
        $this->domain = $original_domain;
        return $opponent;
    }

    /**
     * Get the partner-mapped contact resource ID for TeamSnap.
     *
     * This will create the resource if it does not already exist.
     *
     * @param string $group_uuid
     *   The group that the user belongs too.
     * @param string $user_uuid
     *   The user that has a contact.
     * @param array $contact_info
     *   The contacts information.
     *
     * @return string
     *   The resource ID for TeamSnap API calls.
     */
    public function getContactResource($group_uuid, $user_uuid, array $contact_info)
    {
        $original_domain = $this->domain; // store old domain
        $contact = '';

        // get TeamID from partner-mapping
        $team = parent::readPartnerMap(
            self::PARTNER_MAP_GROUP,
            $group_uuid,
            $group_uuid
        );
        $team = $team['external_resource_id'];

        // get ContactID from partner-mapping
        $contact = parent::readPartnerMap(
            self::PARTNER_MAP_USER,
            $contact_info['uuid'],
            $user_uuid
        );

        // get UserID from partner-mapping
        $user = parent::readPartnerMap(
            parent::PARTNER_MAP_USER,
            $user_uuid,
            $group_uuid
        );
        $user = $user['external_resource_id'];

        // set default data if something is missing
        if (!isset($contact_info['first_name']) || empty($contact_info['first_name'])) {
            $contact_info['first_name'] = '(Guardian)';
        }

        // make payload
        $send = array(
            'label' => 'Guardian',
            'first' => $contact_info['first_name'],
        );

        // add additional contact information, if present
        if (isset($contact_info['last_name']) && !empty($contact_info['last_name'])) {
            $send['last'] = $contact_info['last_name'];
        }

        if (isset($contact['external_resource_id'])) {
            // update existing partner-mapping
            $contact = $contact['external_resource_id'];
            $this->domain = $original_domain . '/teams/' . $team . '/as_roster/'
                . $this->webhook->subscriber['commissioner_id'] . '/rosters/'
                . $user .  '/contacts/' . $contact;

            $this->setData(array('contact' => $send));
            parent::put();
            $this->send();
        } else {
            // create new partner-mapping
            $this->domain = $original_domain . '/teams/' . $team . '/as_roster/'
                . $this->webhook->subscriber['commissioner_id'] .  '/rosters/'
                . $user . '/contacts';

            // add additional information to payload
            if (isset($contact_info['email']) && !empty($contact_info['email'])) {
                $send['contact_email_addresses_attributes'] = array(
                    array(
                        'label' => 'Profile',
                        'email' => $contact_info['email'],
                    ),
                );
            }

            $this->setData(array('contact' => $send));
            parent::post();
            $response = $this->send();
            $response = $this->processJsonResponse($response);

            if (isset($contact_info['uuid']) && !empty($contact_info['uuid'])) {
                // associate AllPlayers user UUID with TeamSnap ContactID
                parent::createPartnerMap(
                    $response['contact']['id'],
                    parent::PARTNER_MAP_USER,
                    $contact_info['uuid'],
                    $user_uuid
                );
            }

            // update contact id
            $contact = $response['contact']['id'];
        }

        // restore the old domain
        $this->domain = $original_domain;
        return $contact;
    }
}
