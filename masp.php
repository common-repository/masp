<?php

/**
 * @package masp
 * @version 0.2
 */
/*
Plugin Name: MASP
Plugin URI: http://wordpress.org/extend/plugins/masp/
Description: MASP is an advanced spam filtering web service. Learns and reacts to incoming threats.
Author: WebUltd s.c.
Version: 0.2.1
Author URI: http://webultd.com/
License: GPLv2 or later
*/

class Masp
{
    private $api_protocol = 'http://';
    private $api_domain = 'api.masp.in';
    private $service_domain = 'masp.in';
    public $options = array();


    public function __construct() {
        $this->options = get_option('masp_options');
    }

    public function init() {
        $plugin_dir = basename(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'languages';
        load_plugin_textdomain( 'masp', false, $plugin_dir );
    }

    private function do_request($url, $postData = null, $put = false) {
        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $url);

        if(!is_null($postData)) {
            curl_setopt($c, CURLOPT_POST, 1);
            curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($postData));

            if($put) {
                curl_setopt($c, CURLOPT_CUSTOMREQUEST, "PUT");
            }
        }

        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($c);
        curl_close($c);

        return $response;
    }

    public function insert_comment($id, $comment) {
        $api_key = $this->options['api_key'];

        $data = array(
            'content' => $comment->comment_content,
            'thread_id' => get_permalink(),
            'thread_title' => get_the_title(),
        );

        $url = $this->api_protocol . $this->api_domain . '/message/add/' . $api_key;
        $result = $this->do_request($url, $data);

        $result = json_decode($result, true);

        $meta = array(
            'id' => $result['id'],
            'is_spam' => $result['is_spam'],
        );

        if($meta['is_spam']) {
            wp_set_comment_status($comment->comment_ID, 'spam');
        }

        update_comment_meta($comment->comment_ID, 'masp_result', json_encode($meta));
    }

    public function change_status($new_status, $old_status, $comment) {
        $api_key = $this->options['api_key'];

        $meta = get_comment_meta($comment->comment_ID, 'masp_result');

        if($meta) {
            $meta = json_decode($meta[0], true);
            $url = $this->api_protocol . $this->api_domain . '/message/manual/' . $meta['id'] . '/' . $api_key;

            if($new_status == 'spam' && !$meta['is_spam']) {
                $result = $this->do_request($url, array('is_spam' => 1), true);
                $meta['is_spam'] = 1;
            } else {
                $result = $this->do_request($url, array('is_spam' => 0), true);
                $meta['is_spam'] = 0;
            }

            update_comment_meta($comment->comment_ID, 'masp_result', json_encode($meta));
        }
    }

    public  function add_menu_item() {
        // add_options_page | add_menu_page
        $this->options_page = add_submenu_page (
            // Menu page to attach to
            'plugins.php',

            // page title
            __('masp_settings', 'masp'),

            // menu title
            'MASP',

            // permissions
            'manage_options',

            // page-name (used in the URL)
            'masp',

            // clicking callback function
            array(&$this, 'generate_settings_page')
        );
    }

    public function generate_settings_page() {
        $msg = '';

        if(!empty($_POST) && check_admin_referer('masp_options_update', 'masp_admin_nonce')) {

            $this->options['api_key'] = $_POST['masp_api_key'];
            update_option('masp_options', $this->options);

            $msg = '<div class="updated fade"><p>' . __('settings_have_been_updated', 'masp') . '</p></div>';
        }

        add_meta_box('masp-settings', __('settings', 'masp'), array(&$this, 'generate_api_box'), $this->options_page, 'normal', 'core');

        include('templates' . DIRECTORY_SEPARATOR . 'admin_page.php');
    }

    public function generate_api_box() {
        $apiKey = esc_attr($this->options['api_key']);
        include('templates' . DIRECTORY_SEPARATOR . 'api_box.php');
    }

    public function check_wordpress_version() {
        global $wp_version;

        if(version_compare($wp_version, '3.0', '<')) {
            exit(__('version_requires', 'masp'));
        }
    }
}

$masp = new Masp();

add_action('init', array(&$masp, 'check_wordpress_version'));
add_action('plugins_loaded', array(&$masp, 'init'));
add_action('admin_menu', array(&$masp, 'add_menu_item'));
add_action('wp_insert_comment', array(&$masp, 'insert_comment'), 1, 2);
add_action('transition_comment_status', array(&$masp, 'change_status'), 1, 3);

?>
