<?php
function calendar_add_rewrite_rule()
{
    add_rewrite_rule(
        '^calendar/([^/]+)$',
        'index.php?calendar_organizer=$matches[1]',
        'top'
    );
}
add_action('init', 'calendar_add_rewrite_rule');

// Register query vars
function calendar_register_query_vars($vars)
{
    $vars[] = 'calendar_organizer';
    return $vars;
}
add_filter('query_vars', 'calendar_register_query_vars');

// Handle custom endpoint
function calendar_template_redirect()
{
    $calendar_organizer = get_query_var('calendar_organizer');
    if ($calendar_organizer) {
        global $wpdb;
        $table_name = $wpdb->prefix . "simple_calendar";
        if (array_key_exists($calendar_organizer, $GLOBALS['ORGANIZATIONS'])) {
            $organizer = $GLOBALS['ORGANIZATIONS'][$calendar_organizer] . " Course Calendar";
        } else {
            $organizer = $GLOBALS['ORGANIZATIONS']["dan-international"] . " Course Calendar (all)";
        }
        // Special case for all calendars
        $organizer_filter = ($calendar_organizer !== 'all') ?
            $wpdb->prepare("AND organizer = %s", $calendar_organizer) : "";
        $events = $wpdb->get_results("SELECT * FROM $table_name WHERE published = 1 $organizer_filter ORDER BY start_time ASC");

        $wp_timezone = wp_timezone_string();

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: inline; filename="' . sanitize_file_name($calendar_organizer) . '.ics"');

        echo "BEGIN:VCALENDAR\r\n";
        echo "VERSION:2.0\r\n";
        echo "PRODID:-//Simple Calendar//NONSGML v1.0//EN\r\n";
        echo "CALSCALE:GREGORIAN\r\n";
        echo "METHOD:PUBLISH\r\n";
        echo "X-WR-CALNAME:" . $organizer ."\r\n";
        echo "X-WR-TIMEZONE:" . $wp_timezone . "\r\n";

        echo "BEGIN:VTIMEZONE\r\n";
        echo "TZID:" . $wp_timezone . "\r\n";

        $year = date('Y');

        // Daylight saving time
        echo "BEGIN:DAYLIGHT\r\n";
        echo "DTSTART:" . $year . "0331T020000\r\n";
        echo "TZOFFSETFROM:+0100\r\n";
        echo "TZOFFSETTO:+0200\r\n";
        echo "TZNAME:CEST\r\n";
        echo "END:DAYLIGHT\r\n";

        // Standard time
        echo "BEGIN:STANDARD\r\n";
        echo "DTSTART:" . $year . "1027T030000\r\n";
        echo "TZOFFSETFROM:+0200\r\n";
        echo "TZOFFSETTO:+0100\r\n";
        echo "TZNAME:CET\r\n";
        echo "END:STANDARD\r\n";

        echo "END:VTIMEZONE\r\n";

        foreach ($events as $event) {
            $dtstamp = gmdate('Ymd\THis\Z');
            echo "BEGIN:VEVENT\r\n";
            echo "UID:" . $event->uuid . "\r\n";
            echo "DTSTAMP:" . $dtstamp . "\r\n";
            echo "SUMMARY:" . ical_escape($event->title) . "\r\n";

            if ($event->all_day) {
                $start_date = date('Ymd', strtotime($event->start_time));

                $end_date = date('Ymd', strtotime($event->end_time . ' +1 day'));

                echo "DTSTART;VALUE=DATE:" . $start_date . "\r\n";
                echo "DTEND;VALUE=DATE:" . $end_date . "\r\n";
            } else {
                $start_datetime = new DateTime($event->start_time, new DateTimeZone($wp_timezone));
                $end_datetime = new DateTime($event->end_time, new DateTimeZone($wp_timezone));

                echo "DTSTART;TZID=" . $wp_timezone . ":" . $start_datetime->format('Ymd\THis') . "\r\n";
                echo "DTEND;TZID=" . $wp_timezone . ":" . $end_datetime->format('Ymd\THis') . "\r\n";
            }

            if (!empty($event->location)) {
                echo "LOCATION:" . ical_escape($event->location) . "\r\n";
            }

            if (!empty($event->description)) {
                echo "DESCRIPTION:" . ical_escape($event->description) . "\r\n";
            }

            if (!empty($event->organizer)) {
                echo "ORGANIZER:" . ical_escape($event->organizer) . "\r\n";
            }

            if (!empty($event->url)) {
                echo "URL:" . ical_escape($event->url) . "\r\n";
            }

            echo "END:VEVENT\r\n";
        }
        echo "END:VCALENDAR\r\n";
        exit;
    }
}
add_action('template_redirect', 'calendar_template_redirect');

function ical_escape($text)
{
    $text = str_replace("\\", "\\\\", $text);
    $text = str_replace("\n", "\\n", $text);
    $text = str_replace("\r", "", $text);
    $text = str_replace(",", "\\,", $text);
    $text = str_replace(";", "\\;", $text);
    return $text;
}
