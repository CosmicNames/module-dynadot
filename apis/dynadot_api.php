<?php

use Blesta\Core\Util\Common\Traits\Container;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'dynadot_response.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'commands' . DIRECTORY_SEPARATOR . 'domains.php';

/**
 * Dynadot API processor
 * 
 * Documentation on Dynadot API: https://www.dynadot.com/domain/api3.html
 */
class DynadotApi
{

    // Load traits
    use Container;

    const LIVE_URL = 'https://api.dynadot.com/api3.json';

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
    public function submit($command, array $args = [], string $method = 'GET')
    {
        $url = self::LIVE_URL;
        /*if ($this->sandbox) {
            $url = self::SANDBOX_URL;
        }*/

        $url .= '?key=' . $this->key .'&command=' . $command;

        if (count($args) > 0) {
            $url .= '&' . http_build_query($args);
        }
        $this->last_request = [
            'url' => $url,
            'args' => $args
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if (Configure::get('Blesta.curl_verify_ssl')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $response = curl_exec($ch);

        if (curl_errno($ch) || !$response) {
            $error = [
                'error' => 'Curl Error',
                'message' => 'An internal error occurred, or the server did not respond to the request.',
                'status' => 500
            ];
            $this->logger->error(curl_error($ch));

            return new DynadotResponse(['content' => json_encode($error), 'headers' => []]);
        }

        $this->httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = explode("\n", $response);

        // Return request response
        return new DynadotResponse([
                'content' => $data[count($data) - 1],
                'headers' => array_splice($data, 0, count($data) - 1)]
        );
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