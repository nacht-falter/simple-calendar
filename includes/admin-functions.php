<?php
function calendar_form_handler()
{
    if (!isset($_POST['calendar_nonce']) || !wp_verify_nonce($_POST['calendar_nonce'], 'calendar_action')) {
        wp_die('Security check failed');
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . "simple_calendar";
    
    $title = sanitize_text_field($_POST['title']);
    $all_day = isset($_POST['all_day']) ? 1 : 0;
    $start_time = sanitize_text_field($_POST['start_time']);
    $end_time = sanitize_text_field($_POST['end_time']);
    $location = sanitize_text_field($_POST['location']);
    $description = sanitize_textarea_field($_POST['description']);
    $category = sanitize_text_field($_POST['category']);
    $url = sanitize_text_field($_POST['url']);
    $published = isset($_POST['published']) ? 1 : 0;

    // Modify datetime format if all-day is checked
    if ($all_day) {
        $start_time = date('Y-m-d 00:00:00', strtotime($start_time));
        $end_time = date('Y-m-d 23:59:59', strtotime($end_time));
    }
    
    if (isset($_POST['event_id']) && $_POST['event_id'] != '') {
        // Retrieve current UUID
        $event_id = intval($_POST['event_id']);
        $existing_event = $wpdb->get_row($wpdb->prepare("SELECT uuid FROM $table_name WHERE id = %d", $event_id));

        if ($existing_event) {
            $uuid = $existing_event->uuid; // Preserve existing UUID
        }

        $wpdb->update(
            $table_name,
            compact('title', 'all_day', 'start_time', 'end_time', 'description', 'location', 'url', 'category', 'published', 'uuid'),
            ['id' => $event_id]
        );
        $message = 'Event updated successfully';
    } else {
        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        $wpdb->insert($table_name, compact('title', 'all_day', 'start_time', 'end_time', 'description', 'location', 'url', 'category', 'published', 'uuid'));
        $message = 'Event created successfully';
    }

    wp_redirect(admin_url('admin.php?page=simple-calendar&message=' . urlencode($message)));
    exit;
}
add_action('admin_post_save_calendar_event', 'calendar_form_handler');

function calendar_delete_handler()
{
    if (!isset($_GET['calendar_nonce']) || !wp_verify_nonce($_GET['calendar_nonce'], 'calendar_delete')) {
        wp_die('Security check failed');
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . "simple_calendar";
    
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        $wpdb->delete($table_name, ['id' => intval($_GET['delete'])]);
        wp_redirect(admin_url('admin.php?page=simple-calendar&message=Event+deleted+successfully'));
        exit;
    }
}
add_action('admin_post_delete_calendar_event', 'calendar_delete_handler');

function calendar_admin_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "simple_calendar";
    
    if (!current_user_can('manage_calendars')) {
        wp_die(__('You do not have sufficient permissions.'));
    }
    
    $event = null;
    if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
        $event = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_GET['edit'])));
    }
    
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    ?>
    <div class="wrap">
        <h2>Manage Events</h2>
        
        <?php 
        if (isset($_GET['message'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($_GET['message']) . '</p></div>';
        }
        ?>
        
        <?php if ($action === 'create' || $event) : ?>
            <h3><?php echo $event ? 'Edit Event' : 'Add New Event'; ?></h3>
            <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="sc-event-form">
                <input type="hidden" name="action" value="save_calendar_event">
                <?php wp_nonce_field('calendar_action', 'calendar_nonce'); ?>
                
                <?php if ($event) : ?>
                    <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>">
                <?php endif; ?>
                
                <label for="title">Event Title</label>
                <input type="text" name="title" id="title" placeholder="Event Title" value="<?php echo esc_attr($event ? $event->title : ''); ?>" required>
                <small>Please enter a descriptive title for the event</small>

                <?php
                $is_all_day = $event && $event->all_day;

                $current_date = date('Y-m-d\TH:i');  // For non-all-day events
                $current_datetime = date('Y-m-d') . 'T00:00';  // Set time to 00:00

                $start_time_value = !empty($event->start_time) && strtotime($event->start_time) ? date('Y-m-d', strtotime($event->start_time)) : $current_date;
                $end_time_value = !empty($event->end_time) && strtotime($event->end_time) ? date('Y-m-d', strtotime($event->end_time)) : $current_date;

                if (!$is_all_day) {
                    $start_time_value = !empty($event->start_time) && strtotime($event->start_time) ? date('Y-m-d\TH:i', strtotime($event->start_time)) : $current_datetime;
                    $end_time_value = !empty($event->end_time) && strtotime($event->end_time) ? date('Y-m-d\TH:i', strtotime($event->end_time)) : $current_datetime;
                }

                $start_time_input_type = $is_all_day ? 'date' : 'datetime-local';
                $end_time_input_type = $is_all_day ? 'date' : 'datetime-local';
                ?>

                <label for="all_day">
                    <input type="checkbox" name="all_day" id="all_day" <?php echo ($event && !$event->all_day) ? '' : 'checked'; ?> onchange="toggleTimeInputs()"> All Day Event<br>
                    <small>Please leave this checked unless you need to set specific times for the event</small>
                </label>

                <label for="start_time">Start Time</label>
                <input type="<?php echo $start_time_input_type; ?>" name="start_time" id="start_time" value="<?php echo esc_attr($start_time_value); ?>" required>

                <label for="end_time">End Time</label>
                <input type="<?php echo $end_time_input_type; ?>" name="end_time" id="end_time" value="<?php echo esc_attr($end_time_value); ?>" required>


                <label for="location">Location</label>
                <input type="text" name="location" id="location" placeholder="Course Location" value="<?php echo esc_attr($event ? $event->location : ''); ?>">
                
                <label for="description">Event Description</label>
                <textarea name="description" id="description" placeholder="Instructor: Name"><?php echo esc_textarea($event ? $event->description : ''); ?></textarea>
                <small>Please include the instructor(s) in the description like this: "Instructor(s): Name(s)"</small>
                
                <label for="url">URL</label>
                <input type="text" name="url" id="url" placeholder="Event Website" value="<?php echo esc_attr($event ? $event->url : ''); ?>">
                
                <label for="category">Category</label>
                <select name="category" id="category" required>
                    <?php
                    foreach ($GLOBALS['CATEGORIES'] as $value => $display) {
                        $selected = $event && $value === $event->category ? 'selected' : '';
                        echo "<option value=\"{$value}\" {$selected}>{$display}</option>";
                    }
                    ?>
                </select>
                
                <label for="published">
                    <input type="checkbox" name="published" id="published" <?php echo $event && $event->published ? 'checked' : ''; ?>> Published
                </label>
                
                <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                    <button type="submit" class="button button-primary"><?php echo $event ? 'Update Event' : 'Save Event'; ?></button>
                    <a href="?page=simple-calendar" class="button">Cancel</a>
                </div>
            </form>
            
        <?php else: ?>
            <a href="?page=simple-calendar&action=create" class="page-title-action">Add New Event</a>
        
            <h3>Existing Events</h3>
            <table class="sc-event-table widefat striped">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Description</th>
                        <th>URL</th>
                        <th>Published?</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $events = $wpdb->get_results("SELECT * FROM $table_name ORDER BY start_time DESC");
                foreach ($events as $event) { 
                    $delete_url = wp_nonce_url(
                        admin_url('admin-post.php?action=delete_calendar_event&delete=' . $event->id),
                        'calendar_delete',
                        'calendar_nonce'
                    );
                    ?>
                    <tr>
                        <td><?php echo esc_html($event->title); ?></td>
                        <td>
                            <?php 
                            if ($event->all_day) {
                                echo esc_html(date('Y-m-d', strtotime($event->start_time))) . ' (all day)';
                            } else {
                                echo esc_html($event->start_time);
                            }
                            ?>
                        </td>
                        <td>
                            <?php 
                            if ($event->all_day) {
                                echo esc_html(date('Y-m-d', strtotime($event->end_time))) . ' (all day)';
                            } else {
                                echo esc_html($event->end_time);
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html($GLOBALS['CATEGORIES'][$event->category] ?? $event->category); ?></td>
                        <td><?php echo esc_html($event->location); ?></td>
                        <td><?php echo esc_html($event->description); ?></td>
                        <td><?php echo esc_html($event->url); ?></td>
                        <td><?php echo esc_html($event->published ? 'Yes' : 'No'); ?></td>
                        <td>
                            <a href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('Are you sure you want to delete this event?');"><span class="dashicons dashicons-trash"></span>Delete</a> |
                            <a href="?page=simple-calendar&edit=<?php echo $event->id; ?>"><span class="dashicons dashicons-edit"></span>Edit</a>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}
