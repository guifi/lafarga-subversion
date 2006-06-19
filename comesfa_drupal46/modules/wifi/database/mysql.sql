# phpMyAdmin MySQL-Dump
# version 2.5.0
# http://www.phpmyadmin.net/ (download page)
#
# Servidor: localhost
# Temps de generació: 21-04-2005 a les 11:49:36
# Versió del servidor: 3.23.58
# PHP versió: 4.2.2
# Base de dades : `comesfa`
# --------------------------------------------------------

#
# Estructura de la taula `wifi_link`
#
# Creació: 21-04-2005 a les 11:32:28
# Darrera actualització: 21-04-2005 a les 11:32:28
#

DROP TABLE IF EXISTS `wifi_link`;
CREATE TABLE `wifi_link` (
  `cid` int(11) NOT NULL auto_increment,
  `uid` int(11) NOT NULL default '0',
  `rid1` int(11) NOT NULL default '0',
  `ip1` varchar(20) default NULL,
  `rid2` int(11) NOT NULL default '0',
  `ip2` varchar(20) NOT NULL default '',
  `hosts` enum('2','6','14','30','62','126','254') NOT NULL default '2',
  `link_type` enum('AP/Client','Bridge','WDS','Cable - same subnet','Cable - vlan2','Cable - vlan3','Cable - vlan4','Tunnel') NOT NULL default 'AP/Client',
  `state` enum('Planned','Building','Testing','Working','Dropped') NOT NULL default 'Planned',
  `created` int(11) NOT NULL default '0',
  `changed` int(11) NOT NULL default '0',
  PRIMARY KEY  (`cid`)
) TYPE=MyISAM AUTO_INCREMENT=219 ;
# --------------------------------------------------------

#
# Estructura de la taula `wifi_location`
#
# Creació: 21-04-2005 a les 11:32:28
# Darrera actualització: 21-04-2005 a les 11:32:28
#

DROP TABLE IF EXISTS `wifi_location`;
CREATE TABLE `wifi_location` (
  `lid` int(11) NOT NULL auto_increment,
  `nid` int(11) NOT NULL default '0',
  `nick` varchar(40) NOT NULL default '',
  `zone` varchar(40) default NULL,
  `lat` decimal(10,6) default NULL,
  `lon` decimal(10,6) default NULL,
  `elevation` int(11) default NULL,
  `sponsor` varchar(40) default NULL,
  `state` enum('Planned','Building','Testing','Working','Dropped') NOT NULL default 'Planned',
  `stable` enum('Yes','Ocasionally') NOT NULL default 'Yes',
  PRIMARY KEY  (`lid`)
) TYPE=MyISAM AUTO_INCREMENT=263 ;
# --------------------------------------------------------

#
# Estructura de la taula `wifi_radio`
#
# Creació: 21-04-2005 a les 11:32:28
# Darrera actualització: 21-04-2005 a les 11:32:28
#

DROP TABLE IF EXISTS `wifi_radio`;
CREATE TABLE `wifi_radio` (
  `rid` int(11) NOT NULL auto_increment,
  `created` int(11) NOT NULL default '0',
  `changed` int(11) NOT NULL default '0',
  `title` varchar(40) NOT NULL default '',
  `ssid` varchar(20) NOT NULL default '',
  `lid` int(10) NOT NULL default '0',
  `nid` int(11) NOT NULL default '0',
  `mode` enum('AP','AP/WDS','Bridge','Client','AP/Client Link') NOT NULL default 'AP',
  `protocol` enum('802.11a','802.11b','802.11g','802.11abg','802.11bg','WiMAX') NOT NULL default '802.11a',
  `mid` int(10) NOT NULL default '0',
  `ip` varchar(20) default NULL,
  `hosts` enum('2','6','14','30','62','126','254') default NULL,
  `mac` varchar(20) default NULL,
  `int_mac` varchar(20) NOT NULL default '''00:00:00:00:00:00''',
  `firmware` enum('n/d','Alchemy Sveasoft','Talisman Sveasoft','OpenWRT') NOT NULL default 'n/d',
  `antenna_type` enum('stock','omni','directive','sector','patch') default NULL,
  `antenna_gain` int(4) default NULL,
  `antenna_orientation` enum('N','NE','E','SE','S','SW','W','NW') default NULL,
  `channel` int(3) default NULL,
  `sponsor` varchar(20) default NULL,
  `state` enum('Planned','Building','Testing','Working','Dropped') NOT NULL default 'Planned',
  `comments` blob NOT NULL,
  PRIMARY KEY  (`rid`),
  UNIQUE KEY `title` (`title`)
) TYPE=MyISAM AUTO_INCREMENT=227 ;

    
