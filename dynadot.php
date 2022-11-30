<?php

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
    /**
     * @var string Debug email address
     */
    private static $debug_to = 'root@localhost';

    /**
     * @var array Namesilo response codes
     */
    private static $codes;

    /**
     * @var string Default module view path
     */
    private static $defaultModuleView;
}