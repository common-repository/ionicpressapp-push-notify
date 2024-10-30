<?php

/**
 * Plugin Name: IonicPressApp Push Notify
 * Plugin URI: http://wordpress.org/plugins/ionicpressapp-push-notify/
 * Description: This plugin allows send push notification to mobile app using Google cloud messaging api key
 * Version: 1.0.0
 * Author: Soft Solutions4U
 * Author URI: http://softsolutions4u.com
 * @package ionicpressapp-push-notify
 */

define('WP_IPA_PUSH_PLUGIN_DIR', plugin_dir_path( __FILE__ ));
define('WP_IPA_PUSH_PLUGIN_URL', plugin_dir_url( __FILE__ ));

include_once(ABSPATH . 'wp-admin/includes/plugin.php');
if (!is_plugin_active('json-api/json-api.php')) {
    add_action(
        'admin_notices',
        function() {
            require_once WP_IPA_PUSH_PLUGIN_DIR .'/views/json-api-error.php';
        }
    );
    return;
}

spl_autoload_register(function($class_name) {
    $formatted_class = str_replace('_', '-', strtolower($class_name));
    $class_array = explode('\\', $formatted_class);
    if ($class_array[0] == 'ipa-push') {
        $class = end($class_array);
        $path = WP_IPA_PUSH_PLUGIN_DIR . '/controllers/class-' . $class .'.php';
        if (file_exists($path)) {
            require_once $path;
        }
    }
});
$ipa_push = new \Ipa_Push\Controllers\Ipa_Push();

/**
 * Activate the plugin, Create table if not exists
 */
function ipa_push_activation() 
{
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'ipa_push_users';
    
    $charsetCollate = $wpdb->get_charset_collate();
    if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
        $sql = "CREATE TABLE `$table_name` (
          `ID` int(11) NOT NULL AUTO_INCREMENT,
          `reg_id` text,
          `os` varchar(55) DEFAULT '' NOT NULL,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `status` tinyint(1) NOT NULL DEFAULT '1',
          PRIMARY KEY (`id`)
        ) $charsetCollate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
register_activation_hook(__FILE__, 'ipa_push_activation');
