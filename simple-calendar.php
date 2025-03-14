<?php
/*
Plugin Name: Simple Calendar
Description: A lightweight event calendar for WordPress.
Version: 1.0
Author: Johannes Bernet
*/

if (!defined('ABSPATH')) {
    exit;
}

define('SC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SC_PLUGIN_URL', plugin_dir_url(__FILE__));
define(
    'CATEGORIES', [
        'dan-international' => 'D.A.N. International',
        'danbw' => 'D.A.N. Baden-Württemberg',
        'dan-in-bayern' => 'D.A.N. in Bayern',
        'dan-berlin' => 'Aikido Dahmeland, Berlin',
        'dan-allgaeu' => 'D.A.N. im Allgäu',
        'dan-belgium' => 'Club Horizon, Brussels',
        'dan-england' => 'D.A.N. England',
    ]
);

require_once SC_PLUGIN_DIR . 'includes/activation.php';
require_once SC_PLUGIN_DIR . 'includes/admin-functions.php';
require_once SC_PLUGIN_DIR . 'includes/ics-functions.php';
require_once SC_PLUGIN_DIR . 'includes/shortcodes.php';

function calendar_enqueue_assets($hook)
{
    if ('toplevel_page_simple-calendar' !== $hook) {
        return;
    }
    
    wp_enqueue_script(
        'simple-calendar-admin-js',
        SC_PLUGIN_URL . 'assets/js/simple-calendar.js',
        array(),
        null,
        true
    );
    wp_enqueue_style(
        'simple-calendar-admin-styles',
        SC_PLUGIN_URL . 'assets/css/admin-styles.css'
    );
}
add_action('admin_enqueue_scripts', 'calendar_enqueue_assets');

function calendar_enqueue_frontend_styles() {
    wp_enqueue_style(
        'simple-calendar-styles',
        SC_PLUGIN_URL . 'assets/css/style.css'
    );
}
add_action('wp_enqueue_scripts', 'calendar_enqueue_frontend_styles');

function calendar_admin_menu()
{
    add_menu_page('Simple Calendar', 'Simple Calendar', 'manage_options', 'simple-calendar', 'calendar_admin_page');
}
add_action('admin_menu', 'calendar_admin_menu');

register_activation_hook(__FILE__, 'calendar_create_table');
