Additional installation instructions when using PEAR databases:
---------------------------------------------------------------
When you use a PEAR database with Drupal (currently only PostgreSQL is
actively maintained and supported) you must create the SQL view
drupal_system_catalog for your database before you activate the dba module.

Look in the 'scripts' directory for the PEAR sql scripts.  Currently
the following two PEAR sql scripts are available:

dba.pgsql-7.3 for PostgreSQL 7.3
dba.pgsql-7.4 for PostgreSQL 7.4 and later versions

The way you create this SQL view depends on your system,
but the most common way (for postgresql) is:

  psql -U username yourdatabase < dba.pgsql-7.4
  
  or (with password prompt)
  
  psql -U username -W yourdatabase < dba.pgsql-7.4


Requires:
--------
 - PEAR support in PHP and a PEAR supported database


Credits:
-------
 - SQL scripts for postgres written by AAM <aam@ugpl.de>
