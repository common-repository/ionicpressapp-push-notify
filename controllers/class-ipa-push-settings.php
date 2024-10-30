<?php

/*
  Controller name: Ipa Push Settings
 
 */

namespace Ipa_Push\Controllers;

/**
 * Ipa Push Settings class
 *
 * @author  SS4U Development Team <info@softsolutions4u.com>
 * @version 1.0.0
 */
class Ipa_Push_Settings
{
    public $options;

    /**
     * Default constructor
     */
    public function __construct()
    {
        if (is_admin()) {
            add_action('admin_init', array($this, 'register'));
        }
        $this->options = get_option('ipa-push-setting');
    }

    /**
     * Register the settings values
     */
    public function register()
    {
        add_settings_section('ipa-push-setting-section', '', '', 'ipa-push');
        add_settings_field('api-key', __('Google cloud messaging Api key', 'ipa-push'), array($this, 'api_key_callback'), 'ipa-push', 'ipa-push-setting-section');
        add_settings_field('send-notification-post-update', __('Send notification on post update', 'ipa-push'), array($this, 'send_notification_post_option_callback'), 'ipa-push', 'ipa-push-setting-section');
        register_setting('ipa-push-setting-group', 'ipa-push-setting', '');
    }

    /**
     * Setting notification on post update callback
     */
    public function send_notification_post_option_callback()
    {
        printf(
            '<input type="checkbox" %s name="ipa-push-setting[send-notification-post-update]" value="1" />',
            !empty($this->options['send-notification-post-update']) ? 'checked' : ''
        );
    }

    /**
     * Setting api key callback
     */
    public function api_key_callback()
    {
        printf(
            '<input type="text" name="ipa-push-setting[api-key]" value="%s" />',
            isset( $this->options['api-key'] ) ? esc_attr( $this->options['api-key']) : ''
        );
    }

    /**
     * Render the settings page
     */
    public function show_settings()
    {
        require_once WP_IPA_PUSH_PLUGIN_DIR .'/views/settings.php';
    }
}
