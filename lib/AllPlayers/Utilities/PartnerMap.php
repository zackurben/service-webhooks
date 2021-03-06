<?php
/**
 * @file
 * Contains /AllPlayers/Utilities/PartnerMap.
 *
 * Provides the Partner-Mapping API functionality.
 */

namespace AllPlayers\Utilities;

use AllPlayers\Utilities\Helper;
use Guzzle\Http\Client;
use Guzzle\Http\Plugin\CookiePlugin;
use Guzzle\Http\CookieJar\ArrayCookieJar;

/**
 * Provides Partner-Mapping API functionality via function calls.
 */
class PartnerMap
{
    /**
     * A string value for an available partner mapping option.
     *
     * @var string
     */
    const PARTNER_MAP_USER = 'user';

    /**
     * A string value for an available partner mapping option.
     *
     * @var string
     */
    const PARTNER_MAP_EVENT = 'event';

    /**
     * A string value for an available partner mapping option.
     *
     * @var string
     */
    const PARTNER_MAP_GROUP = 'group';

    /**
     * A string value for an available partner mapping option.
     *
     * @var string
     */
    const PARTNER_MAP_RESOURCE = 'resource';

    /**
     * A string value for an available partner mapping subtype option.
     *
     * @var string
     */
    const PARTNER_MAP_SUBTYPE_USER_EMAIL = 'email';

    /**
     * A string value for an available partner mapping subtype option.
     *
     * @var string
     */
    const PARTNER_MAP_SUBTYPE_USER_CONTACT = 'contact';

    /**
     * A string value for an available partner mapping subtype option.
     *
     * @var string
     */
    const PARTNER_MAP_SUBTYPE_USER_CONTACT_EMAIL = 'contact_email';

    /**
     * A string value for an available partner mapping subtype option.
     *
     * @var string
     */
    const PARTNER_MAP_SUBTYPE_USER_PHONE = 'phone';

    /**
     * A string value for an available partner mapping subtype option.
     *
     * @var string
     */
    const PARTNER_MAP_SUBTYPE_USER_PHONE_CELL = 'phone_cell';

    /**
     * A string value for an available partner mapping subtype option.
     *
     * @var string
     */
    const PARTNER_MAP_SUBTYPE_USER_PHONE_WORK = 'phone_work';

    /**
     * The base url for the AllPlayers Partner-Mapping API.
     *
     * @var string
     */
    const PARTNER_MAPPING_URL_BASE = 'https://api.allplayers.com/api/v2/externalid';

    /**
     * The base url for the AllPlayers test Partner-Mapping API.
     *
     * @var string
     */
    const PARTNER_MAPPING_URL_BASE_TEST = 'https://api.pdup.allplayers.com/api/v2/externalid';

    /**
     * The AllPlayers APIv1 Authentication endpoint for cookie authentication.
     *
     * @var string
     */
    const ALLPLAYERS_AUTHENTICATION_URL = 'https://www.allplayers.com/api/v1/rest/users/login.json';

    /**
     * The AllPlayers test APIv1 Authentication endpoint for cookie authentication.
     *
     * @var string
     */
    const ALLPLAYERS_AUTHENTICATION_URL_TEST = 'https://www.pdup.allplayers.com/api/v1/rest/users/login.json';

    /**
     * An enumerated value for determining the current environment.
     *
     * @var integer
     */
    const ALLPLAYERS_ENV_TEST = 0;

    /**
     * An enumerated value for determining the current environment.
     *
     * @var integer
     */
    const ALLPLAYERS_ENV_PROD = 1;

    /**
     * The name of the cookie for AllPlayers Partner-Mapping API Authentication.
     *
     * @var CookiePlugin|null
     */
    protected $cookie = null;

    /**
     * The partner id for identifying the owner in the Partner-Mapping API.
     *
     * @var string
     */
    private $partner_id;

    /**
     * The Helper object used to format Webhook response data.
     *
     * @var Helper|null
     */
    protected $helper = null;

    /**
     * An enumerated value to determine our current environment.
     *
     * @var int
     *
     * @see ALLPLAYERS_ENV_TEST
     * @see ALLPLAYERS_ENV_PROD
     */
    protected $environment = self::ALLPLAYERS_ENV_TEST;

    /**
     * Create the PartnerMap instance and create the AllPlayers auth Cookie.
     *
     * @param string $partner_id
     *   The partner id for identifying the owner of a resource.
     * @param string $username
     *   The AllPlayers username for APIv1 authentication.
     * @param string $password
     *   The AllPlayers password for APIv1 authentication.
     */
    public function __construct($partner_id, $username, $password)
    {
        // Determine if we need to change the environment settings.
        include __DIR__ . '/../../../resque/config/config.php';
        if (isset($config['test_env']) && !$config['test_env']) {
            $this->environment = self::ALLPLAYERS_ENV_PROD;
        }

        $this->partner_id = $partner_id;
        $this->helper = new Helper();
        $this->makeCookie($username, $password);
    }

    /**
     * Make the AllPlayers API cookie for the subsequent requests.
     *
     * This is only required if the webhook is using the Partner-Mapping API.
     *
     * @param string $username
     *   The AllPlayers username for logging into APIv1.
     * @param string $password
     *   The AllPlayers password for logging into APIv1.
     */
    private function makeCookie($username, $password)
    {
        // Fetch the AllPlayers Authentication cookie.
        $this->cookie = new CookiePlugin(new ArrayCookieJar());

        // Set the client parameters for the current environment.
        if ($this->environment == self::ALLPLAYERS_ENV_PROD) {
            $url = self::ALLPLAYERS_AUTHENTICATION_URL;
            $cookie_client = new Client($url);
        } else {
            $url = self::ALLPLAYERS_AUTHENTICATION_URL_TEST;
            $headers = array(
                'curl.CURLOPT_SSL_VERIFYPEER' => false,
                'curl.CURLOPT_CERTINFO' => false,
            );
            $cookie_client = new Client($url, $headers);
        }

        $cookie_client->addSubscriber($this->cookie);
        $cookie_auth = $cookie_client->post(
            $cookie_client->getBaseUrl(),
            array('Content-Type' => 'application/x-www-form-urlencoded'),
            'email=' . $username . '&password=' . $password
        );
        $cookie_auth->send();

        // Set the AllPlayers auth Header for subsequent API calls.
        $temp = $this->cookie->getCookieJar()->all();
        foreach ($temp as $c) {
            if (strstr($c->getName(), "SESS")) {
                $this->api_headers['X-Token'] = hash("sha256", $c->getValue());
                break;
            }
        }
    }

    /**
     * Create a resource mapping between AllPlayers and a partner.
     *
     * @param string $external_resource_id
     *   The partner resource id to map.
     * @param string $item_type
     *   The AllPlayers item type to map.
     * @param string $item_uuid
     *   The AllPlayers item uuid to map.
     * @param string $group_uuid
     *   The AllPlayers group uuid associated with the item uuid.
     * @param string $sub_item_type (Optional)
     *   The resource subtype to map.
     *
     * @return array
     *   The AllPlayers response from creating a resource mapping.
     *
     * @see PARTNER_MAP_USER
     * @see PARTNER_MAP_EVENT
     * @see PARTNER_MAP_GROUP
     * @see PARTNER_MAP_RESOURCE
     * @see PARTNER_MAP_SUBTYPE_USER_EMAIL
     * @see PARTNER_MAP_SUBTYPE_USER_CONTACT
     * @see PARTNER_MAP_SUBTYPE_USER_CONTACT_EMAIL
     */
    public function createPartnerMap(
        $external_resource_id,
        $item_type,
        $item_uuid,
        $group_uuid,
        $sub_item_type = null
    ) {
        // Set the client parameters for the current environment.
        if ($this->environment == self::ALLPLAYERS_ENV_PROD) {
            $url = self::PARTNER_MAPPING_URL_BASE;
            $client = new Client($url);
        } else {
            $url = self::PARTNER_MAPPING_URL_BASE_TEST;
            $headers = array(
                'curl.CURLOPT_SSL_VERIFYPEER' => false,
                'curl.CURLOPT_CERTINFO' => false,
            );
            $client = new Client($url, $headers);
        }

        $client->addSubscriber($this->cookie);

        // Set the required data fields.
        $data = array(
            'external_resource_id' => $external_resource_id,
            'item_type' => $item_type,
            'item_uuid' => $item_uuid,
            'group_uuid' => $group_uuid,
            'partner' => $this->partner_id,
        );

        // Add the item subtype if present.
        if (!is_null($sub_item_type)) {
            $data['sub_item_type'] = $sub_item_type;
        }

        // Send an API request and return the response.
        $request = $client->post(
            $client->getBaseUrl(),
            array_merge(array('Content-Type' => 'application/json'), $this->api_headers),
            json_encode($data)
        );

        $response = $request->send();
        $response = $this->helper->processJsonResponse($response);

        return $response;
    }

    /**
     * Read a resource mapping between AllPlayers and a partner.
     *
     * If the partner_uuid parameter is not included, this function will return
     * all the elements mapped with the item_uuid.
     *
     * @param string $item_type
     *   The AllPlayers item type to map.
     * @param string $item_uuid
     *   The AllPlayers item uuid to map.
     * @param string $group_uuid
     *   The AllPlayers group uuid associated with the item uuid.
     * @param string $sub_item_type (Optional)
     *   The resource subtype to map.
     *
     * @return array
     *   The AllPlayers response from reading a resource mapping.
     *
     * @see PARTNER_MAP_USER
     * @see PARTNER_MAP_EVENT
     * @see PARTNER_MAP_GROUP
     * @see PARTNER_MAP_RESOURCE
     * @see PARTNER_MAP_SUBTYPE_USER_EMAIL
     * @see PARTNER_MAP_SUBTYPE_USER_CONTACT
     * @see PARTNER_MAP_SUBTYPE_USER_CONTACT_EMAIL
     */
    public function readPartnerMap(
        $item_type,
        $item_uuid,
        $group_uuid,
        $sub_item_type = 'entity'
    ) {
        $url = '/' . $item_type . '/' . $item_uuid . '/' . $this->partner_id
            . '/' . $group_uuid . '?sub_item_type=' . $sub_item_type;

        // Set the client parameters for the current environment.
        if ($this->environment == self::ALLPLAYERS_ENV_PROD) {
            $url = self::PARTNER_MAPPING_URL_BASE . $url;
            $client = new Client($url);
        } else {
            $url = self::PARTNER_MAPPING_URL_BASE_TEST . $url;
            $headers = array(
                'curl.CURLOPT_SSL_VERIFYPEER' => false,
                'curl.CURLOPT_CERTINFO' => false,
            );
            $client = new Client($url, $headers);
        }

        $client->addSubscriber($this->cookie);

        // Send an API request and return the response.
        $request = $client->get($client->getBaseUrl(), $this->api_headers);
        $response = $request->send();
        $response = $this->helper->processJsonResponse($response);

        // Remove the nested array with one element.
        if (!array_key_exists('message', $response)) {
            $response = array_shift($response);
        }

        return $response;
    }

    /**
     * Delete a resource mapping between AllPlayers and a partner.
     *
     * Either item_type and item_uuid or partner and group_uuid have to be set.
     *
     * If the item_type and item_uuid are included, the entities associated with
     * them will be removed.
     *
     * If the group_uuid alone is set, all entities associated with the group
     * will be removed.
     *
     * @param string $item_type
     *   The AllPlayers item type to map.
     * @param string $group_uuid
     *   The AllPlayers group uuid.
     * @param string $item_uuid (Optional)
     *   The AllPlayers item uuid to map.
     * @param string $sub_item_type (Optional)
     *   The resource subtype to map.
     *
     * @return array
     *   The AllPlayers response from deleting the resource mapping.
     *
     * @see PARTNER_MAP_USER
     * @see PARTNER_MAP_EVENT
     * @see PARTNER_MAP_GROUP
     * @see PARTNER_MAP_RESOURCE
     * @see PARTNER_MAP_SUBTYPE_USER_EMAIL
     * @see PARTNER_MAP_SUBTYPE_USER_CONTACT
     * @see PARTNER_MAP_SUBTYPE_USER_CONTACT_EMAIL
     */
    public function deletePartnerMap(
        $item_type = null,
        $group_uuid = null,
        $item_uuid = null,
        $sub_item_type = null
    ) {
        $url_frag = array();
        if ($item_type != null) {
            $url_frag['item_type'] = $item_type;
        }
        if ($group_uuid != null) {
            $url_frag['group_uuid'] = $group_uuid;
        }
        if ($item_uuid != null) {
            $url_frag['item_uuid'] = $item_uuid;
        }
        if ($sub_item_type != null) {
            $url_frag['sub_item_type'] = $sub_item_type;
        }
        if ($this->partner_id != null) {
            $url_frag['partner'] = $this->partner_id;
        }
        $url = '?' . http_build_query($url_frag);

        // Set the client parameters for the current environment.
        if ($this->environment == self::ALLPLAYERS_ENV_PROD) {
            $url = self::PARTNER_MAPPING_URL_BASE . $url;
            $client = new Client($url);
        } else {
            $url = self::PARTNER_MAPPING_URL_BASE_TEST . $url;
            $headers = array(
                'curl.CURLOPT_SSL_VERIFYPEER' => false,
                'curl.CURLOPT_CERTINFO' => false,
            );
            $client = new Client($url, $headers);
        }

        $client->addSubscriber($this->cookie);

        // Send an API request and return the response.
        $response = $client->delete(
            $client->getBaseUrl(),
            array_merge(
                array('Content-Type' => 'application/json'),
                $this->api_headers
            )
        )->send();
        $response = $this->helper->processJsonResponse($response);

        return $response;
    }
}
