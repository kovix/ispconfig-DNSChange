<?php

$servers = array();

//type->ispconfig_method
$rr_types = array(
    'AAAA' => 'dns_aaaa_update',
    'A' => 'dns_a_update',
    'CNAME' => 'dns_cname_update',
    'MX' => 'dns_mx_update',
    'NS' => 'dns_ns_update',
    'SRV' => 'dns_srv_update',
    'TXT' => 'dns_txt_update'
);

$config = __DIR__ . '/settings.php';

if (!file_exists($config)) {
    die('### A REQUIRED CONFIG FILE MISSING. PLEASE CHECK README! ###');
}

require_once($config);

if (!isset($server_url) || !filter_var($server_url, FILTER_VALIDATE_URL)) {
    die('### A REQUIRED CONFIG VARIABLE ($server_url) MISSING OR INCORRECT. PLEASE CHECK README! ###');
}

if (empty($username)) {
    die('### A REQUIRED CONFIG VARIABLE ($username) MISSING. PLEASE CHECK README! ###');
}

if (empty($password)) {
    die('### A REQUIRED CONFIG VARIABLE ($password) MISSING. PLEASE CHECK README! ###');
}

try {
    $client = new SoapClient(
        null, 
        array(
            'location' => $server_url . '/index.php',
            'uri' => $server_url,
            'exceptions' => 1,
            'stream_context'=> stream_context_create(
                array(
                    'ssl'=> array(
                        'verify_peer'=>false,
                        'verify_peer_name'=>false
                    )
                )
            ),
            'trace' => false
        )
    );

    $session_id = $client->login( $username, $password);

    if ($dns_servers != 'all') {
        $possible_servers = explode(',', $dns_servers);
    }

    $srvs = $client->server_get_all($session_id);
    if (is_array($srvs) && count($srvs) > 0) {
        foreach ($srvs as $srv) {

            if ($dns_servers == 'all' || in_array($srv['server_id'], $possible_servers)) {
                
                $srvDetail = $client->server_get_functions($session_id, $srv['server_id']);
                
                if (is_array($srvDetail) && $srvDetail['dns_server'] == '1') {
                    $servers[] = array(
                        'id' => $srv['server_id'],
                        'name' => $srv['server_name']
                    );
                }    
            }

        }
    } else {
        die('### NO RESPONSE FOR SERVER LIST (RETURNED 0 SERVERS) ###');
    }

    if (count($servers) == 0) {
        die('### NO VALID DNS SERVER FOUND ( NO DNS SERVER EXISTS IN ISPCONFIG OR MASKED BY $dns_servers VARIABLE) ###');
    } else {
        echo "\n++++++++++ DNS SERVERS TO CHECK ++++++++++\n";
        echo "\n#ID\t\tNAME\n";
        foreach ($servers as $srv) {
            echo $srv['id'] . "\t\t" . $srv['name'] . "\n";
        }
        echo "\n";
    }

    $users = $client->client_get_all($session_id);
    
    if (!is_array($users) || count($users) == 0) {
        die('### NO USERS FOUND ###');
    } else {
        echo "\n++++++++++ FOUND ".count($users)." TO CHECK ++++++++++\n";
    }


    echo str_pad('ORIGIN', 25) .  str_pad('NAME', 25) . str_pad('TYPE', 5) . str_pad('DATA', 25) . str_pad('NEWDATA', 25) . "\n";

    $checked_zones = 0;
    $checked_records = 0;
    $changed_records = 0;

    foreach ($users as $u) {
        foreach ($servers as $s) {
            $zones = $client->dns_zone_get_by_user($session_id, $u, $s['id']);
            if (is_array($zones) ) {
                foreach ($zones as $zone) {
                    $checked_zones++;
                    $records = $client->dns_rr_get_all_by_zone($session_id, $zone['id']);
                    if (is_array($records)) {
                        $checked_records += count($records);
                        foreach ($records as $record) {
                            if (strpos($record['data'], $search) !== false) {
                                $changed_records++;
                                $newdata = str_replace($search, $replace, $record['data']);                                
                                echo str_pad( substr($zone['origin'], 0, 23) , 25) .  str_pad( substr($record['name'], 0, 23) , 25) . str_pad( substr($record['type'], 0, 3) , 5) . str_pad( substr($record['data'], 0, 23) , 25) . str_pad( substr($newdata, 0, 23) , 25); 

                                if ( !array_key_exists($record['type'], $rr_types) ) {
                                    echo str_pad('ERR', 8) . "\n----------------------------Unsupported RR: " . $record['type'];
                                } else {
                                    $setId = $record['id'];
                                    $setArr = simplify_array($record);
                                    $setArr['data'] = $newdata;
    
                                    if ($really_change) {
                                        $client->__soapCall($rr_types[$record['type']], array($session_id, $u, $setId, $setArr));
                                        echo str_pad('OK', 8);
                                    }
                                }
                                echo "\n";
                            }
                        }
                    }
                }
            }
        }
    }

    echo "\n\n+++++++++++++++SUMMARY++++++++++++++++\n\nZones checked: $checked_zones\nRecords checked: $checked_records\nRecords Changed: $changed_records\n\n";

    if (!$really_change) {
        echo '*** WARNING: TEST MODE, NO DATA HAS BEEN CHANGED! ***\n\n';
    }
    

    $client->logout($session_id);


}  catch (SoapFault $e) {
        
    $error = '### SOAP Error: ' . $e->getMessage() . "###";
    die($error);
    
}


function simplify_array($arr) {
    unset($arr['id']);
    unset($arr['sys_userid']);
    unset($arr['sys_groupid']);
    unset($arr['sys_perm_user']);
    unset($arr['sys_perm_group']);
    unset($arr['sys_perm_other']);
    unset($arr['stamp']);
    return $arr;
}