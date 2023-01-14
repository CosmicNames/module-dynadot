<?php

Configure::set('Dynadot.tlds', [
    '.com'
]);

// Transfer fields
Configure::set('Dynadot.transfer_fields', [
    'domain' => [
        'label' => Language::_('Dynadot.transfer.domain', true),
        'type' => 'text'
    ],
    'auth' => [
        'label' => Language::_('Dynadot.transfer.EPPCode', true),
        'type' => 'text'
    ],
]);

// Domain fields
Configure::set('Dynadot.domain_fields', [
    'domain' => [
        'label' => Language::_('Dynadot.domain.domain', true),
        'type' => 'text'
    ],
]);

// Nameserver fields
Configure::set('Dynadot.nameserver_fields', [
    'ns1' => [
        'label' => Language::_('Dynadot.nameserver.ns1', true),
        'type' => 'text'
    ],
    'ns2' => [
        'label' => Language::_('Dynadot.nameserver.ns2', true),
        'type' => 'text'
    ],
    'ns3' => [
        'label' => Language::_('Dynadot.nameserver.ns3', true),
        'type' => 'text'
    ],
    'ns4' => [
        'label' => Language::_('Dynadot.nameserver.ns4', true),
        'type' => 'text'
    ],
    'ns5' => [
        'label' => Language::_('Dynadot.nameserver.ns5', true),
        'type' => 'text'
    ]
]);

Configure::set('Dynadot.whois_fields', [
    /*
    'nickname' => array(
        'label' => Language::_("Namesilo.whois.Nickname", true),
        'type' => "text",
        'key' => 'nn',
    ),
    */
    'first_name' => [
        'label' => Language::_('Dynadot.whois.FirstName', true),
        'type' => 'text',
        'rp' => 'fn',
        'lp' => 'first_name',
    ],
    'last_name' => [
        'label' => Language::_('Dynadot.whois.LastName', true),
        'type' => 'text',
        'rp' => 'ln',
        'lp' => 'last_name',
    ],
    'organization' => [
        'label' => Language::_('Dynadot.whois.Organization', true),
        'type' => 'text',
        'rp' => 'organization',
        'lp' => 'organization',
    ],
    'address' => [
        'label' => Language::_('Dynadot.whois.Address1', true),
        'type' => 'text',
        'rp' => 'address1',
        'lp' => 'address1',
    ],
    'address2' => [
        'label' => Language::_('Dynadot.whois.Address2', true),
        'type' => 'text',
        'rp' => 'address2',
        'lp' => 'address2',
    ],
    'city' => [
        'label' => Language::_('Dynadot.whois.City', true),
        'type' => 'text',
        'rp' => 'city',
        'lp' => 'city',
    ],
    'state' => [
        'label' => Language::_('Dynadot.whois.StateProvince', true),
        'type' => 'text',
        'rp' => 'state',
        'lp' => 'state',
    ],
    'zip' => [
        'label' => Language::_('Dynadot.whois.PostalCode', true),
        'type' => 'text',
        'rp' => 'zip',
        'lp' => 'zip',
    ],
    'country' => [
        'label' => Language::_('Dynadot.whois.Country', true),
        'type' => 'text',
        'rp' => 'country',
        'lp' => 'country',
    ],
    'phonenum' => [
        'label' => Language::_('Dynadot.whois.Phone', true),
        'type' => 'text',
        'rp' => 'phonenum',
        'lp' => 'phone',
    ],
    'email' => [
        'label' => Language::_('Dynadot.whois.EmailAddress', true),
        'type' => 'text',
        'rp' => 'email',
        'lp' => 'email',
    ],
]);