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

    public function create($vars): DynadotResponse
    {
        if (isset($vars['contact_id'])) {
            $vars['registrant_contact'] = $vars['contact_id'];
            $vars['admin_contact'] = $vars['contact_id'];
            $vars['technical_contact'] = $vars['contact_id'];
            $vars['billing_contact'] = $vars['contact_id'];
        }

        unset($vars['contact_id']);

        return $this->api->submit('register', $vars);
    }

    public function check($domain): DynadotResponse
    {
        return $this->api->submit('search', [
            'domain0' => $domain
        ]);
    }

    public function getDomainInfo($domain): DynadotResponse
    {
        return $this->api->submit('domain_info', [
            'domain' => $domain
        ]);
    }

    public function getTLDPricing($currency = 'USD'): DynadotResponse
    {
        return $this->api->submit('tld_price', [
            'currency' => $currency
        ]);
    }

    public function setAutoRenewal(string $domain, bool $autorenew = true): DynadotResponse
    {
        return $this->api->submit('set_renew_option', [
            'domain' => $domain,
            'renew_option' => $autorenew ? 'auto' : 'donot'
        ]);
    }

    public function renew(string $domain, int $duration = 1): DynadotResponse
    {
        return $this->api->submit('renew', [
            'domain' => $domain,
            'duration' => $duration
        ]);
    }

    public function addContacts(array $vars): DynadotResponse
    {
        return $this->api->submit('create_contact', $vars);
    }

    public function deleteContacts($contact_id): DynadotResponse
    {
        return $this->api->submit('delete_contact', [
            'contact_id' => $contact_id
        ]);
    }

    public function addPrivacy($domain): DynadotResponse
    {
        return $this->api->submit('set_privacy', [
            'domain' => $domain,
            'option' => 'full'
        ]);
    }

    public function removePrivacy($domain): DynadotResponse
    {
        return $this->api->submit('set_privacy', [
            'domain' => $domain,
            'option' => 'off'
        ]);
    }
}