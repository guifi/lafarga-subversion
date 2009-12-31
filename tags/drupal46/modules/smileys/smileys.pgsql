BEGIN;

CREATE TABLE smileys (
  id int NOT NULL DEFAULT '0',
  acronyms varchar(255) DEFAULT '' NOT NULL,
  image varchar(255) DEFAULT '' NOT NULL,
  description varchar(64) DEFAULT '' NOT NULL,
  standalone INT2 NOT NULL DEFAULT '0',
  PRIMARY KEY (id)

);

CREATE SEQUENCE smileys_id_seq INCREMENT 1 START 1;

COMMIT;
