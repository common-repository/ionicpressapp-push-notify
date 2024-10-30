<?php

/*
  Controller name: Ipa Push Messenger
   
 */

namespace Ipa_Push\Controllers;

/**
 * Ipa Push Messenger class
 *
 * @author  SS4U Development Team <info@softsolutions4u.com>
 * @version 1.0.0
 */
class Ipa_Push_Messenger
{
    protected $gcm_url = 'https://android.googleapis.com/gcm/send';
    protected $api_key;
    protected $registration_ids = array();
    protected $data;

    /**
     * Google cloud messaging api key
     * 
     * @param string $api_key
     */
    public function __construct($api_key)
    {
        $this->api_key = $api_key;
    }

    /**
     * Add registration id to send notification
     * 
     * @param mixed $id Single Registration id or multiple registration id's as array
     * 
     * @return type
     */
    public function add_registration_id($id)
    {
        if (empty($id)) {
            return;
        }
        if (is_string($id)) {
            $this->registration_ids[] = $id;
        } elseif (is_array($id)) {
            $this->registration_ids = array_merge($this->registration_ids, $id);
        }
        $this->registration_ids = array_values(array_unique($this->registration_ids));
    }

    /**
     * Set the data to be send in notification
     * 
     * @param mixed $data
     */
    public function set_data($data)
    {
        $this->data = $data;
    }

    /**
     * Dispatch the messages
     * 
     * @throws \Exception
     */
    public function send()
    {
        try {
            $fields = array('data' => $this->data);
            // Gcm allows only 1000 ids per request
            $send_ids_chunk = array_chunk($this->registration_ids, 1000);
            foreach ($send_ids_chunk as $ids) {
                $fields['registration_ids'] = $ids;
                $response = $this->send_curl($fields);
                if($response['failure'] || $response['canonical_ids']){
                    $this->post_send($response['results'], $ids);
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Init the curl fucntion to send the notification
     * 
     * @param array $fields Fields to be send in the message
     * 
     * @return string Result from the Google cloud messaging
     * @throws Exception
     */
    protected function send_curl($fields)
    {
        if (empty($this->api_key)) {
            throw new Exception('Api key is empty');
        }

        if (empty($fields)) {
            throw new Exception('Fields are empty');
        }

        $headers = array(
            'Authorization: key=' . $this->api_key,
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->gcm_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);
        return json_decode($result, true);
    }

    /**
     * process the cloud messaging response for after send the notification to cloud
     *
     * @param array $response Google cloud messaging Result
     * @param array $gcm_ids  notification send gcm token ids
     *
     * @return null
     */
    public function post_send($response, $gcm_ids)
    {
        global $wpdb;
        if (empty($response) || empty($gcm_ids)) {
            return;
        }
        $table_name = $wpdb->prefix . 'ipa_push_users';
        foreach ($response as $key => $resp) {
            $is_delete = false;
            $gcm_token_id = wp_slash($gcm_ids[$key]);
            if (isset($resp['registration_id'])) {
                $is_delete = true;
                $canonical_id = wp_slash($resp['registration_id']);
                $sql = "SELECT `reg_id` FROM $table_name WHERE `reg_id` = '$canonical_id'";
                $result = $wpdb->get_results($sql);
                if (empty($result)) {
                    $update_sql = "UPDATE $table_name SET  `reg_id` =  '$canonical_id' WHERE  `reg_id` = '$gcm_token_id'";
                    $wpdb->query($update_sql);
                    $is_delete = false;
                }
            }
            if (isset($resp['error']) || $is_delete) {
                $delete_sql = "DELETE FROM `$table_name` WHERE `reg_id` = '$gcm_token_id'";
                $wpdb->query($delete_sql);
            }
        }
    }
}
