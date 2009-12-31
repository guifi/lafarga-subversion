<?php
// $Id: field_image.inc,v 1.15.4.1 2005/04/16 12:56:10 JonBob Exp $

function flexinode_field_image_name($field) {
  return t('image');
}

function flexinode_field_image_form($field, $node) {
  $fieldname = 'flexinode_'. $field->field_id;
  $output = '';
  if ($node->$fieldname) {
    $output .= form_hidden($fieldname .'_old', serialize($node->$fieldname));
  }
  $output .= form_file(t($field->label), $fieldname, 40, ($node->$fieldname ? t('"%filename" has been uploaded. If you upload another file, the current file will be replaced.', array('%filename' => $node->$fieldname->filename)) : '') .' '. t($field->description) .' '. t('The file is limited to %kbKB and a resolution of %wxh pixels (width x height).', array('%wxh' => $field->options[1], '%kb' => $field->options[2])), $field->required);
  return $output;
}

function flexinode_field_image_db_select($field) {
  $fieldname = 'flexinode_'. $field->field_id;
  return $fieldname .'.serialized_data AS '. $fieldname;
}

function flexinode_field_image_db_sort_column($field) {
  return 'flexinode_'. $field->field_id .'.textual_data';
}

function flexinode_field_image_insert($field, $node) {
  $fieldname = 'flexinode_'. $field->field_id;
  $node->$fieldname = file_save_upload($node->$fieldname, $node->$fieldname->filename);
  $serialized = is_object($node->$fieldname) ? serialize($node->$fieldname) : '';
  db_query("INSERT INTO {flexinode_data} (nid, field_id, textual_data, serialized_data) VALUES (%d, %d, '%s', '%s')", $node->nid, $field->field_id, $node->$fieldname->filename, $serialized);
  return $node;
}

function flexinode_field_image_delete($field, $node, $unconditional = 0) {
  $fieldname = 'flexinode_'. $field->field_id;
  $result = db_fetch_object(db_query('SELECT serialized_data FROM {flexinode_data} WHERE nid = %d AND field_id = %d', $node->nid, $field->field_id));
  $file = unserialize($result->serialized_data);
  if ($unconditional || $node->$fieldname != $file) {
    file_delete($file->filepath);
  }
}

function flexinode_field_image_validate($field, $node) {
  $fieldname = 'flexinode_'. $field->field_id;

  $file = flexinode_validate_picture($field, $node);

  if (is_string($file)) {
    return array('value' => $node->$fieldname, 'error' => $file);
  }
  else if (is_object($file)) {
    return array('value' => $file);
  }
  else if (empty($node->$fieldname)) {
    return array('value' => unserialize($node->{$fieldname .'_old'}));
  }
}

function flexinode_field_image_format($field, $node, $brief = 0) {
  $fieldname = 'flexinode_'. $field->field_id;
  $file = is_object($node->$fieldname) ? $node->$fieldname : unserialize($node->$fieldname);
  if ($file) {
    if ($brief) {
      return '<a href="'. file_create_url($file->filepath) .'">'. check_plain($file->filename) .'</a> ('. format_size($file->filesize) .')';
    }
    else {
      return '<img src="'. file_create_url($file->filepath) .'" alt="'. check_plain($node->title) .'" />';
    }
  }
}

function flexinode_field_image_load($field, $node) {
  $fieldname = 'flexinode_'. $field->field_id;
  return unserialize($node->$fieldname);
}

function flexinode_field_image_config($field, $edit) {
  if (!isset($edit['options'])) {
    $edit['options'][1] = '400x300';
    $edit['options'][2] = 100;
  }
  
  $output = form_textfield(t('Maximum picture dimensions'), 'options][', $edit['options'][1], 60, 128, t('Maximum dimensions for pictures. Format: widthxheight'));
  $output .= form_textfield(t('Maximum picture size'), 'options][', $edit['options'][2], 60, 128, t('Maximum picture file size, in kB.'));

  return form_group(t('Options'), $output, t('Options for the image upload.'));
}

function flexinode_field_image_settings() {
  $group = form_textfield(t('Default thumbnail resolution'), 'flexinode_thumb_size', variable_get('flexinode_thumb_size', '100'), 10, 255, t('Default size of thumbnails: format will be the same as original image. Use just one dimension, and put a "x" to specify height. Examples: "100" for width of 100; "x200" for height of 200.'));
  $group .= form_textfield(t('Imagemagick Convert path'), 'flexinode_convert_path', variable_get('flexinode_convert_path', '/usr/local/bin/convert'), 50, 255, t('Absolute path to ImageMagick convert executable. Include the filename at the end.'));
  return form_group(t('Image processing'), $group);
}

/**
 * @addtogroup themeable
 * @{
 */

/**
 * Format an image for display in a node.
 *
 * @param field_id
 *   Which field is being displayed (useful when overriding this function
 *   if you want to style one particular field differently).
 * @param label
 *   The label for the field as displayed on the node form.
 * @param file
 *   The file that the user has uploaded. This is an object as provided
 *   by file.inc.
 * @param formatted_value
 *   The image as an HTML tag.
 */
function theme_flexinode_image($field_id, $label, $file, $formatted_value) {
  $output = theme('form_element', $label, $formatted_value);
  $output = '<div class="flexinode-image-'. $field_id .'">'. $output .'</div>';
  return $output;
}

/** @} End of addtogroup themeable */

function flexinode_validate_picture($field, $node) {
  $fieldname = 'flexinode_'. $field->field_id;
  
  if ($file = file_save_upload($fieldname)) {
    // check that uploaded file is an image, with a maximum file size and maximum height/width
    if ($size = @getimagesize($file->filepath)) {
      list($maxwidth, $maxheight) = explode('x', $field->options[1]);

      if ((!in_array($size[2], array(1, 2, 3)))) {
        return t('The uploaded file was not a valid image.');
      }
      else if (filesize($file->filepath) > ($field->options[2] * 1000)) {
        return t('The uploaded image is too large; the maximum file size is %num kB.', array('%num' => $field->options[2]));
      }
      else if ($size[0] > $maxwidth || $size[1] > $maxheight) {
        return t('The uploaded image is too large; the maximum dimensions are %nxn pixels.', array('%nxn' => $field->options[1]));
      }
    }

    return $file;
  }

  return 0;
}

function flexinode_make_thumbnail($path, $name) {
  $_imagick_convert = variable_get('flexinode_convert_path', '/usr/local/bin/convert');
  $filter = '-scale '. variable_get('flexinode_thumb_size', '100') .' -filter QUADRATIC';
  list($twidth, $theight) = explode('x', variable_get('flexinode_thumb_size', '100'));
  $size = getimagesize($path);
  $path_only = str_replace($name, '', $path);
  $thumb = $path_only .'t_'. flexinode_image_escape_shell($name);

  if ($size[0] < $twidth || $size[1] < $theight) {
    copy($path, $thumb);
  }
  else {
    $path = flexinode_image_escape_shell($path);
    exec("$_imagick_convert $filter $path $thumb");
  }

  return  $path_only .'t_'. $name;
}

function flexinode_image_escape_shell($filename) {

  if (strstr($_ENV['OS'], 'Windows')) {
  // I can't make escapeshellarg work on windows, it adds a '
  // this should be safe enough
    return '"'.addslashes($filename).'"';
  }
  else {
    return escapeshellarg($filename);
  }
}
?>
