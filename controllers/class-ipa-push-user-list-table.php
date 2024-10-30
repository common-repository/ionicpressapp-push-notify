<?php

/*
  Controller name: Ipa Push User List Table
  
 */

namespace Ipa_Push\Controllers;

/**
 * Ipa Push User List Table class
 *
 * @author  SS4U Development Team <info@softsolutions4u.com>
 * @version 1.0.0
 */
class Ipa_Push_User_List_Table extends \WP_List_Table
{
    /**
     * Default constructor
     */
    function __construct() {
        parent::__construct(array(
            'ajax' => false
        ));
    }

    /**
     * Get the value of the column
     * 
     * @param array  $item        Row data
     * @param string $column_name Column name
     * 
     * @return string Column string 
     */
    function column_default($item, $column_name){
        switch($column_name){
            case 'reg_id':
            case 'os':
            case 'created_at':
            case 'status':
                return $item[$column_name];
            default:
                return print_r($item, true);
        }
    }

    /**
     * Parse column cb(check box)
     * 
     * @param array $item A singular item (one full row's worth of data)
     * 
     * @return string Text to be placed inside the column
     */
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            'bulk-delete',
            $item['ID']
        );
    }

    /**
     * Columns array
     * 
     * @return array An associative array containing column information
     */
    function get_columns(){
        $columns = array(
            'cb'         => '<input type="checkbox" />',
            'reg_id'     => 'Device Id',
            'os'         => 'Os',
            'created_at' => 'Registerd Date',
            'status'     => 'Status'
        );
        return $columns;
    }

    /**
     * Get the sortable columns list
     * 
     * @return array An associative array containing all the columns that should be sortable
     */
    function get_sortable_columns() {
        $sortable_columns = array(
            'reg_id'     => array('reg_id',false),     //true means it's already sorted
            'os'         => array('os',false),
            'created_at' => array('created_at',false),
            'status'     => array('status',false)
        );
        return $sortable_columns;
    }

    /**
     * Bulk actions list
     * 
     * @return array An associative array containing all the bulk actions
     */
    function get_bulk_actions() {
        $actions = array(
            'delete'    => 'Delete'
        );
        return $actions;
    }

    /**
     * Perform the bulk action
     */
    function process_bulk_action() {
        if ('delete' === $this->current_action()) {
            $delete_ids = esc_sql($_REQUEST['bulk-delete']);
            foreach ($delete_ids as $id) {
              $this->delete_user($id);
            }
        }
    }

    /** 
     * Prepare the data to show in table
     */
    function prepare_items() {

        $limit    = 10;
        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        $this->process_bulk_action();
        
        $users       = $this->get_users();        
        $current_page = $this->get_pagenum();
        $total_items  = count($users);
        $data        = array_slice($users, (($current_page-1)*$limit), $limit);
        
        $this->items = $data;
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $limit,
            'total_pages' => ceil($total_items/$limit)
        ));
    }

    /**
    * Retrieve customer’s data from the database
    *
    * @return array Users array
    */
    public function get_users()
    {
        global $wpdb;

        $sql = "SELECT * FROM {$wpdb->prefix}ipa_push_users";

        if (!empty($_REQUEST['orderby'])) {
            $sql .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
            $sql .=!empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
        }

        $result = $wpdb->get_results($sql, 'ARRAY_A');

        return $result;
    }

    /**
     * Delete the user from database
     * 
     * @param int $id User id
     */
    public function delete_user($id)
    {
        global $wpdb;

        $wpdb->delete(
            "{$wpdb->prefix}ipa_push_users",
            array('ID' => $id),
            array('%d')
        );
    }
}