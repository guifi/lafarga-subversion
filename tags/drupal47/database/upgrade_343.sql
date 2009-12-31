INSERT INTO `guifi_manufacturer` ( `fid` , `nom` , `url` ) VALUES ( NULL , 'Mikrotik', 'http://mikrotik.com');
ALTER TABLE `guifi_model` CHANGE `modes` `modes` ENUM( '802.11b/g', '802.11b', '802.11a', '802.11a/b/g', 'WiMax', '802.11n' ) NOT NULL DEFAULT '802.11b/g';
ALTER TABLE `guifi_model` ADD `radiodev_max` TINYINT( 2 ) NOT NULL DEFAULT '0' AFTER `tipus` ;
UPDATE `guifi_model` SET `model` = 'WRT54Gv1-4' WHERE `model` = 'WRT54G';
INSERT INTO `guifi_model` (`fid`, `model`, `tipus`, `radiodev_max`, `potencia_max`, `modes`, `AP`, `WDS`, `bridge`, `client`, `connector`, `antenes`, `router`, `firewall`, `QoS`, `snmp`, `hack`, `url`, `comentaris` ) VALUES ( 2, 'WRT54GL', 'Extern', 0, 251, '802.11b/g', 'Si', 'Hack', 'Hack', 'Hack', 'RP-TNC', '2', 'Si', 'Si', 'Hack', 'Hack', 'Si', 'http://www.linksys.com/products/product.', 'El canvi de potència, mode client i WDS, via hack.\r\nHacks disponibles: sveasoft (Satori, Alchemy...), OpenWRT, DD-WRT, ...');
INSERT INTO `guifi_model` (`fid`, `model`, `tipus`, `radiodev_max`, `potencia_max`, `modes`, `AP`, `WDS`, `bridge`, `client`, `connector`, `antenes`, `router`, `firewall`, `QoS`, `snmp`, `hack`, `url`, `comentaris` ) VALUES ( 2, 'WRT54GSv1', 'Extern', 0, 251, '802.11b/g', 'Si', 'Hack', 'Hack', 'Hack', 'RP-TNC', '2', 'Si', 'Si', 'Hack', 'Hack', 'Si', 'http://www.linksys.com/products/product.', 'El canvi de potència, mode client i WDS, via hack.\r\nHacks disponibles: sveasoft (Satori, Alchemy...), OpenWRT, DD-WRT, ...');
INSERT INTO `guifi_model` (`fid`, `model`, `tipus`, `radiodev_max`, `potencia_max`, `modes`, `AP`, `WDS`, `bridge`, `client`, `connector`, `antenes`, `router`, `firewall`, `QoS`, `snmp`, `hack`, `url`, `comentaris` ) VALUES ( 2, 'WRT54GSv2', 'Extern', 0, 251, '802.11b/g', 'Si', 'Hack', 'Hack', 'Hack', 'RP-TNC', '2', 'Si', 'Si', 'Hack', 'Hack', 'Si', 'http://www.linksys.com/products/product.', 'El canvi de potència, mode client i WDS, via hack.\r\nHacks disponibles: sveasoft (Satori, Alchemy...), OpenWRT, DD-WRT, ...');
INSERT INTO `guifi_model` (`fid`, `model`, `tipus`, `radiodev_max`, `potencia_max`, `modes`, `AP`, `WDS`, `bridge`, `client`, `connector`, `antenes`, `router`, `firewall`, `QoS`, `snmp`, `hack`, `url`, `comentaris` ) VALUES ('8', 'Supertrasto RB532 guifi.net', NULL , '6', '400mW', '802.11a/b/g', 'Si', 'Si', 'Si', 'Si', 'N-Female', '2', 'Si', 'Si', 'Si', 'Si', 'No', 'http://www.routerboard.com', NULL);
ALTER TABLE `guifi_radios` ADD `radiodev_counter` TINYINT( 2 ) NOT NULL DEFAULT '0' AFTER `model_id` ;
ALTER TABLE `guifi_interfaces` ADD `radiodev_counter` TINYINT NULL DEFAULT NULL AFTER `device_id` ;
INSERT INTO `guifi_types` (type, text, description) VALUES ('firmware', 'DD-guifi', 'DD-guifi from Miquel Martos');
INSERT INTO `guifi_types` (type, text, description) VALUES ('firmware', 'RouterOSv2.9', 'RouterOS 2.9 from Mikrotik');
UPDATE `guifi_types` SET `relations` = 'WRT54Gv1-4|WRT54GSv1' WHERE `type` = 'firmware' AND `text` = 'Alchemy';
UPDATE `guifi_types` SET `relations` = 'WRT54Gv1-4|WRT54GL|WRT54GSv1|WRT54GSv2' WHERE `type` = 'firmware' AND `text` = 'Talisman';
UPDATE `guifi_types` SET `relations` = 'WRT54Gv1-4|WRT54GL|WRT54GSv1|WRT54GSv2' WHERE `type` = 'firmware' AND `text` = 'DD-WRT';
UPDATE `guifi_types` SET `relations` = 'WRT54Gv1-4|WRT54GL|WRT54GSv1|WRT54GSv2' WHERE `type` = 'firmware' AND `text` = 'DD-guifi';
UPDATE `guifi_types` SET `relations` = 'Supertrasto RB532 guifi.net' WHERE `type` = 'firmware' AND `text` = 'RouterOSv2.9';
DELETE FROM `guifi_types` WHERE `type` = 'device' AND `text` = 'mobile';

