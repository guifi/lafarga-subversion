-- MTRG service
alter table guifi_zone add column mrtg_servers varchar(255) after ntp_servers;
alter table guifi_devices add column url_mrtg_server varchar(255) after extra;

-- Typo
UPDATE `guifi_types` SET `description` = 'DD-WRT from BrainSlayer',`relations` = NULL WHERE `id` =4 AND `type` = 'firmware' LIMIT 1 ;

-- New Model
INSERT INTO `guifi_model` ( `mid` , `fid` , `model` , `tipus` , `potencia_max` , `modes` , `AP` , `WDS` , `bridge` , `client` , `connector` , `antenes` , `router` , `firewall` , `QoS` , `snmp` , `hack` , `url` , `comentaris` ) 
VALUES (
'15', '2', 'WRT54GL', 'Extern', '251mw', '802.11b/g', 'Si', 'Si', 'Si', 'Si', 'RP-TNC', '2', 'Si', 'Si', 'Si', 'Si', 'Si', 'www.linksys.com', NULL 
);



