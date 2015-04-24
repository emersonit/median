<?php
require_once('/median-webapp/config/config.php');
$return_to = $median_base_url; // default path
if (isset($_GET['r']) && trim($_GET['r']) != '') {
    $return_to = trim($_GET['r']);
}
require_once('/median-webapp/lib/simplesaml/lib/_autoload.php');
$saml_auth_service = new SimpleSAML_Auth_Simple('default-sp');
$saml_auth_service->logout( array('ReturnTo' => $return_to) );