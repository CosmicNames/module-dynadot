<?php

use Blesta\Core\Util\Validate\Server;
use Blesta\Core\Util\Common\Traits\Container;

/**
 * Dynadot Module
 *
 * @package blesta
 * @subpackage blesta.components.modules.dynadot
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @link http://www.blesta.com/ Blesta
 * @copyright Copyright (c) 2015-2018, NETLINK IT SERVICES
 * @link http://www.netlink.ie/ NETLINK
 */
class Dynadot extends RegistrarModule
{
    // Load traits
    use Container;

    /**
     * @var string Debug email address
     */
    private static $debug_to = 'root@localhost';

    /**
     * @var string Default module view path
     */
    private static $defaultModuleView;

    /**
     * Initializes the module
     */
    public function __construct()
    {
        // Load configuration required by this module
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load components required by this module
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this module
        Language::loadLang('dynadot', null, dirname(__FILE__) . DS . 'language' . DS);

        Configure::load('dynadot', dirname(__FILE__) . DS . 'config' . DS);

        // Set default module view
        self::$defaultModuleView = 'components' . DS . 'modules' . DS . 'dynadot' . DS;
    }

    /**
     * Returns the rendered view of the manage module page.
     *
     * @param mixed $module A stdClass object representing the module and its rows
     * @param array $vars An array of post data submitted to or on the manager module
     *  page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the manager module page
     */
    public function manageModule($module, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('manage', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView(self::$defaultModuleView);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        $this->view->set('module', $module);

        return $this->view->fetch();
    }

    /**
     * Returns the rendered view of the add module row page
     *
     * @param array $vars An array of post data submitted to or on the add module row page
     *  (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the add module row page
     */
    public function manageAddRow(array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('add_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView(self::$defaultModuleView);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        $this->view->set('vars', (object) $vars);
        return $this->view->fetch();
    }

    /**
     * Returns the rendered view of the edit module row page.
     *
     * @param stdClass $module_row The stdClass representation of the existing module row
     * @param array $vars An array of post data submitted to or on the edit
     *  module row page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the edit module row page
     */
    public function manageEditRow($module_row, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('edit_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView(self::$defaultModuleView);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        $api = $this->getApi($module_row->meta->api_key);
        $domainApi = new DynadotDomains($api);
        $result = $domainApi->getTLDPricing();
        $response = $result->response();

        //$this->printJson($response->TldPriceResponse->TldPrice);

        if (empty($vars)) {
            $vars = $module_row->meta;
        }

        $this->view->set('vars', (object) $vars);

        return $this->view->fetch();
    }

    /**
     * Builds and returns the rules required to add/edit a module row (e.g. server).
     *
     * @param array $vars An array of key/value data pairs
     * @return array An array of Input rules suitable for Input::setRules()
     */
    private function getRowRules(&$vars)
    {
        $rules = [
            'api_key' => [
                'valid' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Dynadot.!error.api_key.valid', true)
                ]
            ],
        ];

        return $rules;
    }

    /**
     * Validates that the given hostname is valid.
     *
     * @param string $host_name The host name to validate
     * @return bool True if the hostname is valid, false otherwise
     */
    public function validateHostName($host_name)
    {
        $validator = new Server();

        return $validator->isDomain($host_name) || $validator->isIp($host_name) || empty($host_name);
    }

    /**
     * Validates that at least 2 name servers are set in the given array of name servers.
     *
     * @param array $name_servers An array of name servers
     * @return bool True if the array count is >= 2, false otherwise
     */
    public function validateNameServerCount($name_servers)
    {
        if (is_array($name_servers) && count($name_servers) >= 2) {
            return true;
        }

        return false;
    }

    /**
     * Validates that the nameservers given are formatted correctly.
     *
     * @param array $name_servers An array of name servers
     * @return bool True if every name server is formatted correctly, false otherwise
     */
    public function validateNameServers($name_servers)
    {
        if (is_array($name_servers)) {
            foreach ($name_servers as $name_server) {
                if (!$this->validateHostName($name_server)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Initializes the DynadotApi and returns an instance of that object
     *
     * @param string $key The key to use when connecting
     * @return DynadotApi The DynadotApi instance
     */
    public function getApi($key = null)
    {
        Loader::load(__DIR__ . DS . 'apis' . DS . 'dynadot_api.php');

        if (empty($key)) {
            if (($row = $this->getModuleRow())) {
                $key = $row->meta->api_key;
            }
        }

        return new DynadotApi($key);
    }


    /**
     * Adds the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being added.
     *
     * @param array $vars An array of module info to add
     * @return array A numerically indexed array of meta fields for the module row containing:
     *
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function addModuleRow(array &$vars)
    {
        $meta_fields = ['api_key'];
        $encrypted_fields = ['api_key'];

        $this->Input->setRules($this->getRowRules($vars));

        // Validate module row
        if ($this->Input->validates($vars)) {
            // Build the meta data for this row
            $meta = [];
            foreach ($vars as $key => $value) {
                if (in_array($key, $meta_fields)) {
                    $meta[] = [
                        'key' => $key,
                        'value' => $value,
                        'encrypted' => in_array($key, $encrypted_fields) ? 1 : 0
                    ];
                }
            }

            return $meta;
        }
    }

    /**
     * Edits the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being updated.
     *
     * @param stdClass $module_row The stdClass representation of the existing module row
     * @param array $vars An array of module info to update
     * @return array A numerically indexed array of meta fields for the module row containing:
     *
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function editModuleRow($module_row, array &$vars)
    {
        $meta_fields = ['api_key'];
        $encrypted_fields = ['api_key'];

        // Merge package settings on to the module row meta
        $module_row_meta = array_merge((array) $module_row->meta, $vars);

        $this->Input->setRules($this->getRowRules($vars));

        // Validate module row
        if ($this->Input->validates($vars)) {
            // Build the meta data for this row
            $meta = [];
            foreach ($module_row_meta as $key => $value) {
                if (in_array($key, $meta_fields) || array_key_exists($key, (array) $module_row->meta)) {
                    $meta[] = [
                        'key' => $key,
                        'value' => $value,
                        'encrypted' => in_array($key, $encrypted_fields) ? 1 : 0
                    ];
                }
            }

            return $meta;
        }
    }


    /**
     * Validates input data when attempting to add a package, returns the meta
     * data to save when adding a package. Performs any action required to add
     * the package on the remote server. Sets Input errors on failure,
     * preventing the package from being added.
     *
     * @param array An array of key/value pairs used to add the package
     * @return array A numerically indexed array of meta fields to be stored for this package containing:
     *
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function addPackage(array $vars = null)
    {
        // Set rules to validate input data
        $this->Input->setRules($this->getPackageRules($vars));

        // Build meta data to return
        $meta = [];
        if ($this->Input->validates($vars)) {
            if (!isset($vars['meta'] )) {
                return [];
            }

            // Return all package meta fields
            foreach ($vars['meta'] as $key => $value) {
                $meta[] = [
                    'key' => $key,
                    'value' => $value,
                    'encrypted' => 0
                ];
            }
        }

        return $meta;
    }

    /**
     * Validates input data when attempting to edit a package, returns the meta
     * data to save when editing a package. Performs any action required to edit
     * the package on the remote server. Sets Input errors on failure,
     * preventing the package from being edited.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array An array of key/value pairs used to edit the package
     * @return array A numerically indexed array of meta fields to be stored for this package containing:
     *
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function editPackage($package, array $vars = null)
    {
        // Set rules to validate input data
        $this->Input->setRules($this->getPackageRules($vars));

        // Build meta data to return
        $meta = [];
        if ($this->Input->validates($vars)) {
            if (!isset($vars['meta'] )) {
                return [];
            }

            // Return all package meta fields
            foreach ($vars['meta'] as $key => $value) {
                $meta[] = [
                    'key' => $key,
                    'value' => $value,
                    'encrypted' => 0
                ];
            }
        }

        return $meta;
    }

    /**
     * Builds and returns rules required to be validated when adding/editing a package.
     *
     * @param array $vars An array of key/value data pairs
     * @return array An array of Input rules suitable for Input::setRules()
     */
    private function getPackageRules(array $vars)
    {
        // Validate the package fields
        $rules = [
            'epp_code' => [
                'valid' => [
                    'ifset' => true,
                    'rule' => ['in_array', [0, 1]],
                    'message' => Language::_('Dynadot.!error.epp_code.valid', true)
                ]
            ]
        ];

        return $rules;
    }

     /**
     * Edits the service on the remote server. Sets Input errors on failure,
     * preventing the service from being edited.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $vars An array of user supplied info to satisfy the request
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being edited (if the current service is an addon service)
     * @return array A numerically indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function editService($package, $service, array $vars = null, $parent_package = null, $parent_service = null)
    {
        return null; // All this handled by admin/client tabs instead
    }


    /**
     * Attempts to validate service info. This is the top-level error checking method. Sets Input errors on failure.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request
     * @return bool True if the service validates, false otherwise. Sets Input errors when false.
     */
    public function validateService($package, array $vars = null)
    {
        $this->Input->setRules($this->getServiceRules($vars));

        return $this->Input->validates($vars);
    }

    /**
     * Attempts to validate an existing service against a set of service info updates. Sets Input errors on failure.
     *
     * @param stdClass $service A stdClass object representing the service to validate for editing
     * @param array $vars An array of user-supplied info to satisfy the request
     * @return bool True if the service update validates or false otherwise. Sets Input errors when false.
     */
    public function validateServiceEdit($service, array $vars = null)
    {
        $this->Input->setRules($this->getServiceRules($vars, true));

        return $this->Input->validates($vars);
    }

    /**
     * Returns the rule set for adding/editing a service
     *
     * @param array $vars A list of input vars
     * @param bool $edit True to get the edit rules, false for the add rules
     * @return array Service rules
     */
    private function getServiceRules(array $vars = null, $edit = false)
    {
        // Validate the service fields
        $rules = [
            'domain' => [
                'valid' => [
                    'if_set' => $edit,
                    'rule' => function ($domain) {
                        $validator = new Server();

                        return $validator->isDomain($domain);
                    },
                    'message' => Language::_('Dynadot.!error.domain.valid', true)
                ]
            ],
            'ns' => [
                'count'=>[
                    'rule' => [[$this, 'validateNameServerCount']],
                    'message' => Language::_('Dynadot.!error.ns_count', true)
                ],
                'valid'=>[
                    'rule'=>[[$this, 'validateNameServers']],
                    'message' => Language::_('Dynadot.!error.ns_valid', true)
                ]
            ]
        ];

        return $rules;
    }

    /**
     * Checks if a feature is enabled for a given service
     *
     * @param string $feature The name of the feature to check if it's enabled (e.g. id_protection)
     * @param stdClass $service An object representing the service
     * @return bool True if the feature is enabled, false otherwise
     */
    private function featureServiceEnabled($feature, $service)
    {
        // Get service option groups
        foreach ($service->options as $option) {
            if ($option->option_name == $feature) {
                return true;
            }
        }

        return false;
    }


      /**
     * Get a list of the TLDs supported by the registrar module
     *
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return array A list of all TLDs supported by the registrar module
     */
    public function getTlds($module_row_id = null)
    {
        // Fetch the TLDs results from the cache, if they exist
        $cache = Cache::fetchCache(
            'tlds',
            Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'dynadot' . DS
        );

        if ($cache) {
            $response = unserialize(base64_decode($cache));
        }

        if (!isset($response)) {
            try {
                $row = $this->getModuleRow($module_row_id);
                if (!$row) {
                    $rows = $this->getModuleRows();
                    if (isset($rows[0])) {
                        $row = $rows[0];
                    }
                    unset($rows);
                }

                $api = $this->getApi($row->meta->api_key);
                $domainApi = new DynadotDomains($api);
                $result = $domainApi->getTLDPricing();
                $tlds = $result->response();
                $response = [];

                foreach ($tlds->TldPriceResponse->TldPrice as $tld) {
                    $response[] = $tld->Tld;
                }

                // Save TLDs in cache
                if (Configure::get('Caching.on') && is_writable(CACHEDIR)) {
                    try {
                        Cache::writeCache(
                            'tlds',
                            base64_encode(serialize($response)),
                            strtotime(Configure::get('Blesta.cache_length')) - time(),
                            Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'dynadot' . DS
                        );
                    } catch (Exception $e) {
                        // Write to cache failed, so disable caching
                        Configure::set('Caching.on', false);
                    }
                }
            } catch (Throwable $e) {
                $response = Configure::get('Dynadot.tlds');
            }
        }

        return (array) $response;
    }

    /**
     * Get a list of the TLD prices
     *
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return array A list of all TLDs and their pricing
     *    [tld => [currency => [year# => ['register' => price, 'transfer' => price, 'renew' => price]]]]
     */
    public function getTldPricing($module_row_id = null)
    {
        return $this->getFilteredTldPricing($module_row_id);
    }

    /**
     * Get a filtered list of the TLD prices
     *
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @param array $filters A list of criteria by which to filter fetched pricings including but not limited to:
     *
     *  - tlds A list of tlds for which to fetch pricings
     *  - currencies A list of currencies for which to fetch pricings
     *  - terms A list of terms for which to fetch pricings
     * @return array A list of all TLDs and their pricing
     *    [tld => [currency => [year# => ['register' => price, 'transfer' => price, 'renew' => price]]]]
     */
    public function getFilteredTldPricing($module_row_id = null, $filters = [])
    {
        $this->setModuleRow($this->getModuleRow($module_row_id));
        $tld_prices = $this->getPrices();
        $tld_yearly_prices = [];
        foreach ($tld_prices as $tld => $currency_prices) {
            $tld_yearly_prices[$tld] = [];
            foreach ($currency_prices as $currency => $prices) {
                $tld_yearly_prices[$tld][$currency] = [];
                foreach (range(1, 10) as $years) {
                    // Filter by 'terms'
                    if (isset($filters['terms']) && !in_array($years, $filters['terms'])) {
                        continue;
                    }

                    $tld_yearly_prices[$tld][$currency][$years] = [
                        'register' => $prices->registration * $years,
                        'transfer' => $prices->transfer * $years,
                        'renew' => $prices->renew * $years
                    ];
                }
            }
        }

        return $tld_yearly_prices;
    }

    /**
     * Retrieves all the Dynadot prices
     *
     * @param array $filters A list of criteria by which to filter fetched pricings including but not limited to:
     *
     *  - tlds A list of tlds for which to fetch pricings
     *  - currencies A list of currencies for which to fetch pricings
     * @return array An array containing all the TLDs with their respective prices
     */
    protected function getPrices(array $filters = [])
    {
        // Fetch the TLDs results from the cache, if they exist
        $cache = Cache::fetchCache(
            'tlds_prices',
            Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'dynadot' . DS
        );

        if ($cache) {
            $result = unserialize(base64_decode($cache));
        }

        Loader::loadModels($this, ['Currencies']);

        if (!isset($result)) {
            $row = $this->getRow();
            $api = $this->getApi($row->meta->api_key);
            $domainApi = new DynadotDomains($api);
            $result = $domainApi->getTLDPricing()->response();

            // Save the TLDs results to the cache
            if (
                Configure::get('Caching.on') && is_writable(CACHEDIR)
                && isset($result->TldPriceResponse) && $result->TldPriceResponse->Status == 'success'
            ) {
                try {
                    Cache::writeCache(
                        'tlds_prices',
                        base64_encode(serialize($result)),
                        strtotime(Configure::get('Blesta.cache_length')) - time(),
                        Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'dynadot' . DS
                    );
                } catch (Exception $e) {
                    // Write to cache failed, so disable caching
                    Configure::set('Caching.on', false);
                }
            }
        }

        $tlds = [];
        if (isset($result->TldPriceResponse) && $result->TldPriceResponse->Status == 'success') {
            $tlds = $result->TldPriceResponse->TldPrice;
        }

        // Get all currencies
        $currencies = $this->Currencies->getAll(Configure::get('Blesta.company_id'));

        // Convert namesilo prices to all currencies
        $pricing = [];

        foreach ($tlds as $tld_pricing) {
            $price = $tld_pricing->Price;
            $tld = $tld_pricing->Tld;

            // Filter by 'tlds'
            if (isset($filters['tlds']) && !in_array($tld, $filters['tlds'])) {
                continue;
            }

            foreach ($currencies as $currency) {
                // Filter by 'currencies'
                if (isset($filters['currencies']) && !in_array($currency->code, $filters['currencies'])) {
                    continue;
                }

                $pricing[$tld][$currency->code] = (object) [
                    'registration' => $this->Currencies->convert(
                        is_scalar($price->Register) ? $price->Register : 0,
                        'USD',
                        $currency->code,
                        Configure::get('Blesta.company_id')
                    ),
                    'transfer' => $this->Currencies->convert(
                        is_scalar($price->Transfer) ? $price->Transfer : 0,
                        'USD',
                        $currency->code,
                        Configure::get('Blesta.company_id')
                    ),
                    'renew' => $this->Currencies->convert(
                        is_scalar($price->Renew) ? $price->Renew : 0,
                        'USD',
                        $currency->code,
                        Configure::get('Blesta.company_id')
                    )
                ];
            }
        }

        return $pricing;
    }


    /**
     * Retrieves all the Dynadot module rows
     *
     * @return array An array containing all the module rows
     */
    private function getRows()
    {
        Loader::loadModels($this, ['ModuleManager']);

        $module_rows = [];
        $modules = $this->ModuleManager->getInstalled();

        foreach ($modules as $module) {
            $module_data = $this->ModuleManager->get($module->id);

            foreach ($module_data->rows as $module_row) {
                if (isset($module_row->meta->namesilo_module)) {
                    $module_rows[] = $module_row;
                }
            }
        }

        return $module_rows;
    }

    /**
     * Retrieves the Dynadot module row
     *
     * @return null|stdClass An stdClass object representing the module row if found, otherwise void
     */
    private function getRow()
    {
        $module_rows = $this->getRows();

        return isset($module_rows[0]) ? $module_rows[0] : null;
    }

    /**
     * Retrieves all the Dynadot module row options
     *
     * @return array An array containing all the module row options
     */
    private function getRowsOptions()
    {
        $rows_options = [];
        $module_rows = $this->getRows();

        foreach ($module_rows as $module_row) {
            if (isset($module_row->meta->namesilo_module)) {
                $rows_options[$module_row->id] = $module_row->meta->user;
            }
        }

        return $rows_options;
    }


    /**
     * Prints the given data as a JSON string
     *
     * @param mixed $data The array or object to be printed
     */
    public function printJson($data = [])
    {
        header('Content-type: application/json');
        echo json_encode($data);
        exit;
    }
}