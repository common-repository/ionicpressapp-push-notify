<?php

/*
  Controller name: IonicPressApp Push Notify
  Controller description: Register the device, Get and Change the Push Notification status(on/off)
 
 */

/**
 * class JSON_API_Ipa_Push_Controller
 * 
 * @author  SS4U Development Team <info@softsolutions4u.com>
 * @version 1.0.0
 */
class JSON_API_Ipa_Push_Controller
{
    /**
     * Register the device to the send the push notification
     * 
     * @return array
     */
    public function register()
    {
        global $json_api, $wpdb;
        $table_name = $wpdb->prefix . 'ipa_push_users';
        if (!$json_api->query->id) {
            $json_api->error("You must include 'id' variable in your request. ");
        }
        $register_id = sanitize_text_field($json_api->query->id);
        $os         = ''; //currently os version is not included
        
        $sql    = "SELECT `reg_id` FROM `$table_name` WHERE `reg_id`='$register_id'";
        $result = $wpdb->get_results($sql);

        if (!$result) {
            $sql = "INSERT INTO $table_name (reg_id) VALUES ('$register_id')";
            $wpdb->query($sql);
            $status = 'You are registered successfully';
         } else {
            $status = 'You are already registered';
         }

        return array(
            'message' => $status,
        );
    }

    /**
     * Change the device status
     *
     * @return array
     */
    public function change_status() {
        global $json_api, $wpdb;
        $table_name = $wpdb->prefix . 'ipa_push_users';
        if (!$json_api->query->id) {
            $json_api->error("unable to update the status");
        }

        $register_id = sanitize_text_field($json_api->query->id);
        $status = $json_api->query->status == 'true' ? '1' : '0';

        $update_sql = "UPDATE $table_name SET `status` = '$status' WHERE `reg_id`='$register_id'";
        $wpdb->query($update_sql);
        $notification_status = $this->get_status();
        return array(
            'notificationStatus' => $notification_status,
        );
    }

    /**
     *
     * Get the device status by ipa token id.
     *
     * @return boolean true | false
     */
    public function get_status() {
        global $json_api, $wpdb;
        $table_name = $wpdb->prefix . 'ipa_push_users';
        if (!$json_api->query->id) {
            $json_api->error("unable to update the status");
        }

        $register_id = sanitize_text_field($json_api->query->id);
        $sql = "SELECT `status` FROM `$table_name` WHERE `reg_id`='$register_id'";
        $result = $wpdb->get_results($sql);
        return ($result[0]->status) ? true : false;
    }
}
