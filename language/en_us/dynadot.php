<?php
// Basics
$lang['Dynadot.name'] = 'Dynadot';
$lang['Dynadot.description'] = 'Dynadot is an ICANN accredited domain name registrar and web host.';

// Module Management
$lang['Dynadot.add_module_row'] = 'Add Account';
$lang['Dynadot.manage.module_rows_title'] = 'Accounts';
$lang['Dynadot.manage.module_rows_heading.api_key'] = 'API Key';
$lang['Dynadot.manage.module_rows.edit'] = 'Edit';
$lang['Dynadot.manage.module_rows.delete'] = 'Delete';
$lang['Dynadot.manage.module_rows.confirm_delete'] = 'Are you sure you want to delete this account?';
$lang['Dynadot.manage.module_rows_no_results'] = 'There are no accounts.';

// Row Meta
$lang['Dynadot.row_meta.api_key'] = 'API Key';

// Add row
$lang['Dynadot.add_row.box_title'] = 'Add Dynadot Account';
$lang['Dynadot.add_row.basic_title'] = 'Basic Settings';
$lang['Dynadot.add_row.add_btn'] = 'Add Account';

// Edit row
$lang['Dynadot.edit_row.box_title'] = 'Edit Dynadot Account';
$lang['Dynadot.edit_row.basic_title'] = 'Basic Settings';
$lang['Dynadot.edit_row.edit_btn'] = 'Update Account';

// Errors
$lang['Dynadot.!error.api_key.valid'] = 'Please enter an api key';
$lang['Dynadot.!error.epp_code.valid'] = 'EPP Code must be 1 or 0.';
$lang['Dynadot.!error.domain.valid'] = 'Invalid domain';
$lang['Dynadot.!error.ns_count'] = 'At least 2 name servers are required.';
$lang['Dynadot.!error.ns_valid'] = 'One or more name servers are invalid.';

// Transfer fields
$lang['Dynadot.transfer.domain'] = 'Domain';
$lang['Dynadot.transfer.EPPCode'] = 'EPP Code';

// Domain fields
$lang['Dynadot.domain.domain'] = 'Domain';
$lang['Dynadot.domain.Years'] = 'Years';
$lang['Dynadot.domain.WhoisPrivacy'] = 'Whois Privacy';
$lang['Dynadot.domain.DomainAction'] = 'Domain Action';

// Nameserver fields
$lang['Dynadot.nameserver.ns1'] = 'Nameserver 1';
$lang['Dynadot.nameserver.ns2'] = 'Nameserver 2';
$lang['Dynadot.nameserver.ns3'] = 'Nameserver 3';
$lang['Dynadot.nameserver.ns4'] = 'Nameserver 4';
$lang['Dynadot.nameserver.ns5'] = 'Nameserver 5';


// Contact fields
$lang['Dynadot.contact.first_name'] = 'First Name';
$lang['Dynadot.contact.last_name'] = 'Last Name';
$lang['Dynadot.contact.email'] = 'E-mail Address';
$lang['Dynadot.contact.address1'] = 'Address 1';
$lang['Dynadot.contact.address2'] = 'Address 2';
$lang['Dynadot.contact.city'] = 'City';
$lang['Dynadot.contact.state'] = 'State';
$lang['Dynadot.contact.zip'] = 'Zip Code';
$lang['Dynadot.contact.country'] = 'Country';
$lang['Dynadot.contact.phone'] = 'Phone';


// Service Fields
$lang['Dynadot.service_fields.domain'] = 'Domain';


// Package Fields
$lang['Dynadot.package_fields.type'] = 'Type';
$lang['Dynadot.package_fields.type_domain'] = 'Domain Registration';
$lang['Dynadot.package_fields.epp_code'] = 'EPP Code';
$lang['Dynadot.package_fields.ns1'] = 'Nameserver 1';
$lang['Dynadot.package_fields.ns2'] = 'Nameserver 2';
$lang['Dynadot.package_fields.ns3'] = 'Nameserver 3';
$lang['Dynadot.package_fields.ns4'] = 'Nameserver 4';
$lang['Dynadot.package_fields.ns5'] = 'Nameserver 5';

$lang['Dynadot.package_field.tooltip.epp_code'] = 'Whether to allow users to request an EPP Code through the Blesta service interface.';
$lang['Dynadot.package_fields.tld_options'] = 'TLDs';

// Service management
$lang['Dynadot.tab_whois.title'] = 'Whois';
$lang['Dynadot.tab_whois.section_registrant'] = 'Registrant';
$lang['Dynadot.tab_whois.section_admin'] = 'Administrative';
$lang['Dynadot.tab_whois.section_tech'] = 'Technical';
$lang['Dynadot.tab_whois.section_billing'] = 'Billing';
$lang['Dynadot.tab_whois.field_submit'] = 'Update Whois';

$lang['Dynadot.tab_email_forwarding.title'] = 'Email Forwarding';
$lang['Dynadot.tab_email_forwarding.desc'] = 'Email forwarding is automatically directing email sent from one address to a different email address. For example, if you had an existing email address of email@email.com, and then registered the domain newdomain.com, you could use email forward to direct sales@newdomain.com to your existing email@email.com email address.';
$lang['Dynadot.tab_email_forwarding.field_email_address'] = 'Email Address';
$lang['Dynadot.tab_email_forwarding.field_forward_to'] = 'Forward To';
$lang['Dynadot.tab_email_forwarding.field_delete'] = 'Delete';
$lang['Dynadot.tab_email_forwarding.field_submit'] = 'Update Forwarders';

$lang['Dynadot.tab_nameservers.title'] = 'Name Servers';
$lang['Dynadot.tab_nameservers.desc'] = 'We allow up to 5 possible name servers, although only 2 are required. It is important that you do not enter the IP address of the name server, but instead enter the actual name server name. Name servers are typically formatted like "NS1.host.com".';
$lang['Dynadot.tab_nameservers.field_ns'] = 'Name Server %1$s'; // %1$s is the name server number
$lang['Dynadot.tab_nameservers.field_submit'] = 'Update Name Servers';

$lang['Dynadot.tab_hosts.title'] = 'Register Nameservers';
$lang['Dynadot.tab_hosts.desc'] = 'If you are already familiar with setting up custom name servers and understand how DNS works, you can create custom name servers and assign it to your domain.';
$lang['Dynadot.tab_hosts.field_host'] = 'Host %1$s'; // %1$s is the host number
$lang['Dynadot.tab_hosts.field_ip'] = 'IP Address(es)';
$lang['Dynadot.tab_hosts.field_hostname'] = 'Host';
$lang['Dynadot.tab_hosts.field_submit'] = 'Update All Hosts';
$lang['Dynadot.tab_client_hosts.help_text'] = 'On this page you can add your own custom name servers (sometimes referred to as "glue records") to associate with your domains.  To remove a host record blank all IP fields associated with it before clicking update.  You can not delete any host records which have domains actively using it as a nameserver.';

$lang['Dynadot.tab_dnssec.title'] = 'DNSSEC';
$lang['Dynadot.tab_dnssec.title_list'] = 'Current DS (DNSSEC) Records';
$lang['Dynadot.tab_dnssec.title_add'] = 'Add DS (DNSSEC) Record';
$lang['Dynadot.tab_dnssec.field_delete'] = 'Delete Record(s)';
$lang['Dynadot.tab_dnssec.field_add'] = 'Add Record';
$lang['Dynadot.tab_dnssec.field_delete'] = 'Delete';
$lang['Dynadot.tab_dnssec.title_disclaimer'] = 'Disclaimer';
$lang['Dynadot.tab_dnssec.warning_message1'] = 'You can use this page to manage the DS records for your domain. You should only use this page if you are comfortable with DS records and DNSSEC.';
$lang['Dynadot.tab_dnssec.warning_message2'] = 'When you manage DS records, <strong>the domain will stop resolving correctly</strong> if your nameservers are not configured correctly with the associated DNSSEC resource records.';

$lang['Dynadot.dnssec.algorithm'] = 'Algorithm';
$lang['Dynadot.dnssec.digest_type'] = 'Digest Type';
$lang['Dynadot.dnssec.digest'] = 'Digest';
$lang['Dynadot.dnssec.key_tag'] = 'Key Tag';

// DNS records
$lang['Dynadot.tab_dnsrecord.title'] = 'DNS Records';
$lang['Dynadot.tab_dnsrecord.title_list'] = 'Current DNS Records';
$lang['Dynadot.tab_dnsrecord.title_add'] = 'Add a DNS Record';
$lang['Dynadot.tab_dnsrecord.field_delete'] = 'Delete Record(s)';
$lang['Dynadot.tab_dnsrecord.field_add'] = 'Add Record';
$lang['Dynadot.tab_dnsrecord.help_text_1'] = 'On this page you can add or delete A, AAAA, CNAME, MX and TXT DNS records. Please be ware that it might take some few minutes for DNS records to propagate.';

$lang['Dynadot.dnsrecord.record_type'] = 'Type';
$lang['Dynadot.dnsrecord.host'] = 'Host';
$lang['Dynadot.dnsrecord.value'] = 'Value';
$lang['Dynadot.dnsrecord.ttl'] = 'TTL';
$lang['Dynadot.dnsrecord.field_delete'] = 'Delete Record(s)';

$lang['Dynadot.dns_records.record_type'] = 'Record Type';
$lang['Dynadot.dns_records.record_type.a_record'] = 'A Record';
$lang['Dynadot.dns_records.record_type.aaaa_record'] = 'AAAA Record';
$lang['Dynadot.dns_records.record_type.cname_record'] = 'CNAME Record';
$lang['Dynadot.dns_records.record_type.mx_record'] = 'MX Record';
$lang['Dynadot.dns_records.record_type.txt_record'] = 'TXT Record';

// Settings
$lang['Dynadot.tab_settings.title'] = 'Settings';
$lang['Dynadot.tab_settings.field_registrar_lock'] = 'Registrar Lock';
$lang['Dynadot.tab_settings.field_registrar_lock_yes'] = 'Set the registrar lock. Recommended to prevent unauthorized transfer.';
$lang['Dynadot.tab_settings.field_registrar_lock_no'] = 'Release the registrar lock so the domain can be transferred.';
$lang['Dynadot.tab_settings.field_request_epp'] = 'Request EPP Code/Transfer Key';
$lang['Dynadot.tab_settings.field_submit'] = 'Update Settings';
$lang['Dynadot.tab_settings.section_verification'] = 'Registrant Email Verification';
$lang['Dynadot.tab_settings.verification_text'] = 'Registrant email verification status for ';
$lang['Dynadot.tab_settings.verified'] = 'Verified';
$lang['Dynadot.tab_settings.not_verified'] = 'NOT VERIFIED';
$lang['Dynadot.tab_settings.not_verified_warning'] = '<strong>WARNING:</strong> Your domain is at risk of being deactivated if you do not verify the registrant email address.';
$lang['Dynadot.tab_settings.field_resend_verification_email'] = 'Resend Verification Email';

$lang['Dynadot.tab_adminactions.title'] = 'Admin Actions';
$lang['Dynadot.tab_adminactions.field_submit'] = 'Send Selected Notice';
$lang['Dynadot.tab_adminactions.sync_date'] = 'Synchronize Renew Date';

$lang['Dynadot.manage.manual_renewal'] = 'Manually Renew (select years)';

// Success messages
$lang['Dynadot.!success.packages_saved'] = 'The packages have been successfully saved.';
$lang['Dynadot.!success.epp_code_sent'] = "The EPP Code/Transfer Key has been sent to the administrative contact for this domain name via email.";

// notices
require_once __DIR__  . '/notices.php';

