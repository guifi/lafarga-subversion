ALTER TABLE `guifi_zone` 
ADD `minx` DECIMAL( 10, 6 ) NULL AFTER `ospf_zone` ,
ADD `miny` DECIMAL( 10, 6 ) NULL AFTER `minx` ,
ADD `maxx` DECIMAL( 10, 6 ) NULL AFTER `miny` ,
ADD `maxy` DECIMAL( 10, 6 ) NULL AFTER `maxx` ;
ALTER TABLE `guifi_zone` 
ADD `local` ENUM( 'Yes', 'No' ) NOT NULL DEFAULT 'Yes' AFTER `maxx` ,
ADD `nodexchange_url` VARCHAR( 255 ) NULL AFTER `local` ,
ADD `refresh` INT NULL AFTER `nodexchange_url` ,
ADD `remote_server_id` VARCHAR( 255 ) NULL AFTER `refresh` ;
