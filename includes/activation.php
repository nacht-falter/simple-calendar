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
        organizer VARCHAR(255) NOT NULL,
        url VARCHAR(255),
        published TINYINT(1) DEFAULT 1,
        uuid VARCHAR(36) NOT NULL UNIQUE
        UNIQUE KEY unique_event (title, start_time, end_time, organizer)
    ) $charset_collate;";

    include_once ABSPATH . "wp-admin/includes/upgrade.php";
    dbDelta($sql);

    // Add calendar-admin role
    $subscriber = get_role('subscriber');
    if (!get_role('calendar-admin')) {
        add_role(
            'calendar-admin',
            'Calendar Admin',
            $subscriber->capabilities
        );
    }

    // add manage_calendars capability to roles
    $roles = ['calendar-admin', 'administrator', 'editor'];

    foreach ($roles as $role_name) {
        $role = get_role($role_name);
        if ($role) {
            $role->add_cap('manage_calendars');
        }
    }
}
