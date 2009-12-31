Drupal nodewords.module README.txt
==============================================================================

This module allows you to set some meta tags for each node.

Giving more attention to the important keywords and/or description on some of
your nodes allows you to get better search engine positioning (given that you
really only provide the keywords which exist in the node body itself, and do
not try to lie).

Some features include:

* The current supported meta tags are ABSTRACT, COPYRIGHT, DESCRIPTION
  and KEYWORDS.

* You can define a global set of KEYWORDS that will appear on each page of
  your site. Node specific keywords are added before these global
  keywords.

* You can tell "nodewords" to add all terms of some specified vocabularies
  to the KEYWORDS meta tag.

* You can define a global COPYRIGHT tag. You can optionally override this
  copyright on one or more node pages.

* You can optionally insert the teaser as a DESCRIPTION tag, if you leave
  the DESCRIPTION tag empty.

* On taxonomy pages, the term description is used as the DESCRIPTION
  tag. The term itself is added to the list of KEYWORDS.

* On the front page, the site mission is used as the DESCRIPTION tag. And
  the site slogan as the ABSTRACT tag. Both are only included when you
  enable the site mission and/or site slogan in your theme (and if they
  are set).

* You can select which of these tags you want to output on each page. You
  can also remove the edit box for these tags from the node edit page if
  you don't like using it.

* All text of all meta tags are added to the search system so they are
  searable too.

Installation (from scratch)
------------------------------------------------------------------------------

1. Backup your database.

2. Copy 'nodewords.module' to the 'modules/' directory.

3. Import the supplied nodewords.mysql file into your Drupal database. Eg:

   $ mysql -u user -p database < nodewords.mysql

   Do not forget to adjust it to work with your table prefix if you use
   table prefixing.

4. Enable the module as usual from Drupal's admin pages.

5. If required, define global keywords and other settings on the 
   'admin/settings/nodewords' page.

6. Every node you now create will have a "Meta tags" form where you can
   insert the necessary information.

Upgrading from an earlier nodewords 4.6 (or a nodewords CVS-HEAD version)
------------------------------------------------------------------------------

1. Backup your database.

2. Copy the new version of 'nodewords.module' to the 'modules/' directory,
   overwriting any existing one.

3. The 'nodewords' table will change between version 4.6 and 4.7. The
   easiest way to upgrade is:

   a. rename the existing 'nodewords' table to 'nodewords_old', this
      can be done by executing following SQL code:

        RENAME TABLE nodewords TO nodewords_old;

   b. create the new 'nodewords' table

        $ mysql -u user -p database < nodewords.mysql

   Do not forget to adjust it to work with your table prefix if you use
   table prefixing.

4. Copy the file 'update-nodewords.php' to the root of your Drupal
   installation and run it by pointing your webbrowser to its address.

   The script will copy all data into the new 'nodewords' table.

   Remove 'update-nodewords.php' when the update is complete.

5. If required, define global keywords and other settings on the
   'admin/settings/nodewords' page.

Credits / Contact
------------------------------------------------------------------------------

The original author of this module is Andras Barthazi. Mike Carter
(mike[at]buddasworld.co.uk) and Gabor Hojtsy (gobap[at]hp.net)
provided some feature enchanements.
Robrecht Jacques (robrecht.jacques[at]advalvas.be) is the current
active maintainer.

