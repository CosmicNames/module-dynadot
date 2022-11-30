<?php

use Blesta\Core\Util\Common\Traits\Container;

/**
 * Dynadot API processor
 * 
 * Documentation on Dynadot API: https://www.dynadot.com/domain/api3.html
 */
class DynadotApi
{

    // Load traits
    use Container;

    const LIVE_URL = 'https://api.dynadot.com/api3.xml';

    /**
     * @var string The api key to use when executing API commands
     */
    private $key;

    /**
     * @var array An array representing the last request made
     */
    private $last_request = ['url' => null, 'args' => null];
    
    /**
     * @var int http return code
     */
    public $httpcode;

    /**
     * @param string $key The api key to use when executing commands
     */
    public function __construct($key) 
    {
        $this->key = $key;

        // Initialize logger
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;
    }

    /**
     * Submits a request to the API
     *
     * @param string $command The command to submit
     * @param array $args An array of key/value pair arguments to submit to the given API command
     * @return DynadotResponse The response object
     */
    public function submit($command, array $args = [])
    {
        $url = self::LIVE_URL;
        /*if ($this->sandbox) {
            $url = self::SANDBOX_URL;
        }*/

        $url .= '?key=' . $this->key .'&command=' . $command;

        $this->last_request = [
            'url' => $url,
            'args' => $args
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

        if (Configure::get('Blesta.curl_verify_ssl')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $response = curl_exec($ch);

        if ($response == false) {
            $this->logger->error(curl_error($ch));
        }

        $this->httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return new DynadotResponse($response);
    }

    /**
     * Returns the details of the last request made
     *
     * @return array An array containg:
     *
     *  - url The URL of the last request
     *  - args The paramters passed to the URL
     */
    public function lastRequest()
    {
        return $this->last_request;
    }

}