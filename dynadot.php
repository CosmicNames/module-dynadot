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
     * Process API response, setting an errors, and logging the request
     *
     * @param DynadotApi $api The Dynadot API object
     * @param DynadotResponse $response The Dynadot API response object
     */
    private function processResponse(DynadotApi $api, DynadotResponse $response)
    {
        $this->logRequest($api, $response);

        $status = $response->status();

        // Set errors if non-200 http code
        if ($api->httpcode != 200) {
            $this->Input->setErrors(['errors' => ['API returned non-200 HTTP code']]);
        }

        // Set errors, if any
        $errors = $response->errors();
        if (count($errors) > 0) {
            $this->Input->setErrors(['errors' => (array)$errors]);
        }
    }

    /**
     * Logs the API request
     *
     * @param DynadotApi $api The Dynadot API object
     * @param DynadotResponse $response The Dynadot API response object
     */
    private function logRequest(DynadotApi $api, DynadotResponse $response)
    {
        $last_request = $api->lastRequest();
        $url = substr($last_request['url'], 0, strpos($last_request['url'], '?'));
        $this->log($url, serialize($last_request['args']), 'input', true);
        $this->log($url, serialize($response->response()), 'output', $api->httpcode == 200);
    }

    /**
     * Returns the TLD of the given domain
     *
     * @param string $domain The domain to return the TLD from
     * @param stdClass module row object
     * @return string The TLD of the domain
     */
    private function getTld($domain, $row = null)
    {
        if ($row == null) {
            $row = $this->getRow();
        }

        $tlds = $this->getTlds();
        $domain = strtolower($domain);

        foreach ($tlds as $tld) {
            if (substr($domain, -strlen($tld)) == $tld) {
                return $tld;
            }
        }

        return strstr($domain, '.');
    }

    /**
     * Formats a phone number into +NNN.NNNNNNNNNN
     *
     * @param string $number The phone number
     * @param string $country The ISO 3166-1 alpha2 country code
     * @return string The number in +NNN.NNNNNNNNNN
     */
    private function formatPhone($number, $country)
    {
        if (!isset($this->Contacts)) {
            Loader::loadModels($this, ['Contacts']);
        }

        return $this->Contacts->intlNumber($number, $country, '.');
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
     * Adds the service to the remote server. Sets Input errors on failure,
     * preventing the service from being added.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request
     * @param stdClass $parent_package A stdClass object representing the parent service's selected package
     *  (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent service of the service being added
     *  (if the current service is an addon service and parent service has already been provisioned)
     * @param string $status The status of the service being added. These include:
     *
     *  - active
     *  - canceled
     *  - pending
     *  - suspended
     * @return array A numerically indexed array of meta fields to be stored for this service containing:
     *
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function addService(
        $package,
        array $vars = null,
        $parent_package = null,
        $parent_service = null,
        $status = 'pending'
    )
    {
        $row = $this->getModuleRow($package->module_row);
        $api = $this->getApi($row->meta->api_key);

        #
        # TODO: Handle validation checks
        # TODO: Fix nameservers
        #

        if (isset($vars['domain'])) {
            $tld = $this->getTld($vars['domain'], $row);
            $vars['domain'] = trim($vars['domain']);
        }

        $input_fields = array_merge(
            Configure::get('Dynadot.domain_fields'),
            (array) Configure::get('Dynadot.domain_fields' . $tld),
            (array) Configure::get('Dynadot.nameserver_fields'),
            (array) Configure::get('Dynadot.transfer_fields'),
            ['duration' => true, 'transfer' => $vars['transfer'] ?? 1, 'private' => 0]
        );

        // Set the whois privacy field based on the config option
        if (isset($vars['configoptions']['id_protection'])) {
            $vars['private'] = $vars['configoptions']['id_protection'];
        }

        // .ca and .us domains can't have traditional whois privacy
        if ($tld == '.ca' || $tld == '.us') {
            unset($input_fields['private']);
        }

        if (isset($vars['use_module']) && $vars['use_module'] == 'true') {
            if ($package->meta->type == 'domain') {
                $vars['duration'] = 1;

                foreach ($package->pricing as $pricing) {
                    if ($pricing->id == $vars['pricing_id']) {
                        $vars['duration'] = $pricing->term;
                        break;
                    }
                }

                $whois_fields = Configure::get('Dynadot.whois_fields');

                // Set all whois info from client ($vars['client_id'])
                if (!isset($this->Clients)) {
                    Loader::loadModels($this, ['Clients']);
                }

                if (!isset($this->Contacts)) {
                    Loader::loadModels($this, ['Contacts']);
                }

                $client = $this->Clients->get($vars['client_id']);

                if ($client) {
                    $contact_numbers = $this->Contacts->getNumbers($client->contact_id);
                }

                $whoisContact = [];

                foreach ($whois_fields as $key => $value) {
                    if (str_contains($key, 'phonenum')) {
                        $whoisContact[$value['rp']] = $this->formatPhone(
                            isset($contact_numbers[0]) ? $contact_numbers[0]->number : null,
                            $client->country
                        );
                    } else {
                        $whoisContact[$value['rp']] =
                            (isset($value['lp']) && !empty($value['lp'])) ? $client->{$value['lp']} : 'NA';
                    }
                }

                $fields = array_intersect_key($vars, $input_fields);

                // update name for contact creation
                $whoisContact['name'] = $whoisContact['fn'] .' '. $whoisContact['ln'];

                unset($whoisContact['fn']);
                unset($whoisContact['ln']);

                // create a contact
                $domains = new DynadotDomains($api);
                $response = $domains->addContacts($whoisContact);
                $this->processResponse($api, $response);
                if ($this->Input->errors()) {
                    return;
                }
                $fields['contact_id'] = $response->response()->CreateContactResponse->CreateContactContent->ContactId;

                // Handle transfer
                if (isset($vars['auth']) && $vars['auth']) {
                    $transfer = new DynadotDomainsTransfer($api);

                    $response = $transfer->create($fields);
                    $this->processResponse($api, $response);

                    if ($this->Input->errors()) {
                        if (isset($vars['contact_id'])) {
                            $domains->deleteContacts($vars['contact_id']);
                        }

                        return;
                    }
                } else {
                    // Handle registration
                    $domains = new DynadotDomains($api);

                    $private = $fields['private'];
                    unset($fields['private']);

                    $response = $domains->create($fields);
                    $this->processResponse($api, $response);

                    if ($this->Input->errors()) {
                        // if namesilo is running a promotion on registrations we have to work around their system if
                        // we are doing a multi-year registration
                        $error = 'Invalid number of years, or no years provided.';
                        if (reset($this->Input->errors()['errors']) === $error) {
                            // unset the errors since we are working around it
                            $this->Input->setErrors([]);

                            // set the registration length to 1 year and save the remainder for an extension
                            $total_years = $fields['years'];
                            $fields['duration'] = 1;
                            $response = $domains->create($fields);
                            $this->processResponse($api, $response);

                            // now extend the remainder of the years
                            $fields['duration'] = $total_years - 1;
                            $response = $domains->renew($fields);
                            $this->processResponse($api, $response);
                        }

                        if (isset($vars['contact_id'])) {
                            $domains->deleteContacts($vars['contact_id']);
                        }

                        return;
                    }

                    if (!$private) {
                        $response = $domains->removePrivacy($fields['domain']);
                        $this->processResponse($api, $response);
                    }
                }
            }
        }

        $meta = [];
        $fields = array_intersect_key($vars, $input_fields);
        foreach ($fields as $key => $value) {
            $meta[] = [
                'key' => $key,
                'value' => $value,
                'encrypted' => 0
            ];
        }

        return $meta;
    }

    /**
     * Edits the service on the remote server. Sets Input errors on failure,
     * preventing the service from being edited.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $vars An array of user supplied info to satisfy the request
     * @param stdClass $parent_package A stdClass object representing the parent service's selected package
     *  (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent service of the service being edited
     *  (if the current service is an addon service)
     * @return array A numerically indexed array of meta fields to be stored for this service containing:
     *
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function editService($package, $service, array $vars = [], $parent_package = null, $parent_service = null)
    {
        $row = $this->getModuleRow($package->module_row);
        $api = $this->getApi($row->meta->api_key);
        $domains = new DynadotDomains($api);

        // Manually renew the domain
        $renew = isset($vars['renew']) ? (int) $vars['renew'] : 0;
        if ($renew > 0 && $vars['use_module'] == 'true') {
            $this->renewService($package, $service, $parent_package, $parent_service, $renew);
            unset($vars['renew']);
        }

        // Handle whois privacy via config option
        $id_protection = $this->featureServiceEnabled('id_protection', $service);
        if (!$id_protection && isset($vars['configoptions']['id_protection'])) {
            $response = $domains->addPrivacy($this->getServiceDomain($service));
            $this->processResponse($api, $response);
        } elseif ($id_protection && !isset($vars['configoptions']['id_protection'])) {
            $response = $domains->removePrivacy($this->getServiceDomain($service));
            $this->processResponse($api, $response);
        }

        return null; // All this handled by admin/client tabs instead
    }

    /**
     * Cancels the service on the remote server. Sets Input errors on failure,
     * preventing the service from being canceled.
     */
    public function cancelService($package, $service, $parent_package = null, $parent_service = null)
    {
        $row = $this->getModuleRow($package->module_row);
        $api = $this->getApi($row->meta->api_key);

        if ($package->meta->type == 'domain') {
            $fields = $this->serviceFieldsToObject($service->fields);

            $domains = new DynadotDomains($api);
            $response = $domains->setAutoRenewal($fields->{'domain'}, false);
            $this->processResponse($api, $response);

            if ($this->Input->errors()) {
                return;
            }
        }

        return;
    }

    /**
     * Suspends the service on the remote server. Sets Input errors on failure,
     * preventing the service from being suspended.
     */
    public function suspendService($package, $service, $parent_package = null, $parent_service = null)
    {
        $row = $this->getModuleRow($package->module_row);
        $api = $this->getApi($row->meta->api_key);

        if ($package->meta->type == 'domain') {
            $fields = $this->serviceFieldsToObject($service->fields);

            // Make sure auto renew is off
            $domains = new DynadotDomains($api);
            $response = $domains->setAutoRenewal($fields->{'domain'}, false);
            $this->processResponse($api, $response);

            if ($this->Input->errors()) {
                return;
            }
        }

        return;
    }

    /**
     * Allows the module to perform an action when the service is ready to renew.
     * Sets Input errors on failure, preventing the service from renewing.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param stdClass $parent_package A stdClass object representing the parent service's selected package
     *  (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent service of the service being renewed
     *  (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically indexed array of meta fields to be
     *  stored for this service containing:
     *
     *      - key The key for this meta field
     *      - value The value for this key
     *      - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function renewService($package, $service, $parent_package = null, $parent_service = null, $years = null)
    {
        $row = $this->getModuleRow($package->module_row);
        $api = $this->getApi($row->meta->api_key);

        if ($package->meta->type == 'domain') {
            $fields = $this->serviceFieldsToObject($service->fields);

            $domain = $fields->{'domain'};
            $duration = 1;

            if (!$years) {
                foreach ($package->pricing as $pricing) {
                    if ($pricing->id == $service->pricing_id) {
                        $duration = $pricing->term;
                        break;
                    }
                }
            } else {
                $duration = $years;
            }

            $domains = new DynadotDomains($api);
            $response = $domains->renew($domain, $duration);
            $this->processResponse($api, $response);

            if ($this->Input->errors()) {
                return;
            }
        }

        return null;
    }


    /**
     * Returns all fields used when adding/editing a package, including any
     * javascript to execute when the page is rendered with these fields.
     *
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render as well as any additional
     *  HTML markup to include
     */
    public function getPackageFields($vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        // Fetch all packages available for the given server or server group
        $module_row = null;
        if (isset($vars->module_group) && $vars->module_group == '') {
            if (isset($vars->module_row) && $vars->module_row > 0) {
                $module_row = $this->getModuleRow($vars->module_row);
            } else {
                $rows = $this->getModuleRows();
                if (isset($rows[0])) {
                    $module_row = $rows[0];
                }
                unset($rows);
            }
        } else {
            // Fetch the 1st server from the list of servers in the selected group
            $rows = $this->getModuleRows(isset($vars->module_group) ? $vars->module_group : null);
            if (isset($rows[0])) {
                $module_row = $rows[0];
            }
            unset($rows);
        }

        $fields = new ModuleFields();

        $types = [
            'domain' => Language::_('Dynadot.package_fields.type_domain', true),
        ];

        // Set type of package
        $type = $fields->label(
            Language::_('Dynadot.package_fields.type', true),
            'dynadot_type'
        );
        $type->attach(
            $fields->fieldSelect(
                'meta[type]',
                $types,
                (isset($vars->meta['type']) ? $vars->meta['type'] : null),
                ['id' => 'dynadot_type']
            )
        );
        $fields->setField($type);

        // Set all TLD checkboxes
        $tld_options = $fields->label(Language::_('Dynadot.package_fields.tld_options', true));

        $tlds = $this->getTlds();
        sort($tlds);

        foreach ($tlds as $tld) {
            $tld_label = $fields->label($tld, 'tld_' . $tld);
            $tld_options->attach(
                $fields->fieldCheckbox(
                    'meta[tlds][]',
                    $tld,
                    (isset($vars->meta['tlds']) && in_array($tld, $vars->meta['tlds'])),
                    ['id' => 'tld_' . $tld],
                    $tld_label
                )
            );
        }
        $fields->setField($tld_options);

        $epp_code_label = $fields->label(Language::_('Dynadot.package_fields.epp_code', true));
        $epp_code_label->attach(
            $fields->fieldCheckbox(
                'meta[epp_code]',
                '1',
                $vars->meta['epp_code'] ?? '0' == '1',
                ['id' => 'epp_code'],
                $fields->label(Language::_('Dynadot.package_fields.enable_epp_code', true), 'epp_code')
            )
        );
        $fields->setField($epp_code_label);

        // Set nameservers
        for ($i = 1; $i <= 5; $i++) {
            $type = $fields->label(Language::_('Dynadot.package_fields.ns' . $i, true), 'dynadot_ns' . $i);
            $type->attach(
                $fields->fieldText(
                    'meta[ns][]',
                    (isset($vars->meta['ns'][$i - 1]) ? $vars->meta['ns'][$i - 1] : null),
                    ['id' => 'dynadot_ns' . $i]
                )
            );
            $fields->setField($type);
        }

        $fields->setHtml(
            "
            <script type=\"text/javascript\">
                $(document).ready(function() {
                    toggleTldOptions($('#dynadot_type').val());

                    // Re-fetch module options
                    $('#dynadot_type').change(function() {
                        toggleTldOptions($(this).val());
                    });

                    function toggleTldOptions(type) {
                        if (type == 'ssl')
                            $('.dynadot_tlds').hide();
                        else
                            $('.dynadot_tlds').show();
                    }
                });
            </script>
        "
        );

        return $fields;
    }

    /**
     * Returns all fields to display to an admin attempting to add a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render as well as any additional
     *  HTML markup to include
     */
    public function getAdminAddFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Form', 'Html']);

        if ($package->meta->type == 'domain') {
            // Set default name servers
            if (!isset($vars->ns1) && isset($package->meta->ns)) {
                $i = 1;
                foreach ($package->meta->ns as $ns) {
                    $vars->{'ns' . $i++} = $ns;
                }
            }

            // Handle transfer request
            if ((isset($vars->transfer) && $vars->transfer) || (isset($vars->auth) && $vars->auth)) {
                return $this->arrayToModuleFields(Configure::get('Dynadot.transfer_fields'), null, $vars);
            } else {
                // Handle domain registration
                #
                # TODO: Select TLD, then display additional fields
                #

                $fields = Configure::get('Dynadot.transfer_fields');

                $fields['transfer'] = [
                    'label' => Language::_('Dynadot.domain.DomainAction', true),
                    'type' => 'radio',
                    'value' => '1',
                    'options' => [
                        '0' => 'Register',
                        '1' => 'Transfer',
                    ],
                ];

                $fields['auth'] = [
                    'label' => Language::_('Dynadot.transfer.EPPCode', true),
                    'type' => 'text',
                ];

                $module_fields = $this->arrayToModuleFields(
                    array_merge($fields, Configure::get('Dynadot.nameserver_fields')),
                    null,
                    $vars
                );

                $module_fields->setHtml(
                    "
                    <script type=\"text/javascript\">
                        $(document).ready(function() {
                            $('#transfer_id_0').prop('checked', true);
                            $('#auth_id').closest('li').hide();
                            // Set whether to show or hide the ACL option
                            $('#auth').closest('li').hide();
                            if ($('input[name=\"transfer\"]:checked').val() == '1') {
                                $('#auth_id').closest('li').show();
                            }

                            $('input[name=\"transfer\"]').change(function() {
                                if ($('input[name=\"transfer\"]:checked').val() == '1') {
                                    $('#auth_id').closest('li').show();
                                    $('#ns1_id').closest('li').hide();
                                    $('#ns2_id').closest('li').hide();
                                    $('#ns3_id').closest('li').hide();
                                    $('#ns4_id').closest('li').hide();
                                    $('#ns5_id').closest('li').hide();
                                } else {
                                    $('#auth_id').closest('li').hide();
                                    $('#ns1_id').closest('li').show();
                                    $('#ns2_id').closest('li').show();
                                    $('#ns3_id').closest('li').show();
                                    $('#ns4_id').closest('li').show();
                                    $('#ns5_id').closest('li').show();
                                }
                            });

                            $('input[name=\"transfer\"]').change();
                        });
                    </script>"
                );

                // Build the domain fields
                $fields = $this->buildDomainModuleFields($vars);
                if ($fields) {
                    $module_fields = $fields;
                }
            }
        }

        return (isset($module_fields) ? $module_fields : new ModuleFields());
    }

    /**
     * Returns all fields to display to a client attempting to add a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render as well as any additional
     *  HTML markup to include
     */
    public function getClientAddFields($package, $vars = null)
    {

        // Handle universal domain name
        if (isset($vars->domain)) {
            $vars->domain = $vars->domain;
        }

        if ($package->meta->type == 'domain') {
            // Set default name servers
            if (!isset($vars->ns) && isset($package->meta->ns)) {
                $i = 1;
                foreach ($package->meta->ns as $ns) {
                    $vars->{'ns' . $i++} = $ns;
                }
            }

            if (isset($vars->domain)) {
                $tld = $this->getTld($vars->domain);
            }

            // Handle transfer request
            if ((isset($vars->transfer) && $vars->transfer) || isset($vars->auth)) {
                $fields = array_merge(
                    Configure::get('Dynadot.transfer_fields'),
                    (array) Configure::get('Dynadot.domain_fields' . $tld)
                );

                // .ca domains can't have traditional whois privacy
                if ($tld == '.ca') {
                    unset($fields['private']);
                }

                // We should already have the domain name don't make editable
                $fields['domain']['type'] = 'hidden';
                $fields['domain']['label'] = null;
                // we already know we're doing a transfer, don't make it editable
                $fields['transfer']['type'] = 'hidden';
                $fields['transfer']['label'] = null;

                $module_fields = $this->arrayToModuleFields($fields, null, $vars);

                return $module_fields;
            } else {
                // Handle domain registration
                $fields = array_merge(
                    Configure::get('Dynadot.nameserver_fields'),
                    Configure::get('Dynadot.domain_fields'),
                    (array) Configure::get('Dynadot.domain_fields' . $tld)
                );

                // .ca domains can't have traditional whois privacy
                if ($tld == '.ca') {
                    unset($fields['private']);
                }

                // We should already have the domain name don't make editable
                $fields['domain']['type'] = 'hidden';
                $fields['domain']['label'] = null;

                $module_fields = $this->arrayToModuleFields($fields, null, $vars);
            }
        }

        // Determine whether this is an AJAX request
        return (isset($module_fields) ? $module_fields : new ModuleFields());
    }

    /**
     * Builds and returns the module fields for domain registration
     *
     * @param stdClass $vars An stdClass object representing the input vars
     * @param $client True if rendering the client view, or false for the admin (optional, default false)
     * return mixed The module fields for this service, or false if none could be created
     */
    private function buildDomainModuleFields($vars, $client = false)
    {
        if (isset($vars->domain)) {
            $tld = $this->getTld($vars->domain);

            $extension_fields = Configure::get('Dynadot.domain_fields' . $tld);
            if ($extension_fields) {
                // Set the fields
                $fields = array_merge(Configure::get('Dynadot.domain_fields'), $extension_fields);

                if (!isset($vars->transfer) || $vars->transfer == '0') {
                    $fields = array_merge($fields, Configure::get('Dynadot.nameserver_fields'));
                } else {
                    $fields = array_merge($fields, Configure::get('Dynadot.transfer_fields'));
                }

                if ($client) {
                    // We should already have the domain name don't make editable
                    $fields['domain']['type'] = 'hidden';
                    $fields['domain']['label'] = null;
                }

                // Build the module fields
                $module_fields = new ModuleFields();

                // Allow AJAX requests
                $ajax = $module_fields->fieldHidden('allow_ajax', 'true', ['id' => 'dynadot_allow_ajax']);
                $module_fields->setField($ajax);
                $please_select = ['' => Language::_('AppController.select.please', true)];

                foreach ($fields as $key => $field) {
                    // Build the field
                    $label = $module_fields->label((isset($field['label']) ? $field['label'] : ''), $key);

                    $type = null;
                    if ($field['type'] == 'text') {
                        $type = $module_fields->fieldText(
                            $key,
                            (isset($vars->{$key}) ? $vars->{$key} :
                                (isset($field['options']) ? $field['options'] : '')),
                            ['id' => $key]
                        );
                    } elseif ($field['type'] == 'select') {
                        $type = $module_fields->fieldSelect(
                            $key,
                            (isset($field['options']) ? $please_select + $field['options'] : $please_select),
                            (isset($vars->{$key}) ? $vars->{$key} : ''),
                            ['id' => $key]
                        );
                    } elseif ($field['type'] == 'checkbox') {
                        $type = $module_fields->fieldCheckbox($key, (isset($field['options']) ? $field['options'] : 1));
                        $label = $module_fields->label((isset($field['label']) ? $field['label'] : ''), $key);
                    } elseif ($field['type'] == 'hidden') {
                        $type = $module_fields->fieldHidden(
                            $key,
                            (isset($vars->{$key}) ? $vars->{$key} :
                                (isset($field['options']) ? $field['options'] : '')),
                            ['id' => $key]
                        );
                    }

                    // Include a tooltip if set
                    if (!empty($field['tooltip'])) {
                        $label->attach($module_fields->tooltip($field['tooltip']));
                    }

                    if ($type) {
                        $label->attach($type);
                        $module_fields->setField($label);
                    }
                }
            }
        }

        return (isset($module_fields) ? $module_fields : false);
    }

    /**
     * Returns all fields to display to an admin attempting to edit a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render as well as any additional
     *  HTML markup to include
     */
    public function getAdminEditFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        // Create domain label
        $domain = $fields->label(Language::_('Dynadot.manage.manual_renewal', true), 'renew');
        // Create domain field and attach to domain label
        $domain->attach(
            $fields->fieldSelect(
                'renew',
                [0, '1 year', '2 years', '3 years', '4 years', '5 years'],
                (isset($vars->renew) ? $vars->renew : null),
                ['id' => 'renew']
            )
        );
        // Set the label as a field
        $fields->setField($domain);

        return $fields;
    }

    /**
     * Fetches the HTML content to display when viewing the service info in the
     * admin interface.
     *
     * @param stdClass $service A stdClass object representing the service
     * @param stdClass $package A stdClass object representing the service's package
     * @return string HTML content containing information to display when viewing the service info
     */
    public function getAdminServiceInfo($service, $package)
    {
        return '';
    }

    /**
     * Fetches the HTML content to display when viewing the service info in the
     * client interface.
     *
     * @param stdClass $service A stdClass object representing the service
     * @param stdClass $package A stdClass object representing the service's package
     * @return string HTML content containing information to display when viewing the service info
     */
    public function getClientServiceInfo($service, $package)
    {
        return '';
    }

    /**
     * Returns all tabs to display to an admin when managing a service
     *
     * @param stdClass $service A stdClass object representing the service
     * @return array An array of tabs in the format of method => title.
     *  Example: ['methodName' => "Title", 'methodName2' => "Title2"]
     */
    public function getAdminServiceTabs($service)
    {
        Loader::loadModels($this, ['Packages']);

        $package = $this->Packages->get($service->package_id ?? $service->package->id);

        if ($package->meta->type == 'domain') {
            $tabs = [
                'tabWhois' => Language::_('Dynadot.tab_whois.title', true),
                'tabEmailForwarding' => Language::_('Dynadot.tab_email_forwarding.title', true),
                'tabNameservers' => Language::_('Dynadot.tab_nameservers.title', true),
                'tabHosts' => Language::_('Dynadot.tab_hosts.title', true),
                'tabDnssec' => Language::_('Dynadot.tab_dnssec.title', true),
                'tabDnsRecords' => Language::_('Dynadot.tab_dnsrecord.title', true),
                'tabSettings' => Language::_('Dynadot.tab_settings.title', true),
                'tabAdminActions' => Language::_('Dynadot.tab_adminactions.title', true),
            ];

            // Check if DNS Management is enabled
            if (!$this->featureServiceEnabled('dns_management', $service)) {
                unset($tabs['tabDnssec'], $tabs['tabDnsRecords']);
            }

            // Check if Email Forwarding is enabled
            if (!$this->featureServiceEnabled('email_forwarding', $service)) {
                unset($tabs['tabEmailForwarding']);
            }

            return $tabs;
        }
    }

    /**
     * Returns all tabs to display to a client when managing a service.
     *
     * @param stdClass $service A stdClass object representing the service
     * @return array An array of tabs in the format of method => title, or method => array where array contains:
     *
     *  - name (required) The name of the link
     *  - icon (optional) use to display a custom icon
     *  - href (optional) use to link to a different URL
     *      Example:
     *      ['methodName' => "Title", 'methodName2' => "Title2"]
     *      ['methodName' => ['name' => "Title", 'icon' => "icon"]]
     */
    public function getClientServiceTabs($service)
    {
        Loader::loadModels($this, ['Packages']);

        $package = $this->Packages->get($service->package_id ?? $service->package->id);

        if ($package->meta->type == 'domain') {
            $tabs = [
                'tabClientWhois' => [
                    'name' => Language::_('Dynadot.tab_whois.title', true),
                    'icon' => 'fas fa-users'
                ],
                'tabClientEmailForwarding' => [
                    'name' => Language::_('Dynadot.tab_email_forwarding.title', true),
                    'icon' => 'fas fa-envelope'
                ],
                'tabClientNameservers' => [
                    'name' => Language::_('Dynadot.tab_nameservers.title', true),
                    'icon' => 'fas fa-server'
                ],
                'tabClientHosts' => [
                    'name' => Language::_('Dynadot.tab_hosts.title', true),
                    'icon' => 'fas fa-hdd'
                ],
                'tabClientDnssec' => [
                    'name' => Language::_('Dynadot.tab_dnssec.title', true),
                    'icon' => 'fas fa-globe-americas'
                ],
                'tabClientDnsRecords' => [
                    'name' => Language::_('Dynadot.tab_dnsrecord.title', true),
                    'icon' => 'fas fa-sitemap'
                ],
                'tabClientSettings' => [
                    'name' => Language::_('Dynadot.tab_settings.title', true),
                    'icon' => 'fas fa-cog'
                ]
            ];

            // Check if DNS Management is enabled
            if (!$this->featureServiceEnabled('dns_management', $service)) {
                unset($tabs['tabClientDnssec'], $tabs['tabClientDnsRecords']);
            }

            // Check if Email Forwarding is enabled
            if (!$this->featureServiceEnabled('email_forwarding', $service)) {
                unset($tabs['tabClientEmailForwarding']);
            }

            return $tabs;
        }
    }

    /**
     * Admin Actions tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabAdminActions($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $vars = new stdClass();

        Loader::load(__DIR__ . DS . 'includes' . DS . 'communication.php');

        $communication = new Communication($service);

        $vars->options = $communication->getNotices();

        if (!empty($post)) {
            $fields = $this->serviceFieldsToObject($service->fields);
            $row = $this->getModuleRow($package->module_row);
            $api = $this->getApi($row->meta->api_key);
            $domains = new DynadotDomains($api);

            if (!empty($post['notice'])) {
                $communication->send($post);
            }

            if (isset($post['action']) && $post['action'] == 'sync_date') {
                Loader::loadModels($this, ['Services', 'Domains.DomainsDomains']);

                $domain_info = $domains->getDomainInfo($fields->domain);
                $this->processResponse($api, $domain_info);

                if (!$this->Input->errors()) {
                    $domain_info = $domain_info->response();
                    $expires = $domain_info->DomainInfoResponse->DomainInfo->Expiration;
                    $created = $domain_info->DomainInfoResponse->DomainInfo->Registration;
                    $edit_vars['date_added'] = date('Y-m-d h:i:s', ($created / 1000));
                    $edit_vars['date_renews'] = date('Y-m-d h:i:s', ($expires / 1000));
                    $this->Services->edit($service->id, $edit_vars, $bypass_module = true);
                    $this->DomainsDomains->setExpirationDate($service->id, $edit_vars['date_renews']);
                }
            }
        }

        $this->view = new View('tab_admin_actions', 'default');

        Loader::loadHelpers($this, ['Form', 'Html']);

        $this->view->set('vars', $vars);
        $this->view->setDefaultView(self::$defaultModuleView);

        return $this->view->fetch();
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
            ]
        ];

        // Transfers (EPP Code)
        if (isset($vars['transfer']) && ($vars['transfer'] == '1' || $vars['transfer'] == true)) {
            $rule = [
                'auth' => [
                    'empty' => [
                        'rule' => ['isEmpty'],
                        'negate' => true,
                        'message' => Language::_('Dynadot.!error.epp.empty', true),
                        'post_format' => 'trim'
                    ]
                ],
            ];
            $rules = array_merge($rules, $rule);
        }

        /*'ns' => [
                'count'=>[
                    'rule' => [[$this, 'validateNameServerCount']],
                    'message' => Language::_('Dynadot.!error.ns_count', true)
                ],
                'valid'=>[
                    'rule'=>[[$this, 'validateNameServers']],
                    'message' => Language::_('Dynadot.!error.ns_valid', true)
                ]
            ]*/

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
     * Verifies that the provided domain name is available
     *
     * @param string $domain The domain to lookup
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return bool True if the domain is available, false otherwise
     */
    public function checkAvailability($domain, $module_row_id = null)
    {
        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->api_key);

        $domains = new DynadotDomains($api);
        $result = $domains->check($domain);
        $this->processResponse($api, $result);

        if ($api->httpcode != 200) {
            return false;
        }

        $checkResults = $result->response();

        // if unsuccessful, return false
        if ($checkResults->SearchResponse->ResponseCode == -1) {
            return false;
        }

        // if available, return true
        if ($checkResults->SearchResponse->SearchResults[0]->Available === "yes") {
            return true;
        }

        return false;
    }

    /**
     * Gets the domain expiration date
     *
     * @param stdClass $service The service belonging to the domain to lookup
     * @param string $format The format to return the expiration date in
     * @return string The domain expiration date in UTC time in the given format
     * @see Services::get()
     */
    public function getExpirationDate($service, $format = 'Y-m-d H:i:s')
    {
        Loader::loadHelpers($this, ['Date']);

        $domain = $this->getServiceDomain($service);
        $module_row_id = $service->module_row_id ?? null;

        $row = $this->getModuleRow($module_row_id);
        $api = $this->getApi($row->meta->api_key);

        $domains = new DynadotDomains($api);
        $result = $domains->getDomainInfo($domain);
        $this->processResponse($api, $result);
        $response = $result->response();

        if ($response->DomainInfoResponse->ResponseCode == -1) {
            return false;
        }

        $expires = $response->DomainInfoResponse->DomainInfo->Expiration;

        return $this->Date->format(
            $format,
            ($expires / 1000) ?? date('c')
        );
    }

    /**
     * Gets the domain name from the given service
     *
     * @param stdClass $service The service from which to extract the domain name
     * @return string The domain name associated with the service
     */
    public function getServiceDomain($service)
    {
        if (isset($service->fields)) {
            foreach ($service->fields as $service_field) {
                if ($service_field->key == 'domain') {
                    return $service_field->value;
                }
            }
        }

        return $this->getServiceName($service);
    }


      /**
     * Get a list of the TLDs supported by the registrar module
     *
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return array A list of all TLDs supported by the registrar module
     */
    public function getTlds($module_row_id = null)
    {
        $row = $this->getModuleRow($module_row_id);
        $row = !empty($row) ? $row : $this->getModuleRows()[0];

        // Fetch the TLDs results from the cache, if they exist
        $cache = Cache::fetchCache(
            'tlds',
            Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'dynadot' . DS
        );

        if ($cache) {
            return unserialize(base64_decode($cache));
        }

        // Fetch Dynadot TLDs
        $tlds = [];

        if (empty($row)) {
            return $tlds;
        }

        $api = $this->getApi($row->meta->api_key);
        $domains = new DynadotDomains($api);
        $result = $domains->getTLDPricing();
        $response = $result->response();

        foreach ($response->TldPriceResponse->TldPrice as $tld) {
            $tlds[] = $tld->Tld;
        }

        if (count($tlds) > 0) {
            // Save TLDs in cache
            if (Configure::get('Caching.on') && is_writable(CACHEDIR)) {
                try {
                    Cache::writeCache(
                        'tlds',
                        base64_encode(serialize($tlds)),
                        strtotime(Configure::get('Blesta.cache_length')) - time(),
                        Configure::get('Blesta.company_id') . DS . 'modules' . DS . 'dynadot' . DS
                    );
                } catch (Exception $e) {
                    // Write to cache failed, so disable caching
                    Configure::set('Caching.on', false);
                }
            }
        }

        return $tlds;
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
            $domains = new DynadotDomains($api);
            $result = $domains->getTLDPricing()->response();

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