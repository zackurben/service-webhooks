<?php
/**
 * @file
 * Contains /AllPlayers/Utilities/Helper.
 *
 * Provides helper functions for processing webhooks.
 */

namespace AllPlayers\Utilities;

use Guzzle\Http\Message\Response;

/**
 * A helper object that provides various utility functions.
 */
class Helper
{
    /**
     * Return the JSON object from a Guzzle Response object.
     *
     * @param \Guzzle\Http\Message\Response $response
     *   The Guzzle Response from which to parse the JSON object.
     *
     * @return array
     *   The JSON decoded, associative keyed, array.
     */
    public function processJsonResponse(Response $response)
    {
        $return = '';

        // Strip JSON string data from response message.
        if (strpos($response->getMessage(), "\n[{") !== false) {
            $return = substr(
                $response->getMessage(),
                strpos($response->getMessage(), '[{')
            );
        } else {
            $return = substr(
                $response->getMessage(),
                strpos($response->getMessage(), '{')
            );
        }

        return json_decode($return, true);
    }
}
