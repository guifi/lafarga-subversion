-- 
-- Estructura de la taula `guifi_location`
-- 

CREATE TABLE `guifi_location` (
  `id` int(11) NOT NULL auto_increment,
  `nick` varchar(40) NOT NULL default '',
  `zone_id` int(11) default NULL,
  `zone_description` varchar(255) default NULL,
  `lat` decimal(10,6) default NULL,
  `lon` decimal(10,6) default NULL,
  `elevation` int(11) default NULL,
  `contact` varchar(40) default NULL,
  `status_flag` varchar(40) NOT NULL default 'Planned',
  `stable` enum('Yes','No') NOT NULL default 'Yes',
  `graph_server` varchar(40) default NULL,
  `user_created` int(10) NOT NULL default '0',
  `user_changed` int(10) default NULL,
  `timestamp_created` int(11) NOT NULL default '0',
  `timestamp_changed` int(11) default NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM /*!40100 DEFAULT CHARACTER SET utf8 */;

-- 
-- Estructura de la taula `guifi_networks`
-- 

CREATE TABLE `guifi_networks` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `base` varchar(255) NOT NULL default '',
  `mask` varchar(255) NOT NULL default '255.255.255.0',
  `zone` int(10) unsigned NOT NULL,
  `network_type` enum('backbone','public') NOT NULL default 'public',
  `user_created` int(10) NOT NULL default '0',
  `user_changed` int(10) NOT NULL default '0',
  `timestamp_created` int(11) NOT NULL default '0',
  `timestamp_changed` int(11) NOT NULL default '0',
  `valid` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `networks` (`base`(16),`mask`(16)),
  KEY `net_zone` (`zone`)
) TYPE=MyISAM /*!40100 DEFAULT CHARACTER SET utf8 */;

-- 
-- Estructura de la taula `guifi_zone`
-- 

CREATE TABLE `guifi_zone` (
  `id` int(5) unsigned NOT NULL auto_increment,
  `title` varchar(255) NOT NULL default '',
  `nick` varchar(10) NULL,
  `body` longtext NOT NULL,
  `master` int(5) unsigned NOT NULL,
  `time_zone` varchar(15) NOT NULL,
  `dns_servers` varchar(255) default NULL,
  `ntp_servers` varchar(255) default NULL,
  `mrtg_servers` varchar(255) default NULL,
  `graph_server` varchar(40) default NULL,
  `image` varchar(255) NOT NULL default '',
  `map_coord` mediumtext NOT NULL,
  `map_poly` mediumtext NOT NULL,
  `homepage` varchar(255) default NULL,
  `notification` varchar(255) default NULL,
  `ospf_zone` varchar(255) default NULL,
  `weight` tinyint(4) NOT NULL default '0',
  `valid` tinyint(1) NOT NULL default '0',
  `user_created` int(10) NOT NULL default '0',
  `user_changed` int(10) NOT NULL default '0',
  `timestamp_created` int(11) NOT NULL default '0',
  `timestamp_changed` int(11) NOT NULL default '0',

  PRIMARY KEY  (`id`),
  KEY `name` (`title`(10))
) TYPE=MyISAM /*!40100 DEFAULT CHARACTER SET utf8 */;
        
CREATE TABLE guifi_devices (
  id int(11) NOT NULL auto_increment,
  nid int(11) NOT NULL,
  nick varchar(40) NOT NULL,
  type varchar(40) NOT NULL,
  contact varchar(40) default NULL,
  mac varchar(20) NOT NULL default '00:00:00:00:00:00',
  comment longtext default NULL,
  flag varchar(40) NOT NULL default 'Planned',
  extra longtext DEFAULT NULL,
  url_mrtg_server varchar(255) default NULL,
  graph_server varchar(40) default NULL,
  user_created int(10) NOT NULL default '0',
  user_changed int(10) NOT NULL default '0',
  timestamp_created int(11) NOT NULL default '0',
  timestamp_changed int(11) NOT NULL default '0',
  PRIMARY KEY  (id),
  UNIQUE KEY nick (nick)
) TYPE=MyISAM /*!40100 DEFAULT CHARACTER SET utf8 */;

--
-- table guifi_links
-- contains many de-normalized data, but helpful for:
--  .improving performance
--  .simplify queries 
--  .track consistency 
--

CREATE TABLE guifi_links (
  id int(11) NOT NULL ,       
  nid int(11) NOT NULL ,       
  device_id int(11) NOT NULL ,       
  interface_id int(11) NOT NULL ,
  ipv4_id int(11) NOT NULL ,
  link_type varchar(40) NOT NULL ,
  routing varchar(40),
  flag varchar(40) NOT NULL default 'Planned',
  PRIMARY KEY (device_id,id), 
  KEY (id)
) TYPE=MyISAM /*!40100 DEFAULT CHARACTER SET utf8 */;

CREATE TABLE guifi_interfaces (
 id int(11) NOT NULL auto_increment,       
 device_id int(11) NOT NULL,       
 interface_type varchar(40) NOT NULL,
 mac varchar(20) NOT NULL default '00:00:00:00:00:00',
 PRIMARY KEY (`device_id`,`id`)
) TYPE=MyISAM /*!40100 DEFAULT CHARACTER SET utf8 */;

CREATE TABLE guifi_radios (
  id int(11) NOT NULL auto_increment,
  radiodev_counter tinyint(2) NOT NULL default '0',
  nid int(11) NOT NULL,
  model_id int(10) NOT NULL,
  ssid varchar(20) NOT NULL default '',
  mode varchar(40) NOT NULL,
  protocol varchar(40) NOT NULL default '802.11bg',
  channel int(3) default NULL,
  antenna_angle int(4) default 0,
  antenna_gain int(4) default NULL,
  antenna_azimuth int(4) default '360',
  clients_accepted enum('Yes','No') NOT NULL default 'Yes',
  PRIMARY KEY  (id,radiodev_counter),
  KEY  (nid)
) TYPE=MyISAM /*!40100 DEFAULT CHARACTER SET utf8 */;


CREATE TABLE guifi_types (
  id int(11) NOT NULL auto_increment,
  type varchar(15) NOT NULL,
  text varchar(15) NOT NULL,
  description longtext NOT NULL,
  relations longtext NULL,
  PRIMARY KEY  (type,id),
  KEY text (text)
) TYPE=MyISAM /*!40100 DEFAULT CHARACTER SET utf8 */;

-- 
-- Estructura de la taula `guifi_services`
-- 

CREATE TABLE `guifi_services` (
  `id` int(11) NOT NULL auto_increment,
  `nick` varchar(40) NOT NULL default '',
  `service_type` varchar(40) NOT NULL default '',
  `zone_id` int(11) default NULL,
  `device_id` int(11) default NULL,
  `contact` varchar(40) default NULL,
  `status_flag` varchar(40) NOT NULL default 'Planned',
  `extra` longtext NULL default NULL,
  `user_created` int(10) NOT NULL default '0',
  `user_changed` int(10) default NULL,
  `timestamp_created` int(11) NOT NULL default '0',
  `timestamp_changed` int(11) default NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM /*!40100 DEFAULT CHARACTER SET utf8 */;

-- 
-- Estructura de la taula `guifi_users`
-- 

CREATE TABLE `guifi_users` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `nid` int(11) unsigned NOT NULL default '0',
  `services` longtext NOT NULL,
  `firstname` varchar(60) NOT NULL default '',
  `lastname` varchar(60) NOT NULL default '',
  `username` varchar(40) NOT NULL default '',
  `password` varchar(128) NOT NULL default '',
  `email` varchar(64) default NULL,
  `extra` longtext,
  `user_created` int(10) NOT NULL default '0',
  `user_changed` int(10) NOT NULL default '0',
  `timestamp_created` int(10) NOT NULL default '0',
  `timestamp_changed` int(10) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `username` (`username`)
) TYPE=MyISAM /*!40100 DEFAULT CHARACTER SET utf8 */;

-- 
-- interface types
-- relation describes MAC (related to base MAC)
-- 

INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('interface', 'Lan',     'Device base address (Lan)',      '0');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('interface', 'wLan/Lan','Device lan & wlan (bridged)',    '2');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('interface', 'wLan',    'wireless lan',                   '2');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('interface', 'Wan',     'Wan',                            '1');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('interface', 'wds/p2p', 'P2P Wds',                        '2');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('interface', 'vlan',    'Virtual network over Lan',       '0');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('interface', 'vwan',    'Virtual network over Wan',       '1');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('interface', 'vwlan',   'Virtual network over wLan',      '2');
-- INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('interface', 'vlan1',   'vlan #1 (plugged into port #1)', '3');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('interface', 'vlan2',   'vlan #2 (plugged into port #2)', '4');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('interface', 'vlan3',   'vlan #3 (plugged into port #3)', '5');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('interface', 'vlan4',   'vlan #4 (plugged into port #4)', '6');

--
-- radio mode types
--

INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('mode', 'ap',          'AP or AP with WDS', 'ap|client');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('mode', 'client',      'Wireless client',   'ap');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('mode', 'bridge',      'Wireless Bridge',   'bridge');
INSERT INTO `guifi_types` (type, text, description) VALUES ('mode', 'routedclient','Routed client');

--
-- link types
--

INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('link', 'cable',    'Cable',             'vlan|vwan|vwlan|vlan1|vlan2|vlan3|vlan4|Lan|wLan/Lan|Wan');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('link', 'tunnel',   'Tunnel',            'tunnel');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('link', 'bridge',   'Wireless Bridge',   'wLan/Lan|Lan');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('link', 'wds',      'Wireless WDS',      'wds/p2p');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('link', 'ap/client','Wireless AP/Client','Wan/wLan|wLan/Lan');

-- 
-- device types
-- 

INSERT INTO `guifi_types` (type, text, description) VALUES ('device', 'radio',  'Wireless device, like a router, bridge, AP...');
INSERT INTO `guifi_types` (type, text, description) VALUES ('device', 'phone',  'Voip handset, telephone');
-- INSERT INTO `guifi_types` (type, text, description) VALUES ('device', 'mobile', 'Mobile device. like a laptop or pda');
INSERT INTO `guifi_types` (type, text, description) VALUES ('device', 'server', 'Server computer');
INSERT INTO `guifi_types` (type, text, description) VALUES ('device', 'nat',    'Firewall, private Network behind a NAT');
INSERT INTO `guifi_types` (type, text, description) VALUES ('device', 'ADSL',   'ADSL router or device providing internet access');
INSERT INTO `guifi_types` (type, text, description) VALUES ('device', 'cam',    'Network camera. Live view.');
        
-- 
-- service types
-- 

INSERT INTO `guifi_types` (type, text, description) VALUES ('service', 'AP',       'Wireless connectivity for end users');
INSERT INTO `guifi_types` (type, text, description) VALUES ('service', 'ADSL',     'Open ADSL-type internet access');
INSERT INTO `guifi_types` (type, text, description) VALUES ('service', 'Proxy',    'Internet access trough a proxy');
INSERT INTO `guifi_types` (type, text, description) VALUES ('service', 'DNS',      'Domain Name Server service');
INSERT INTO `guifi_types` (type, text, description) VALUES ('service', 'NTP',      'Network Time Protocol service');
INSERT INTO `guifi_types` (type, text, description) VALUES ('service', 'mail',     'Mail server');
INSERT INTO `guifi_types` (type, text, description) VALUES ('service', 'web',      'Web server');
INSERT INTO `guifi_types` (type, text, description) VALUES ('service', 'ftp',      'FTP or shared disk server');
INSERT INTO `guifi_types` (type, text, description) VALUES ('service', 'p2p',      'Peer 2 Peer server');
INSERT INTO `guifi_types` (type, text, description) VALUES ('service', 'asterisk', 'Asterisk VoIP PBX server');
INSERT INTO `guifi_types` (type, text, description) VALUES ('service', 'radio',    'Radio broadcast');
INSERT INTO `guifi_types` (type, text, description) VALUES ('service', 'tv',       'TV broadcast');
INSERT INTO `guifi_types` (type, text, description) VALUES ('service', 'irc',      'IRC (chat) server');
INSERT INTO `guifi_types` (type, text, description) VALUES ('service', 'IM',       'Instant Messaging, jabber server');
INSERT INTO `guifi_types` (type, text, description) VALUES ('service', 'cam',      'Network camera with live view.');
INSERT INTO `guifi_types` (type, text, description) VALUES ('service', 'svn',      'Subversion/CVS repository.');
INSERT INTO `guifi_types` (type, text, description) VALUES ('service', 'meteo',    'Weather station.');
INSERT INTO `guifi_types` (type, text, description) VALUES ('service', 'apt-cache','Linux distribution cache.');
INSERT INTO `guifi_types` (type, text, description) VALUES ('service', 'wol',      'Wake-on-lan');
INSERT INTO `guifi_types` (type, text, description) VALUES ('service', 'iperf',    'iperf bandwidth test');
INSERT INTO `guifi_types` (type, text, description) VALUES ('service', 'teamspeak','TeamSpeak Server - Voice conference');
INSERT INTO `guifi_types` (type, text, description) VALUES ('service', 'games',    'Generic games server');
INSERT INTO `guifi_types` (type, text, description) VALUES ('service', 'SNPgraphs','SNP graph server');

--
-- radio mode types
--

INSERT INTO `guifi_types` (type, text, description) VALUES ('mode', 'NAT Client', 'NAT Client');
-- INSERT INTO `guifi_types` (type, text, description) VALUES ('mode', 'Routed Client', 'Routed Client');
-- INSERT INTO `guifi_types` (type, text, description) VALUES ('mode', 'Bridged  Client', 'Bridged  Client');


--
-- status flag types
--

INSERT INTO `guifi_types` (type, text, description) VALUES ('status', 'Planned',  'Planned');
INSERT INTO `guifi_types` (type, text, description) VALUES ('status', 'Reserved', 'Reserved');
INSERT INTO `guifi_types` (type, text, description) VALUES ('status', 'Building', 'Building');
INSERT INTO `guifi_types` (type, text, description) VALUES ('status', 'Testing',  'Testing');
INSERT INTO `guifi_types` (type, text, description) VALUES ('status', 'Working',  'Online');
INSERT INTO `guifi_types` (type, text, description) VALUES ('status', 'Dropped',  'Dropped');

--
-- protocol types
--

INSERT INTO `guifi_types` (type, text, description) VALUES ('protocol', '802.11a',    '802.11a (1-54Mbps - 5Ghz)');
INSERT INTO `guifi_types` (type, text, description) VALUES ('protocol', '802.11b',    '802.11b (1-11Mbps - 2.4Ghz)');
INSERT INTO `guifi_types` (type, text, description) VALUES ('protocol', '802.11g',    '802.11g (2-54Mbps - 2.4Ghz)');
INSERT INTO `guifi_types` (type, text, description) VALUES ('protocol', '802.11bg',   '802.11g+ (1-54Mbps - 2.4Ghz)');
INSERT INTO `guifi_types` (type, text, description) VALUES ('protocol', '802.11g+',   '802.11g+ (2-125Mbps - 2.4Ghz)');
INSERT INTO `guifi_types` (type, text, description) VALUES ('protocol', '802.11abg',  '802.11abg (1-54Mbps - 2.4/5Ghz)');
INSERT INTO `guifi_types` (type, text, description) VALUES ('protocol', '802.11n',    '802.11n - MIMO (1-125Mbps - 2.4/5Ghz)');
INSERT INTO `guifi_types` (type, text, description) VALUES ('protocol', 'WiMAX',      '802.16a - WiMAX (1-125Mbps - 2-8Ghz)');
INSERT INTO `guifi_types` (type, text, description) VALUES ('protocol', 'legacy',     'legacy/proprietary protocol');

--
-- firmware types
--

INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('firmware', 'n/a',      'not available',NULL);
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('firmware', 'Alchemy',  'Alchemy from sveasoft','WRT54Gv1-4|WRT54GSv1');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('firmware', 'Talisman', 'Talisman from sveasoft','WRT54Gv1-4|WRT54GL|WRT54GSv1|WRT54GSv2');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('firmware', 'DD-WRT',   'DD-WRT from BrainSlayer','WRT54Gv1-4|WRT54GL|WRT54GSv1|WRT54GSv2');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('firmware', 'DD-guifi', 'DD-guifi from Miquel Martos','WRT54Gv1-4|WRT54GL|WRT54GSv1|WRT54GSv2|WHR-HP-G54, WHR-G54S');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('firmware', 'RouterOSv2.9', 'RouterOS 2.9 from Mikrotik','Supertrasto RB532 guifi.net|Supertrasto RB133C guifi.net|Supertrasto RB133 guifi.net|Supertrasto RB112 guifi.net|Supertrasto RB153 guifi.net|Supertrasto guifiBUS guifi.net');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('firmware', 'whiterussian',  'OpenWRT-whiterussian','WRT54Gv1-4|WRT54GL|WRT54GSv1|WRT54GSv2|Wrap|Supertrasto RB532 guifi.net|Supertrasto RB133C guifi.net|Supertrasto RB133 guifi.net|Supertrasto RB112 guifi.net|Supertrasto RB153 guifi.net|Supertrasto guifiBUS guifi.net');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('firmware', 'kamikaze',  'OpenWRT kamikaze','WRT54Gv1-4|WRT54GL|WRT54GSv1|WRT54GSv2|Wrap|Supertrasto RB532 guifi.net|Supertrasto RB133C guifi.net|Supertrasto RB133 guifi.net|Supertrasto RB112 guifi.net|Supertrasto RB153 guifi.net|Supertrasto guifiBUS guifi.net');

--
-- antenna types
--

INSERT INTO `guifi_types` (type, text, description) VALUES ('antenna', '0',   'original/integrated');
INSERT INTO `guifi_types` (type, text, description) VALUES ('antenna', '360', 'omnidirectional');
INSERT INTO `guifi_types` (type, text, description) VALUES ('antenna', '6',   'yagi/directive');
INSERT INTO `guifi_types` (type, text, description) VALUES ('antenna', '90',  'sector 90 degrees');
INSERT INTO `guifi_types` (type, text, description) VALUES ('antenna', '120', 'sector 120 degrees');
INSERT INTO `guifi_types` (type, text, description) VALUES ('antenna', '90',  'patch 90 degrees');
INSERT INTO `guifi_types` (type, text, description) VALUES ('antenna', '60',  'patch 60 degrees');
INSERT INTO `guifi_types` (type, text, description) VALUES ('antenna', '30',  'patch 30 degrees');

--
-- orientation (bearing) types
--

-- INSERT INTO `guifi_types` (type, text, description) VALUES ('bearing', 'N',   'North');
-- INSERT INTO `guifi_types` (type, text, description) VALUES ('bearing', 'NE',  'North east');
-- INSERT INTO `guifi_types` (type, text, description) VALUES ('bearing', 'E',   'East');
-- INSERT INTO `guifi_types` (type, text, description) VALUES ('bearing', 'SE',  'South east');
-- INSERT INTO `guifi_types` (type, text, description) VALUES ('bearing', 'S',   'South');
-- INSERT INTO `guifi_types` (type, text, description) VALUES ('bearing', 'SW',  'South west');
-- INSERT INTO `guifi_types` (type, text, description) VALUES ('bearing', 'W',   'West');
-- INSERT INTO `guifi_types` (type, text, description) VALUES ('bearing', 'NW',  'North east');
-- INSERT INTO `guifi_types` (type, text, description) VALUES ('bearing', 'all', '360 degrees');

--
-- Time zones
--

INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"-12 1 0",'(GMT-12:00) Kwajalein');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"-11 1 0",'(GMT-11:00) Midway Island, Samoa');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"-10 1 0",'(GMT-10:00) Hawaii');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"-09 1 1",'(GMT-09:00) Alaska');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"-08 1 1",'(GMT-08:00) Pacific Time (USA &amp; Canada)');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"-07 1 0",'(GMT-07:00) Arizona');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"-07 2 1",'(GMT-07:00) Mountain Time (USA &amp; Canada)');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"-06 1 0",'(GMT-06:00) Mexico');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"-06 2 1",'(GMT-06:00) Central Time (USA &amp; Canada)');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"-05 1 0",'(GMT-05:00) Indiana East, Colombia, Panama');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"-05 2 1",'(GMT-05:00) Eastern Time (USA &amp; Canada)');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"-04 1 0",'(GMT-04:00) Bolivia, Venezuela');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"-04 2 1",'(GMT-04:00) Atlantic Time (Canada), Brazil West');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"-03.5 1 1",'(GMT-03:30) Newfoundland');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"-03 1 0",'(GMT-03:00) Guyana');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"-03 2 1",'(GMT-03:00) Brazil East, Greenland');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"-02 1 0",'(GMT-02:00) Mid-Atlantic');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"-01 1 2",'(GMT-01:00) Azores');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"+00 1 0",'(GMT) Gambia, Liberia, Morocco');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"+00 2 2",'(GMT) England');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"+01 1 0",'(GMT+01:00) Tunisia');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"+01 2 2",'(GMT+01:00) Gurb, France, Germany, Italy');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"+02 1 0",'(GMT+02:00) South Africa');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"+02 2 2",'(GMT+02:00) Greece, Ukraine, Romania, Turkey');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"+03 1 0",'(GMT+03:00) Iraq, Jordan, Kuwait');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"+04 1 0",'(GMT+04:00) Armenia');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"+05 1 0",'(GMT+05:00) Pakistan, Russia');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"+06 1 0",'(GMT+06:00) Bangladesh, Russia');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"+07 1 0",'(GMT+07:00) Thailand, Russia');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"+08 1 0",'(GMT+08:00) China, Hong Kong, Australia Western');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"+08 2 0",'(GMT+08:00) Singapore, Taiwan, Russia');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"+09 1 0",'(GMT+09:00) Japan, Korea');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"+09.5 1 4",'(GMT+09:30) Australia Central');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"+10 1 0",'(GMT+10:00) Guam, Russia');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"+10 2 4",'(GMT+10:00) Australia Eastern');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"+11 1 0",'(GMT+11:00) Solomon Islands');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"+12 1 0",'(GMT+12:00) Fiji');
INSERT INTO `guifi_types` (type, text, description) VALUES ('tz',"+12 2 4",'(GMT+12:00) New Zealand');

--
-- Time zones
--

INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"0",'Auto 2.4GHz','802.11b|802.11g|802.11bg|802.11abg|802.11g+|802.11n');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"1",'1.- 2412 MHz','802.11b|802.11g|802.11bg|802.11abg|802.11g+|802.11n');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"2",'2-. 2417 MHz','802.11b|802.11g|802.11bg|802.11abg|802.11g+|802.11n');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"3",'3.- 2422 MHz','802.11b|802.11g|802.11bg|802.11abg|802.11g+|802.11n');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"4",'4.- 2422 MHz','802.11b|802.11g|802.11bg|802.11abg|802.11g+|802.11n');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"5",'5.- 2432 MHz','802.11b|802.11g|802.11bg|802.11abg|802.11g+|802.11n');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"6",'6.- 2437 MHz','802.11b|802.11g|802.11bg|802.11abg|802.11g+|802.11n');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"7",'7.- 2442 MHz','802.11b|802.11g|802.11bg|802.11abg|802.11g+|802.11n');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"8",'8.- 2447 MHz','802.11b|802.11g|802.11bg|802.11abg|802.11g+|802.11n');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"9",'9.- 2452 MHz','802.11b|802.11g|802.11bg|802.11abg|802.11g+|802.11n');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"10",'10.- 2457 MHz','802.11b|802.11g|802.11bg|802.11abg|802.11g+|802.11n');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"11",'11.- 2462 MHz','802.11b|802.11g|802.11bg|802.11abg|802.11g+|802.11n');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"12",'12.- 2467 MHz','802.11b|802.11g|802.11bg|802.11abg|802.11g+|802.11n');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"13",'13.- 2472 MHz','802.11b|802.11g|802.11bg|802.11abg|802.11g+|802.11n');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"14",'14.- 2477 MHz','802.11b|802.11g|802.11bg|802.11abg|802.11g+|802.11n');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"5000",'Auto 5GHz','802.11a|802.11abg');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"5180",'1.- 5180 MHz','802.11a|802.11abg');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"5200",'2.- 5200 MHz','802.11a|802.11abg');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"5220",'3.- 5220 MHz','802.11a|802.11abg');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"5240",'4.- 5240 MHz','802.11a|802.11abg');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"5260",'5.- 5260 MHz','802.11a|802.11abg');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"5280",'6.- 5280 MHz','802.11a|802.11abg');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"5300",'7.- 5300 MHz','802.11a|802.11abg');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"5320",'8.- 5320 MHz','802.11a|802.11abg');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"5500",'9.- 5500 MHz','802.11a|802.11abg');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"5520",'10.- 5520 MHz','802.11a|802.11abg');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"5540",'11.- 5540 MHz','802.11a|802.11abg');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"5560",'12.- 5560 MHz','802.11a|802.11abg');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"5580",'13.- 5580 MHz','802.11a|802.11abg');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"5600",'14.- 5600 MHz','802.11a|802.11abg');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"5620",'15.- 5620 MHz','802.11a|802.11abg');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"5640",'16.- 5640 MHz','802.11a|802.11abg');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"5660",'17.- 5660 MHz','802.11a|802.11abg');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"5680",'18.- 5680 MHz','802.11a|802.11abg');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('channel',"5700",'19.- 5700 MHz','802.11a|802.11abg');

--
-- Routing methods
-- Relations contains supported firmwares
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('routing',"n/a",'None','');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('routing',"Static",'Static routing','');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('routing',"Gateway",'Gateway to AP','');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('routing',"OSPF",'OSPF','Alchemy|Talisman|DD-WRT|DD-guifi|RouterOSv2.9');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('routing',"BGP",'BGP','Alchemy|Talisman|DD-WRT|DD-guifi|RouterOSv2.9');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('routing',"OLSR",'OLSR','');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('routing',"OLSR-NG",'OLSR-NG','');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('routing',"BATMAN",'BATMAN','');
INSERT INTO `guifi_types` (type, text, description, relations) VALUES ('routing',"RIP",'RIP','');

--
-- radio manufacturers
--

CREATE TABLE `guifi_manufacturer` (
  `fid` int(11) NOT NULL auto_increment,
  `nom` varchar(40) NOT NULL default '',
  `url` varchar(40) default NULL,
  PRIMARY KEY  (`fid`)
) TYPE=MyISAM /*!40100 DEFAULT CHARACTER SET utf8 */;

-- 
-- Volcant dades de la taula `guifi_manufacturer`
-- 

INSERT INTO `guifi_manufacturer` VALUES (0, 'Other', null);
INSERT INTO `guifi_manufacturer` VALUES (0, 'D-Link', 'http://www.dlink.com');
INSERT INTO `guifi_manufacturer` VALUES (0, 'Linksys', 'http://www.linksys.com');
INSERT INTO `guifi_manufacturer` VALUES (0, 'Conceptonic', 'http://www.conceptronic.net/');
INSERT INTO `guifi_manufacturer` VALUES (0, 'US Robotics', 'http://www.usr.com');
INSERT INTO `guifi_manufacturer` VALUES (0, '3Com', 'http://www.3com.com');
INSERT INTO `guifi_manufacturer` VALUES (0, 'Zyxel', 'http://www.zyxel.com');
INSERT INTO `guifi_manufacturer` VALUES (0, 'Conceptronic', NULL);
INSERT INTO `guifi_manufacturer` VALUES (0, 'Mikrotik', 'http://mikrotik.com');
INSERT INTO `guifi_manufacturer` VALUES (0, 'Buffalo', 'http://www.buffalotech.com');
-- phpMyAdmin SQL Dump
-- version 2.7.0-pl2
-- http://www.phpmyadmin.net
-- 
-- Servidor: localhost
-- Temps de generació: 30-03-2006 a les 22:42:59
-- Versió del servidor: 4.1.11
-- PHP versió: 5.0.4
-- 
-- Base de dades: `proves`
-- 

-- --------------------------------------------------------

-- 
-- Estructura de la taula `guifi_model`
-- 

CREATE TABLE `guifi_model` (
  `mid` int(11) NOT NULL auto_increment,
  `fid` int(11) NOT NULL default '0',
  `model` varchar(40) NOT NULL default '',
  `tipus` enum('Extern','PCI','PCMCIA') default NULL,
  `radiodev_max` tinyint(2) NOT NULL default '1',
  `potencia_max` int(11) default NULL,
  `modes` enum('802.11b/g','802.11b','802.11a','802.11a/b/g','WiMax','802.11n') NOT NULL default '802.11b/g',
  `AP` enum('Si','No') default NULL,
  `virtualAP` enum('Yes','No') NOT NULL default 'No',
  `WDS` enum('Si','No','Hack') default NULL,
  `bridge` enum('Si','No','Hack') default NULL,
  `client` enum('Si','No','Hack') default NULL,
  `connector` varchar(20) default NULL,
  `antenes` enum('2','1','0') default '2',
  `router` enum('Si','No') default NULL,
  `firewall` enum('Si','No') default NULL,
  `QoS` enum('Si','No','Hack') default NULL,
  `snmp` enum('Si','No','Hack') default NULL,
  `hack` enum('Si','No') default NULL,
  `interfaces` varchar(240) default NULL,
  `url` varchar(240) default NULL,
  `comentaris` varchar(240) default NULL,
  `supported` enum('Yes','No','Deprecated') NOT NULL default 'Yes',
  PRIMARY KEY  (`mid`)
)  TYPE=MyISAM /*!40100 DEFAULT CHARACTER SET utf8 */;

-- 
-- guifi_model
-- 

INSERT INTO `guifi_model` VALUES (0, 0, 'Other', NULL, 1, NULL, '802.11a/b/g', 'Si', 'No', 'Si', 'Si', 'Si', NULL, NULL, 'Si', 'Si', NULL, NULL, NULL, 'wLan/Lan', NULL, 'To be used for unknown or not listed devices', 'Yes'); 
INSERT INTO `guifi_model` VALUES (0, 2, 'WRT54Gv1-4', 'Extern', 1, 251, '802.11b/g', 'Si', 'No', 'Hack', 'Hack', 'Hack', 'RP-TNC', '2', 'Si', 'Si', 'Hack', 'Hack', 'Si', 'wLan/Lan|vlan|vlan2|vlan3|vlan4|Wan', 'http://www.linksys.com/products/product.', 'El canvi de potència, mode client i WDS, via hack.\r\nHacks disponibles: sveasoft (Satori, Alchemy...), OpenWRT...','Yes');
INSERT INTO `guifi_model` VALUES (0, 4, 'USR5450', 'Extern', 1, 100, '802.11b/g', 'Si', 'No', 'Si', 'Si', 'Si', 'RP-SMA', '2', 'No', 'No', 'No', 'Si', 'Si', 'wLan/Lan', 'http://www.usr-emea.com/products/p-networking-product.asp?prod=net-5450&page=overview&loc=span', 'suporta snmp, 108...','Deprectated');
INSERT INTO `guifi_model` VALUES (0, 4, 'USR8054', 'Extern', 1, 100, '802.11b/g', 'Si', 'No', 'No', 'No', 'No', 'RP-SMA', '2', 'Si', 'Si', 'No', 'Si', 'No', 'wLan/Lan', 'http://www.usr-emea.com/products/p-networking-product.asp?prod=net-8054&page=overview&loc=span', 'suporta snmp, 108...','Deprecated');
INSERT INTO `guifi_model` VALUES (0, 1, 'DWL-2000AP+', 'Extern', 1, 0, '802.11b/g', 'Si', 'No', 'No', 'Si', 'Si', 'RP-SMA', '1', 'No', 'No', 'No', 'No', 'No', 'wLan/Lan', 'http://www.dlink.es/?go=n3UbJWiD+YSHWPFL1WecNQcr8NQe9UH9RtZOHa06dX6U9AJhlDtnw5L4mVO+s93xky+DSk2hueOIj+pl9A7TpjAkaMQ=', '','Deprecated');
INSERT INTO `guifi_model` VALUES (0, 1, 'DWL-2100AP', 'Extern', 1, 250, '802.11b/g', 'Si', 'No', 'Si', 'No', 'Si', 'RP-SMA', '2', 'No', 'No', 'No', 'Si', 'Si', 'wLan/Lan', 'http://www.dlink.es/?go=n3UbJWiD+YSHWPFL1WecNQcr8NQe9UH9RtZOHa06dX6U8QRhlDtnw5L4mVO+tdzxky+DSk2hueOIj+pv/g3QoTgkacA=', 'Hack de potència via comanda telnet  -overridetxpower; 2 antennas, 1 is inside.','Deprecated');
INSERT INTO `guifi_model` VALUES (0, 6, 'Prestige 650W', 'Extern', 1, 0, '802.11b', 'Si', 'No', 'No', 'Si', 'Si', 'RP-SMA', '1', 'Si', 'Si', 'No', '', 'No', 'wLan/Lan', 'http://www.zyxel.com/product/guifi_model.php?indexcate=1023416340&indexcate1=1021877946&indexFlagvalue=1021873638', 'Routser ADSL, útil per prescindir del kit USB i a més donar-se cobertura dins de casa. El venen a telefònica.','Deprecated');
INSERT INTO `guifi_model` VALUES (0, 7, 'C54APT', 'Extern', 1, 250, '802.11b/g', 'Si', 'No', 'Si', 'No', 'Si', 'RP-SMA', '2', 'No', 'No', 'No', 'No', 'Si', 'wLan/Lan', 'http://www.conceptronic.net/product3.asp?g=9&p=C54APT', '"hack" via telnet - overridetxpower. els guifi_model antics es podien actualitzar al firmware del DWL-2100AP','Deprecated');
INSERT INTO `guifi_model` VALUES (0, 4, 'USR5410', 'PCMCIA', 1, 80, '802.11b/g', 'No', 'No', 'No', 'No', 'Si', '', '', 'No', 'No', 'No', 'No', 'No', 'wLan/Lan', 'http://www.usr-emea.com/products/p-wireless-product.asp?prod=net-5410&loc=span', 'suporta 108','Deprecated');
INSERT INTO `guifi_model` VALUES (0, 7, 'C54c', 'PCMCIA', 1, 0, '802.11b/g', 'No', 'No', 'No', 'No', 'Si', '', '1', 'No', 'No', 'No', 'No', 'No', 'wLan/Lan', 'http://www.conceptronic.net', '','Deprecated');
INSERT INTO `guifi_model` VALUES (0, 7, 'C54i', 'PCI', 1, 0, '802.11b/g', 'Si', 'No', 'No', 'No', 'Si', 'RP-SMA', '1', 'No', 'No', 'No', 'No', 'No', 'wLan/Lan', 'http://www.conceptronic.net', '','Deprecated');
INSERT INTO `guifi_model` VALUES (0, 2, 'WAP11', 'Extern', 1, 80, '802.11b', 'Si', 'No', 'No', 'Si', 'Si', 'RP-TNC', '2', 'No', 'No', 'No', 'No', 'No', 'wLan/Lan', 'http://www.linksys.com/products/product.asp?grid=33&scid=35&prid=563', 'L''històric!','Deprecated');
INSERT INTO `guifi_model` VALUES (0, 6, 'Prestige 650HW-31E', 'Extern', 1, 0, '802.11b', 'Si', 'No', 'No', 'Si', 'Si', 'RP-SMA', '1', 'Si', 'Si', 'No', 'No', 'No', 'wLan/Lan', 'http://www.zyxel.co.uk/Products.32+B6JnR4X1p5WEVMcHJvZHVjdHNfcGkxW3Nob3dVaWRdPTQyJmNIYXNoPTc4NWYzZTA5MTc_.0.html', 'Router ADSL, útil per prescindir del kit USB i a més donar-se cobertura dins de casa. El venen a telefònica.','Deprecated');
INSERT INTO `guifi_model` VALUES (0, 2, 'WAP54G', 'Extern', 1, 28, '802.11b/g', 'Si', 'No', 'No', 'No', 'Si', 'RP-TNC', '2', 'No', 'No', 'No', 'No', 'No', 'wLan/Lan', '', '','Deprecated');
INSERT INTO `guifi_model` VALUES (0, 9, 'WHR-HP-G54, WHR-G54S', 'Extern', 1, 251, '802.11b/g', 'No', 'Si', 'Hack', 'Hack', 'Hack', 'RP-SMA', '1', 'Si', 'Si', 'Hack', 'Hack', 'Si', 'wLan/Lan|vlan|vlan2|vlan3|vlan4|Wan', 'http://www.buffalo-technology.com/products/product-detail.php?productid=124&categoryid=28', 'El canvi de potència, mode client i WDS, via hack.','Deprecated');
INSERT INTO `guifi_model` VALUES (0, 2, 'WRT54GL', 'Extern', 1, 251, '802.11b/g', 'No', 'Si', 'Hack', 'Hack', 'Hack', 'RP-TNC', '2', 'Si', 'Si', 'Hack', 'Hack', 'Si', 'wLan/Lan|vlan|vlan2|vlan3|vlan4|Wan', 'http://www.linksys.com/products/product.', 'El canvi de potència, mode client i WDS, via hack.\r\nHacks disponibles: sveasoft (Satori, Alchemy...), OpenWRT, DD-WRT, ...','Yes');
INSERT INTO `guifi_model` VALUES (0, 2, 'WRT54GSv1', 'Extern', 1, 251, '802.11b/g', 'No', 'Si', 'Hack', 'Hack', 'Hack', 'RP-TNC', '2', 'Si', 'Si', 'Hack', 'Hack', 'Si', 'wLan/Lan|vlan2|vlan3|vlan4|Wan', 'http://www.linksys.com/products/product.', 'El canvi de potència, mode client i WDS, via hack.\r\nHacks disponibles: sveasoft (Satori, Alchemy...), OpenWRT, DD-WRT, ...','Yes');
INSERT INTO `guifi_model` VALUES (0, 2, 'WRT54GSv2', 'Extern', 1, 251, '802.11b/g', 'No', 'Si', 'Hack', 'Hack', 'Hack', 'RP-TNC', '2', 'Si', 'Si', 'Hack', 'Hack', 'Si', 'wLan/Lan|vlan|vlan2|vlan3|vlan4|Wan', 'http://www.linksys.com/products/product.', 'El canvi de potència mode client i WDS, via hack.\r\nHacks disponibles: sveasoft (Satori, Alchemy...), OpenWRT, DD-WRT, ...','Yes');
INSERT INTO `guifi_model` VALUES (0, 8, 'Supertrasto RB532 guifi.net', NULL, 6, 400, '802.11a/b/g', 'Si', 'Yes', 'Si', 'Si', 'Si', 'N-Female', '2', 'Si', 'Si', 'Si', 'Si', 'No', 'wLan/Lan|ether2|ether3|ether4|ether5|ether6|ether7|ether8|ether9', 'http://www.routerboard.com', NULL,'Yes');
INSERT INTO `guifi_model` VALUES (0, 8, 'Supertrasto RB133C guifi.net', NULL, 1, 400, '802.11a/b/g', 'Si', 'Yes', 'Si', 'Si', 'Si', 'N-Female', '2', 'Si', 'Si', 'Si', 'Si', 'No', 'wLan/Lan', 'http://www.routerboard.com', NULL,'Yes');
INSERT INTO `guifi_model` VALUES (0, 8, 'Supertrasto RB133 guifi.net', NULL, 3, 400, '802.11a/b/g', 'Si', 'Yes', 'Si', 'Si', 'Si', 'N-Female', '2', 'Si', 'Si', 'Si', 'Si', 'No', 'wLan/Lan|ether2|ether3', 'http://www.routerboard.com', NULL,'Yes');
INSERT INTO `guifi_model` VALUES (0, 8, 'Supertrasto RB112 guifi.net', NULL, 2, 400, '802.11a/b/g', 'Si', 'Yes', 'Si', 'Si', 'Si', 'N-Female', '2', 'Si', 'Si', 'Si', 'Si', 'No', 'wLan/Lan', 'http://www.routerboard.com', NULL,'Yes');
INSERT INTO `guifi_model` VALUES (0, 8, 'Supertrasto RB153 guifi.net', NULL, 3, 400, '802.11a/b/g', 'Si', 'Yes', 'Si', 'Si', 'Si', 'N-Female', '2', 'Si', 'Si', 'Si', 'Si', 'No', 'wLan/Lan|ether2|ether3|ether4|ether5', 'http://www.routerboard.com', NULL,'Yes');
INSERT INTO `guifi_model` VALUES (0, 8, 'Supertrasto guifiBUS guifi.net', NULL, 24, 400, '802.11a/b/g', 'Si', 'Yes', 'Si', 'Si', 'Si', 'N-Female', '2', 'Si', 'Si', 'Si', 'Si', 'No', 'wLan/Lan|ether2|ether3|ether4|ether5', 'http://www.routerboard.com', NULL,'Yes');



CREATE TABLE `guifi_ipv4` (
  `id` int(11) NOT NULL,
  `interface_id` int(11) NOT NULL default '0',
  `ipv4` varchar(16) default NULL,
  `netmask` varchar(16) NOT NULL default '255.255.255.0',
  PRIMARY KEY  (`interface_id`,`id`),
  UNIQUE KEY `ipv4` (`ipv4`)
) TYPE=MyISAM /*!40100 DEFAULT CHARACTER SET utf8 */;
