<?php

/*
  Controller name: Ipa Push
  
 */

namespace Ipa_Push\Controllers;

/**
 * Ipa Push class
 *
 * @author  SS4U Development Team <info@softsolutions4u.com>
 * @version 1.0.0
 */
class Ipa_Push
{
    /**
     * Plugin Settings instance
     */
    protected $obj_settings;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->obj_settings = new Ipa_Push_Settings();
        if (is_admin()) {
            add_action('admin_menu', array($this, 'get_admin_menu'));
        }
        // add post page
        add_action('post_submitbox_misc_actions', array($this, 'get_post_checkbox_option'));
        
        // Register controllers for json api
        add_filter('json_api_controllers', array($this, 'get_json_api_controllers'));
        add_filter('json_api_ipa_push_controller_path', array($this, 'get_ipa_push_controller_path'));
        
        // Register hooks to send Ipa on update post
        add_action('publish_post', array($this, 'send_ipa_push_notification'), 10, 3);
    }

    /**
     * Get the post publish meta box
     */
    function get_post_checkbox_option() {
        global $post;
        if ($this->can_send_notification_on_post_update() && get_post_type($post) == 'post') {
            require_once WP_IPA_PUSH_PLUGIN_DIR .'/views/send-notification-checkbox.php';
        }
    }

    /**
     * Check whether the plugin can able to send notification based on general the settings
     *
     * @return boolean TRUE|FALSE
     */
    public function can_send_notification()
    {
        $api_key = $this->obj_settings->options['api-key'];

        if (empty($api_key)) {
            return false;
        }

        return true;
    }

    /**
     * Check whether the plugin can able to send notification for post update
     *
     * @return boolean TRUE|FALSE
     */
    public function can_send_notification_on_post_update()
    {
        if (!$this->can_send_notification()) {
            return false;
        }
        $notification_option = $this->obj_settings->options['send-notification-post-update'];
        if (empty($notification_option)) {
            return false;
        }

        return true;
    }

    /**
     * Hook to send push notification, while updating the post
     * 
     * @param integer $ID   Post id
     * @param string  $post Post instance
     * 
     * @return null
     */
    public function send_ipa_push_notification($ID, $post)
    {
        if (   !$this->can_send_notification_on_post_update()
            || empty($_POST['ipa-push-send-notification'])
            || 'post' != get_post_type($post)
        ) {
            return;
        }

        $api_key = $this->obj_settings->options['api-key'];

        $post_title  = get_the_title($post);
        $post_url    = get_permalink($post);
        $post_author = get_the_author_meta('display_name', $post->post_author);
        $message    = array(
            'title'      => $post_title,
            'message'    => 'Added by '. $post_author,
            'postId'     => $ID,
            'type'       => ($post->post_date != $post->post_modified) ? 'edit' : 'new',
            'subtitle'   => '',
            'tickerText' => '',
            'msgcnt'     => 1,
            'vibrate'    => 1,
            'contents'   => $post_url
        );
        // Send notification
        try {
            $users        = $this->get_all_users(1);
            $ipa_messenger = new Ipa_Push_Messenger($api_key);
            $ipa_messenger->set_data($message);
            $ipa_messenger->add_registration_id($users);
            $ipa_messenger->send();
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }
    
    /**
     * Hook to register the Ipa push json api controller
     * 
     * @param array $controllers Controllers array
     * 
     * @return array
     */
    function get_json_api_controllers($controllers)
    {
        $controllers[] = 'Ipa_Push';
        return $controllers;
    }
    
    /**
     * Return's the Ipa push json api controller class path
     * 
     * @return string
     */
    function get_ipa_push_controller_path()
    {
        return dirname(__FILE__) . '/class-json-api-ipa-push-controller.php';
    }
    
    /**
     * Get administrator menu
     */
    public function get_admin_menu()
    {
        add_menu_page(
            __('IonicPressApp Push Notify', 'ipa-push'),
            __('IonicPressApp Push Notify', 'ipa-push'),
            'manage_options',
            'ipa-push',
            array($this, 'list_users'),
            'dashicons-cloud'
        );
        add_submenu_page(
            'ipa-push',
            __('New Message', 'ipa-push'),
            __('New Message', 'ipa-push'),
            'manage_options',
            'ipa-push-new-message',
            array($this, 'send_message')
        );
        add_submenu_page(
            'ipa-push',
            __('Settings', 'ipa-push'),
            __('Settings', 'ipa-push'),
            'manage_options',
            'ipa-push-settings',
            array($this->obj_settings, 'show_settings')
        );
    }
    
    /**
     * Return's all register devices id
     * 
     * @return array
     */
    public function get_all_users($status = null)
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ipa_push_users';
        $sql = "SELECT reg_id FROM $table_name";
        if(isset($status)){
            $sql .= ' WHERE status =' . $status;
        }
        $res = $wpdb->get_results($sql);
        $users = array();
        if ($res != false) {
            foreach ($res as $row) {
                array_push($users, $row->reg_id);
            }
        }
        return $users;
    }
    
    /**
     * Parse User's overview section
     */
    public function list_users()
    {
        $list_table = new Ipa_Push_User_List_Table();
        $list_table->prepare_items();
        
        require_once WP_IPA_PUSH_PLUGIN_DIR .'/views/users.php';
    }

    /**
     * Parse New Message page
     */
    public function send_message()
    {
        $post = stripslashes_deep($_POST);
        wp_register_script('chosen.js', WP_IPA_PUSH_PLUGIN_URL . 'lib/chosen/jquery.chosen.js');
        wp_enqueue_script('chosen.js');
        wp_register_style('chosen.css', WP_IPA_PUSH_PLUGIN_URL . 'lib/chosen/chosen.css');
        wp_enqueue_style('chosen.css');

        $api_key = $this->obj_settings->options['api-key'];
        if ($this->can_send_notification() && isset($post['send-notification'])) {
            try {
                $ipa_messenger = new Ipa_Push_Messenger($api_key);
                $ipa_messenger->set_data(array('message' => $post['push-message']));
                $ipa_messenger->add_registration_id($post['users']);
                $ipa_messenger->send();
            } catch (\Exception $e) {
                die($e->getMessage());
            }
        }
        $users = $this->get_all_users(1);
        require_once WP_IPA_PUSH_PLUGIN_DIR .'/views/new-message.php';
    }
}
