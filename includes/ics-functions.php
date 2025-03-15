<?php
function calendar_add_rewrite_rule()
{
    add_rewrite_rule(
        '^calendar/([^/]+)$',
        'index.php?calendar_category=$matches[1]',
        'top'
    );
}
add_action('init', 'calendar_add_rewrite_rule');

// Register query vars
function calendar_register_query_vars($vars)
{
    $vars[] = 'calendar_category';
    return $vars;
}
add_filter('query_vars', 'calendar_register_query_vars');

// Handle custom endpoint
function calendar_template_redirect()
{
    $calendar_category = get_query_var('calendar_category');

    if ($calendar_category) {
        global $wpdb;
        $table_name = $wpdb->prefix . "simple_calendar";

        if (array_key_exists($calendar_category, $GLOBALS['CATEGORIES'])) {
                    $category = $GLOBALS['CATEGORIES'][$calendar_category] . " Course Calendar";
        } else {
            $category = $GLOBALS['CATEGORIES']["dan-international"] . " Course Calendar (all)";
        }

        // Special case for all calendars
        $category_filter = ($calendar_category !== 'all') ? 
            $wpdb->prepare("AND category = %s", $calendar_category) : "";

        $events = $wpdb->get_results("SELECT * FROM $table_name WHERE published = 1 $category_filter ORDER BY start_time ASC");

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: inline; filename="' . sanitize_file_name($calendar_category) . '.ics"');

        echo "BEGIN:VCALENDAR\r\n";
        echo "VERSION:2.0\r\n";
        echo "PRODID:-//Simple Calendar//NONSGML v1.0//EN\r\n";
        echo "CALSCALE:GREGORIAN\r\n";
        echo "METHOD:PUBLISH\r\n";
        echo "X-WR-CALNAME:" . $category ."\r\n";
        echo "X-WR-TIMEZONE:UTC\r\n";

        foreach ($events as $event) {
            $dtstamp = gmdate('Ymd\THis\Z');

            echo "BEGIN:VEVENT\r\n";
            echo "UID:$event->uuid\r\n";
            echo "DTSTAMP:$dtstamp\r\n";
            echo "SUMMARY:" . esc_html($event->title) . "\r\n";
            if ($event->all_day) {
                echo "DTSTART;VALUE=DATE:" . date('Ymd', strtotime($event->start_time)) . "\n";
                echo "DTEND;VALUE=DATE:" . date('Ymd', strtotime($event->end_time)) . "\n";
            } else {
                echo "DTSTART:" . gmdate('Ymd\THis\Z', strtotime($event->start_time)) . "\r\n";
                echo "DTEND:" . gmdate('Ymd\THis\Z', strtotime($event->end_time)) . "\r\n";
            }
            echo "LOCATION:" . esc_html($event->location) . "\r\n";
            echo "DESCRIPTION:" . esc_html($event->description) . "\r\n";
            echo "CATEGORIES:" . esc_html($event->category) . "\r\n";
            echo "URL:" . esc_html($event->url) . "\r\n";
            echo "END:VEVENT\r\n";
        }

        echo "END:VCALENDAR\r\n";
        exit;
    }
}
add_action('template_redirect', 'calendar_template_redirect');
