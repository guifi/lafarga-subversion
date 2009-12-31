
--
-- Table structure for table `tinymce_settings`
--

CREATE TABLE tinymce_settings (
  name varchar(128) NOT NULL default '',
  settings text NOT NULL,
  PRIMARY KEY  (name)
) TABLESPACE drupal_data;

--
-- Table structure for table `tinymce_role`
--

CREATE TABLE tinymce_role (
  name varchar(128) NOT NULL default '',
  rid smallint NOT NULL default '0',
  PRIMARY KEY (name,rid)
) TABLESPACE drupal_data;
