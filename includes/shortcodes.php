<?php
function calendar_display_events($atts)
{
    global $wpdb;
    $table_name = $wpdb->prefix . "simple_calendar";

    $category_filter = isset($atts['category']) ? sanitize_text_field($atts['category']) : '';

    $query = "SELECT * FROM $table_name WHERE published = 1";
    if ($category_filter) {
        $query .= $wpdb->prepare(" AND category = %s", $category_filter);
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
            <div>Category</div>
            <div>Start Time</div>
            <div>End Time</div>
            <div>Location</div>
        </div>
        <?php foreach ($events as $event) : ?>
            <div class="sc-event-row">
                <div><?php echo esc_html($event->title); ?></div>
                <div><?php echo esc_html($event->category); ?></div>
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
