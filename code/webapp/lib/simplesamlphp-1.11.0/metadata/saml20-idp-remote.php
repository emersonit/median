<?php
/**
 * SAML 2.0 remote IdP metadata for simpleSAMLphp.
 *
 * Remember to remove the IdPs you don't use from this file.
 *
 * See: https://rnd.feide.no/content/idp-remote-metadata-reference
 */

/*
 * Guest IdP. allows users to sign up and register. Great for testing!
 */
// $metadata['https://openidp.feide.no'] = array(
// 	'name' => array(
// 		'en' => 'Feide OpenIdP - guest users',
// 		'no' => 'Feide Gjestebrukere',
// 	),
// 	'description'          => 'Here you can login with your account on Feide RnD OpenID. If you do not already have an account on this identity provider, you can create a new one by following the create new account link and follow the instructions.',
//
// 	'SingleSignOnService'  => 'https://openidp.feide.no/simplesaml/saml2/idp/SSOService.php',
// 	'SingleLogoutService'  => 'https://openidp.feide.no/simplesaml/saml2/idp/SingleLogoutService.php',
// 	'certFingerprint'      => 'c9ed4dfb07caf13fc21e0fec1572047eb8a7a4cb'
// );
//

$metadata['https://login.emerson.edu/saml2/idp/metadata.php'] = array (
  'metadata-set' => 'saml20-idp-remote',
  'entityid' => 'https://login.emerson.edu/saml2/idp/metadata.php',
  'SingleSignOnService' =>
  array (
    0 =>
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://login.emerson.edu/saml2/idp/SSOService.php',
    ),
  ),
  'SingleLogoutService' => 'https://login.emerson.edu/saml2/idp/SingleLogoutService.php',
  'keys' =>
  array (
    0 =>
    array (
      'type' => 'X509Certificate',
      'signing' => true,
      'encryption' => true,
      'X509Certificate' => 'MIIEMTCCAxmgAwIBAgIJAKN0/KN2I4U9MA0GCSqGSIb3DQEBBQUAMIGuMQswCQYDVQQGEwJVUzEWMBQGA1UECAwNTWFzc2FjaHVzZXR0czEPMA0GA1UEBwwGQm9zdG9uMRgwFgYDVQQKDA9FbWVyc29uIENvbGxlZ2UxHzAdBgNVBAsMFkluZm9ybWF0aW9uIFRlY2hub2xvZ3kxGjAYBgNVBAMMEWxvZ2luLmVtZXJzb24uZWR1MR8wHQYJKoZIhvcNAQkBFhByb290QGVtZXJzb24uZWR1MB4XDTE0MDQwMjE5NDgwOVoXDTI0MDQwMTE5NDgwOVowga4xCzAJBgNVBAYTAlVTMRYwFAYDVQQIDA1NYXNzYWNodXNldHRzMQ8wDQYDVQQHDAZCb3N0b24xGDAWBgNVBAoMD0VtZXJzb24gQ29sbGVnZTEfMB0GA1UECwwWSW5mb3JtYXRpb24gVGVjaG5vbG9neTEaMBgGA1UEAwwRbG9naW4uZW1lcnNvbi5lZHUxHzAdBgkqhkiG9w0BCQEWEHJvb3RAZW1lcnNvbi5lZHUwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQC3qJbjTbnDdz4MlwuTXhmzog3R5p475BL7eUX9Q6pj/tM807iaKpXlSgakVnDa38o1+Du/1DGEfZ9MhTE9E4yeztuNbFJVO9ScKdk6a6hZDIW1YUnWIZrW9v52usKS5Cqs8bmHUCCcFx8jZWnmaS6c6rWkGxSN9Kq6AmzClGXID2jubZcEmGPcFjR4Tg4W6Dche3CTZfLHDYRDXxUZ3qpcNVVhcFSxvCkDNr0U9ZGsw1V/kHd9dnGvonhezaJDTiDhUI1xyw/qnQdWsqnrFfMDMmdaRq/OnHXJ7X+H6eZ0wvluz4IBt4r07LcXBn7yvZTRlVBdDThol++6XFjWJVuxAgMBAAGjUDBOMB0GA1UdDgQWBBT6TymEH+rN7J/JGdpM7JeW+38OhDAfBgNVHSMEGDAWgBT6TymEH+rN7J/JGdpM7JeW+38OhDAMBgNVHRMEBTADAQH/MA0GCSqGSIb3DQEBBQUAA4IBAQCMgG0EPvRcyDfcGnUYJmIgiYV8WVg3gILPUQ5T6tPcHJxgvfIcqFigAgWI6A/5rD3m5bVGcj+GfOZ4icDvj5Lftd6uAsu+JTXxGfxMJwoqIozx2hLL73usS21VrZRxatE0IT3F2oWz0eytE82OMCfvwBCBk71IkWN0orTTyrbYGZhvxJWVRtxUvfZ+LEa73Iu/Zg51/frqYYHoBjsX5rVT3H3rBhJqrlHjLS9Fw2Pay/TxAwp5FPC6zmCKXYfrkS9wsWKpmuK5J1Xlup8MqqwguX3zRaIFfYUW1c8bLkP7HCA1YTb7pImqBE8YfRerJbNBdbk8AnS/5GdSLL1oSuHK',
    ),
    1 =>
    array (
      'type' => 'X509Certificate',
      'signing' => true,
      'encryption' => false,
      'X509Certificate' => 'MIIEUTCCA7qgAwIBAgIBQjANBgkqhkiG9w0BAQQFADCBtTELMAkGA1UEBhMCVVMxFjAUBgNVBAgTDU1hc3NhY2h1c2V0dHMxDzANBgNVBAcTBkJvc3RvbjEYMBYGA1UEChMPRW1lcnNvbiBDb2xsZWdlMR8wHQYDVQQLExZJbmZvcm1hdGlvbiBUZWNobm9sb2d5MRgwFgYDVQQDEw9FbWVyc29uIENvbGxlZ2UxKDAmBgkqhkiG9w0BCQEWGWFkbWluaXN0cmF0b3JAZW1lcnNvbi5lZHUwHhcNMDkxMTMwMTQ0MjQ3WhcNMTExMTMwMTQ0MjQ3WjCBnjELMAkGA1UEBhMCVVMxFjAUBgNVBAgTDU1hc3NhY2h1c2V0dHMxGDAWBgNVBAoTD0VtZXJzb24gQ29sbGVnZTEfMB0GA1UECxMWSW5mb3JtYXRpb24gVGVjaG5vbG9neTEcMBoGA1UEAxMTdGFndGVhbS5lbWVyc29uLmVkdTEeMBwGCSqGSIb3DQEJARYPbG9sQGVtZXJzb24uZWR1MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDTmy0QfNucvHJLwEGzVTbCW4/4XlIHXvJ02vdQ6/BpkQo7SK8mceaoMYddK224nl8UnA9gNKMHx9N5eSIzO9IsUYkM69YHJxQGXYs0k8YRzqR5S/YUz7/jU+PGzZr+viMoLy+F5G7pyQwtuL1RkFl2HjRfIptJ7yyn5ouscofMYwIDAQABo4IBhDCCAYAwCQYDVR0TBAIwADAsBglghkgBhvhCAQ0EHxYdT3BlblNTTCBHZW5lcmF0ZWQgQ2VydGlmaWNhdGUwHQYDVR0OBBYEFMjuL1mv8vCW51vSIgU4B+X6CY4sMIHqBgNVHSMEgeIwgd+AFGXBHK5e1CMPF9s7Thd+bTQ54HX7oYG7pIG4MIG1MQswCQYDVQQGEwJVUzEWMBQGA1UECBMNTWFzc2FjaHVzZXR0czEPMA0GA1UEBxMGQm9zdG9uMRgwFgYDVQQKEw9FbWVyc29uIENvbGxlZ2UxHzAdBgNVBAsTFkluZm9ybWF0aW9uIFRlY2hub2xvZ3kxGDAWBgNVBAMTD0VtZXJzb24gQ29sbGVnZTEoMCYGCSqGSIb3DQEJARYZYWRtaW5pc3RyYXRvckBlbWVyc29uLmVkdYIJALPmb40FAKx5MDkGCWCGSAGG+EIBBAQsFipodHRwOi8vd3d3LmVtZXJzb24uZWR1L2luZm90ZWNoL2NhLWNybC5wZW0wDQYJKoZIhvcNAQEEBQADgYEAldLrAFfvBEd7Ef8hr62X29EBTrqrSUzxKFhN8tf7jFp3pAomIdXJ+qNxIGCpVmQDJCOyvch0uAI+odE/LjFT5M7iOifKZLoJupLpIGS1VT9kPKiI5D/U6W6aDyOmocIyVBhRLHjj6VV8R17Xr8vt4jRKucbM3PwTcik/R7ZgLjw=',
    ),
  ),
  'NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
  'base64attributes' => true,
);