<?php

/**
  Plugin Name: Double Click
  Plugin URI: https://github.com/ControleOnline/doubleclick
  Description: Double Click
  Version: 1.0.1
  Author: Controle Online
  Author URI: http://www.controleonline.com
  License: GPL2
 */
chdir(dirname(__FILE__) . DIRECTORY_SEPARATOR . '../../../');
require_once (dirname(__FILE__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
require_once ( ABSPATH . 'wp-admin/includes/upgrade.php' );
$WPDoubleClick = \DoubleClick\WPDoubleClick::run($wpdb);
add_action('activated_plugin', array('\DoubleClick\WPDoubleClick', 'activateDoubleClick'), 10);
add_action('deactivated_plugin', array('\DoubleClick\WPDoubleClick', 'deactivateDoubleClick'), 10);
