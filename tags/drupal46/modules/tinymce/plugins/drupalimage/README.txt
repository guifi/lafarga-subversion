 drupalimage plugin for TinyMCE
-----------------------------

About:
  This plugin integrates the Drupal img_assist module with TinyMCE allowing you
  to upload, browse and insert images into your post. You must first install
  img_assist:

    http://drupal.org/project/img_assist

Installation instructions:
  * Copy the drupalimage directory to the plugins directory of TinyMCE (/jscripts/tiny_mce/plugins).
  * Add plugin to TinyMCE plugin option list example: plugins : "drupalimage".
    The advanced theme already has done this step.
  * Add this "a[name|href|target|title|onclick]" to extended_valid_elements option.
    The advanced theme already has done this step.

Initialization example:
  tinyMCE.init({
    theme : "advanced",
    mode : "textareas",
    plugins : "preview",
    extended_valid_elements : "a[name|href|target|title|onclick]"
  });
