<?php

/*

    Median Config Functions

    - getFileAPI() returns the hostname of a random available file API server, for use by a Median function

*/

require_once('/median-webapp/includes/dbconn_mongo.php');

// get the address of a file API server to send API requests to
// depends on the median monitor service to be checking them
function getFileAPI() {
    // use mongodb median6.servers of type "files"
    // or false if none are available
    // returns something like 'http://m6-files-1.dev.emerson.edu:9090/'

    global $m6db;

    $get_fileapi_servers = $m6db->servers->find( array('t' => 'files', 'e' => true) );

    if ($get_fileapi_servers->count() > 0) {
        $fileapi_servers = array();
        foreach ($get_fileapi_servers as $server) {
            $fileapi_url = 'http://';
            if (isset($server['hostname']) && trim($server['hostname']) != '') {
                $fileapi_url .= $server['hostname']; // try for hostname first
            } else {
                $fileapi_url .= $server['ip']; // use IP instead by default
            }
            $fileapi_url .= ':'.$server['port'].'/'; // always include the port and trailing slash
            $fileapi_servers[] = $fileapi_url;
        }
        unset($server);
        return $fileapi_servers[array_rand($fileapi_servers)]; // return a random one
    } else {
        return false;
    }
}
