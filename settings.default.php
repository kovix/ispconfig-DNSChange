<?php

//IspConfig server SOAP URI, Username and password
$server_url = 'https://yourispconfighost:8080/remote';
$username = 'dnschange';
$password = 'xxxxxx';

$really_change = false; //set to true to actually do the job, otherwise a dry-run will made!

$dns_servers = 'all'; //otherwise single server_id or comma separated values of server_ids (No spaces!!! eg. 1,2,3)

$search = '1.2.3.4';
$replace = '5.6.7.8'; //Warning: No checks made against $replace. It does not have to be a valid ip, you can change for example cname-s os ns-es across the server, so be carful.