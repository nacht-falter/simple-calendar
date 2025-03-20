<?php
function calendar_display_events($atts)
{
    global $wpdb;
    $table_name = $wpdb->prefix . "simple_calendar";

    $organizer_filter = isset($atts['organizer']) ? sanitize_text_field($atts['organizer']) : '';

    $query = "SELECT * FROM $table_name WHERE published = 1";
    if ($organizer_filter) {
        $query .= $wpdb->prepare(" AND organizer = %s", $organizer_filter);
    }
    $query .= " ORDER BY start_time ASC";

    $events = $wpdb->get_results($query);

    if (!$events) {
        return "<p>No events found.</p>";
    }

    ob_start();
    ?>
    <div class="sc-event-list">
        <div class="sc-event-header">
            <div>Title</div>
            <div>Organizer</div>
            <div>Start Time</div>
            <div>End Time</div>
            <div>Location</div>
        </div>
        <?php foreach ($events as $event) : ?>
            <div class="sc-event-row">
                <div><?php echo esc_html($event->title); ?></div>
                <div><?php echo esc_html($event->organizer); ?></div>
                <div><?php echo date('M d, Y H:i', strtotime($event->start_time)); ?></div>
                <div><?php echo date('M d, Y H:i', strtotime($event->end_time)); ?></div>
                <div><?php echo esc_html($event->location); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('event_list', 'calendar_display_events');
