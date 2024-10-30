<?php

if(!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

if(get_option('masp_options') != false) {
    delete_option('masp_options');
}

?>