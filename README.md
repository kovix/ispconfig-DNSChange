# ispconfig-DNSChange

Simple script to change a value in IspConfig 3.x dns zones.

## Synopsys

Imagine a situation when you have an IspConfig 3.x multi server setup with more than 300 DNS zones, and you have to change one of your IP addresses, that exists in every dns zone. With this script you can simply replace values in DNS zones.

### Prerequisites
IspConfig 3.x server

## How to use

* Create Soap User in IspConfig (System -> Remote users). Add rights to server_get_all, server_get_functions, client_get_all, dns_zone_get_by_user, dns_rr_get_all_by_zone, dns_*_update
* copy settings.default.php to settings.php and adjust parameters
* Run with really_change = false;
* If everything ok run with really_change = true;


## Questions
Feel free to drop me a message at info@kovix.info

##Warranty, License, etc
Absolutley no warranty, use at your own risk, feel free to modify! (And credit original code, please!)