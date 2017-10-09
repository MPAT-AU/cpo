<?php
/*
 * Plugin Name: MPAT Custom Posts Order
 * Plugin URI: https://github.com/MPAT-eu/cpo
 * Description: Settingless Custom Post Order
 * Version: 1.0.1
 * Author: Jean-Philippe Ruijs
 * Author URI: https://github.com/MPAT-eu/
 * Text Domain: mpat-cpo
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

/**
* Define
*/

define( 'MPO_URL', plugins_url( '', __FILE__ ) );
define( 'MPO_DIR', plugin_dir_path( __FILE__ ) );

/**"orderby"
* Uninstall hook
*/

register_uninstall_hook( __FILE__, 'mpo_uninstall' );

function mpo_uninstall()
{
    global $wpdb;
    if (function_exists( 'is_multisite' ) && is_multisite()) {
        $curr_blog = $wpdb->blogid;
        $blogids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
        foreach ($blogids as $blog_id) {
            switch_to_blog( $blog_id );
            mpo_uninstall_db();
        }
        switch_to_blog( $curr_blog );
    } else {
        mpo_uninstall_db();
    }
}

function mpo_uninstall_db()
{
    global $wpdb;
    $result = $wpdb->query( "DESCRIBE $wpdb->terms `term_order`" );
    if ($result) {
        $query = "ALTER TABLE $wpdb->terms DROP `term_order`";
        $result = $wpdb->query( $query );
    }
    delete_option( 'mpo_activation' );
}

/**
* Class & Method
*/

$mpo = new Mpo();

class Mpo
{
    function __construct()
    {
        if (!get_option( 'mpo_activation' )) {
            $this->mpo_activation();
        }
        add_action( 'admin_init', array( $this, 'load_script_css' ) );
        // sortable ajax action
        add_action( 'wp_ajax_update-menu-order', array( $this, 'update_menu_order' ) );
        // reorder post types
        add_action( 'pre_get_posts', array( $this, 'mpo_pre_get_posts' ) );
    }
    
    function mpo_activation()
    {
        global $wpdb;
        $result = $wpdb->query( "DESCRIBE $wpdb->terms `term_order`" );
        if (!$result) {
            $query = "ALTER TABLE $wpdb->terms ADD `term_order` INT( 4 ) NULL DEFAULT '0'";
            $result = $wpdb->query( $query );
        }
        update_option( 'mpo_activation', 1 );
    }

    function _check_load_script_css()
    {
        $active = true;
        // exclude (sorting, addnew page, edit page)
        if (isset( $_GET['orderby'] ) || strstr( $_SERVER['REQUEST_URI'], 'action=edit' ) || strstr( $_SERVER['REQUEST_URI'], 'wp-admin/post-new.php' )) {
            return false;
        } else {
            if (isset( $_GET['post_type'] ) && !isset( $_GET['taxonomy'] )) {
                // if page or custom post types
            }
            if (!isset( $_GET['post_type'] ) && strstr( $_SERVER['REQUEST_URI'], 'wp-admin/edit.php' )) {
                // if post
            }
        }
        return $active;
    }

    function load_script_css()
    {
        if ($this->_check_load_script_css()) {
            wp_enqueue_script( 'jquery' );
            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_script( 'mpojs', MPO_URL.'/js/mpat-cpo.js', array( 'jquery' ), null, true );
            wp_enqueue_style( 'mpo', MPO_URL.'/css/mpat-cpo.css', array(), null );
        }
    }

    function update_menu_order()
    {
        global $wpdb;

        parse_str( $_POST['order'], $data );
        
        if (!is_array( $data )) {
            return false;
        }
            
        // get objects per now page
        $id_arr = array();
        foreach ($data as $key => $values) {
            foreach ($values as $position => $id) {
                $id_arr[] = $id;
            }
        }
        
        // get menu_order of objects per now page
        $menu_order_arr = array();
        foreach ($id_arr as $key => $id) {
            $q ="SELECT menu_order FROM $wpdb->posts WHERE ID = ".intval( $id ) ;
            //var_dump($q);
            $results = $wpdb->get_results( $q );
            foreach ($results as $result) {
                $menu_order_arr[] = $result->menu_order;
            }
        }
        
        // maintains key association = no
        sort( $menu_order_arr );
        
        foreach ($data as $key => $values) {
            foreach ($values as $position => $id) {
                $wpdb->update( $wpdb->posts, array( 'menu_order' => $menu_order_arr[$position] ), array( 'ID' => intval( $id ) ) );
            }
        }
    }
    
    function mpo_pre_get_posts($wp_query)
    {
        $wp_query->set( 'orderby', 'menu_order' );
        $wp_query->set( 'order', 'ASC' );
    }
}
