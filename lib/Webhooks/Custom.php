<?php
/**
 * @file
 * Provides the Custom webhooks plugin definition.
 */

namespace Webhooks;

/**
 * Defines a custom url webhook that will push events to external app.
 */
class Custom extends Webhook
{
    /**
     * Use custom url as domain.
     */
      public function __construct($args = array(), $domain = '', $authentication = 'no_authentication')
    {
          parent::__construct(array(), $args['url'], $authentication);
    }
}

