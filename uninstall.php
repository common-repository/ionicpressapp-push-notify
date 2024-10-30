<?php
/**
 * IonicPressApp Push Notify
 *
 * Uninstalling IonicPressApp Push Notify.
 *
 * @author  SS4U Development Team <info@softsolutions4u.com>
 * @version 1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

//drop a custom db table
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ipa_push_users" );

//delete options
delete_option('ipa-push-setting');