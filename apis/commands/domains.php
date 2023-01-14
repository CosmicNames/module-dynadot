<?php

/**
 * Dynadot Domain Management
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package dynadot.commands
 */
class DynadotDomains
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

    public function check($domain): DynadotResponse
    {
        return $this->api->submit('search', [
            'domain0' => $domain
        ]);
    }

    public function getTLDPricing($currency = 'USD'): DynadotResponse
    {
        return $this->api->submit('tld_price', [
            'currency' => $currency
        ]);
    }
}