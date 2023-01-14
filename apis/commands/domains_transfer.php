<?php

/**
 * Dynadot Domain Transfer Management
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package dynadot.commands
 */
class DynadotDomainsTransfer
{
    /**
     * @var DynadotApi
     */
    private $api;

    /**
     * Sets the API to use for communication
     *
     * @param DynadotApi $api The API to use for communication
     */
    public function __construct(DynadotApi $api)
    {
        $this->api = $api;
    }

    public function create($vars): DynadotResponse
    {
        if (isset($vars['contact_id'])) {
            $vars['registrant_contact'] = $vars['contact_id'];
            $vars['admin_contact'] = $vars['contact_id'];
            $vars['technical_contact'] = $vars['contact_id'];
            $vars['billing_contact'] = $vars['contact_id'];
        }

        unset($vars['contact_id']);

        return $this->api->submit('transfer', $vars);
    }
}