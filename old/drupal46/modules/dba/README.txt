Overview:
--------
The dba module provides Drupal administrators with direct access to their
Drupal database tables from within the standard Drupal user interface.

WARNING:  If a user is granted (or manages to aquire) 'dba adminster database'
permissions, they are able to directly alter the database.  At minimum, they
are able to modify data, and possibly to drop tables.  Depending on how you
have defined your database permissions, the user may also be able to modify
other databases unrelated to your Drupal installation.  Use at your own risk!


Features:
--------
 - support for MySQL and PostgreSQL
 - ability to execute sql scripts and see the resulting output
 - overview listing all tables and total row counts for each
 - ability to backup one or more tables from database
 - ability to view table data
 - ability to view table description
 - ability to delete all data from one ore more tables
 - ability to edit a specific row (using simple form)
 - ability to delete a specific row
 - in MySQL, ability to check and repair one or more tables
 - two permissions: 'dba view database' and 'dba administer database'


Comments:
--------
The ability to edit or delete a specific row will only be available if the
given table has a primary key.  If the table does not have a primary key,
we can't safely modify a single row, so the options are not available.
Examples of tables that don't have a primary key include: accesslog, blocks,
filters and search_index.

Installation and configuration:
------------------------------
Installation is as simple as copying the module into your 'modules' directory,
then enabling the module at 'administer >> modules'.  If using a MySQL database,
you can optionally configure default values for the check and repair
functionality at 'adminsiter >> settings >> dba'.

If using a PEAR database (only PostgreSQL is supported at this time), you will
need to follow the additional directions found in README.pgsql prior to
enabling this module.


Requires:
--------
 - Drupal 4.5


Credits:
-------
 - Written by Jeremy Andrews <jeremy@kerneltrap.org>
 - PostgreSQL support provided by AAM <aam@ugpl.de>
