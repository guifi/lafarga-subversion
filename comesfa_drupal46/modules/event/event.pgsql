-- Event.module SQL Definitions
-- $Id: event.pgsql,v 1.3 2005/04/16 02:36:38 crunchywelch Exp $

CREATE TABLE event (
  nid int NOT NULL default '0',
  start int NOT NULL default '0',
  end int NOT NULL default '0',
  tz int NOT NULL default '0',
  PRIMARY KEY (nid),
  KEY start (start)
);
