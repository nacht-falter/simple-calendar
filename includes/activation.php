<?php
// Create events table
function calendar_activate()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "simple_calendar";

    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        start_time DATETIME NOT NULL,
        end_time DATETIME NOT NULL,
        all_day TINYINT(1) DEFAULT 1,
        location VARCHAR(255),
        description TEXT,
        category VARCHAR(255) NOT NULL,
        url VARCHAR(255),
        published TINYINT(1) DEFAULT 1,
        uuid VARCHAR(36) NOT NULL UNIQUE
    ) $charset_collate;";
    
    include_once ABSPATH . "wp-admin/includes/upgrade.php";
    dbDelta($sql);

    // Add calendar-admin role
    $editor = get_role('editor');
    if (!get_role('calendar-admin')) {
        add_role(
            'calendar-admin',
            'Calendar Admin',
            $editor->capabilities
        );
    }

    $role = get_role('calendar-admin');
    $role->add_cap('manage_calendars');
    $admin_role = get_role('administrator');

    if ($admin_role) {
        $admin_role->add_cap('manage_calendars');
    }
}
