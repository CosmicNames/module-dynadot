<?php

/**
 * Dynadot Nameserver Management
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package dynadot.commands
 */
class DynadotDomainsNs
{
    /**
     * @var DynadotApi
     */
    private $api;

    /**
     * Sets the API to use for communication
     *
     * @param DynadotApi $api
     */
    public function __construct(DynadotApi $api)
    {
        $this->api = $api;
    }

    /***
     * Creates a new nameserver.
     *
     *
     * @param string $host the nameserver to create
     * @param string $ip the nameserver ip address
     * @return DynadotResponse
     */
    public function create(string $host, string $ip): DynadotResponse
    {
        return $this->api->submit('register_ns', [
            'host' => $host,
            'ip' => $ip
        ]);
    }

    /**
     * Deletes a nameserver using a server id that
     * is returned from the create method
     *
     * @param int $server_id the server id
     * @return DynadotResponse
     */
    public function delete(int $server_id): DynadotResponse
    {
        return $this->api->submit('delete_ns', [
            'server_id' => $server_id
        ]);
    }

    /**
     * Retrieves information abut registered nameservers for a domain
     *
     * @param string $domain
     * @return DynadotResponse
     */
    public function getInfo(string $domain)
    {
        return $this->api->submit('get_ns', [
            'domain' => $domain
        ]);
    }

    /**
     * Updates the IP addresses for a registered nameserver
     *
     * @param int $server_id the nameserver server id
     * @param string $ip the ip address to use
     * @return DynadotResponse
     */
    public function update(int $server_id, string $ip): DynadotResponse
    {
        // Todo: allow up to 9 ips to be sent
        $vars = [
            'server_id' => $server_id,
            'ip0' => $ip
        ];

        return $this->api->submit('set_ns_ip', $vars);
    }


}