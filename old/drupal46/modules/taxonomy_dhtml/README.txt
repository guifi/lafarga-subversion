Readme
------

A page listing recent nodes on your site, organized by taxonomy term. Also provides a block
for each vocabulary, listing terms and their node counts. Finally, a box is 
exported to the syndication.module main page.

Requirements
------------

This module requires Drupal CVS circa 4/22/2003 or Drupal 4.2. 

Installation
------------

1. Copy all files in this directory to modules/taxonomy_dhtml/... (i.e. a directory named
   'taxonomy_dhtml' inside the Drupal modules/ directory).

2. If you had previously installed taxonomy_dhtml.module in the modules/ directory, remove it.

3. If you had previously installed following files into the misc/ directory, remove them.

- menuExpandable3.js
- menuExpandable3.css
- plus.gif
- square.gif
- minus.gif

4. Active this module on your admin/system/modules page.

Usage
------------

Just browse to the taxonomy_dhtml page.  Optionally enable a 
block for each vocabulary on your Admin -> Blocks page.

Known Issues
--------------

Some work is needed in order to generalize the collapsible list functionality from this module, 
such that other modules may benefit.

Credits
------

Developed by Gazingus at http://www.gazingus.org/dhtml/?id=109. Adapted
for Drupal by Moshe Weitzman <weitzman AT tejasa.com>

