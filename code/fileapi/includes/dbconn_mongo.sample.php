<?php

/*

    Median MongoDB connection info
    
    I recommend using at least a three-node replica set
    
    docs here: http://php.net/manual/en/mongoclient.construct.php

*/

// try it because it might fail!
try {
    $m = new MongoClient('mongodb://your-server-1.com:27017,your-server-2.com:27017,your-server-3.com:27017', array('replicaSet' => 'mongosetname'));
    $m->setReadPreference(MongoClient::RP_PRIMARY_PREFERRED, array()); // prefer the primary
    $m->setWriteConcern(1); // always have a write concern of 1 by default
    $mdb = $m->median5; // shortcut to the original median database
    $m6db = $m->median6; // shortcut to the median 6 annex database
} catch (MongoConnectionException $e) {
    // report error -- could be done better
    echo 'db error message: '.$e->getMessage()."\n";
    echo 'db error code: '.$e->getCode()."\n";
}
