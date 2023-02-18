<?php
/**
 * Plugin Name: Uber Safe Break
 * Description: A plugin that allows registered users to play a game of Uber Safe Break.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 */
// Add a shortcode for the game.
add_shortcode( 'ubersafebreak', 'ubersafebreak_shortcode' );
function ubersafebreak_shortcode() {
    // Verify that the user is logged in.
    if ( ! is_user_logged_in() ) {
        return 'You must be logged in to play the game.';
    }
    // Get the current user's ID.
    $user_id = get_current_user_id();

    // Check how many guesses the user has made this week.
    $guesses = get_user_meta( $user_id, 'ubersafebreak_guesses', true );
    if ( ! $guesses ) {
        $guesses = 0;
    }
    $max_guesses = get_option( 'ubersafebreak_max_guesses', 4 );
    if ( $guesses >= $max_guesses ) {
        return 'Sorry, you have used up all your guesses for this week.';
    }

    // Set the default pin code.
    $pin = get_option( 'ubersafebreak_pin', '123456' );
    // Check if the user has entered a guess.
    $guess = isset( $_POST['ubersafebreak_guess'] ) ? sanitize_text_field( $_POST['ubersafebreak_guess'] ) : '';

    // Check if the guess is correct.
    $message = '';
    if ( $guess ) {
        if ( $guess == $pin ) {
            $custom_message = get_option('ubersafebreak_custom_message', '');
            if (!empty($custom_message)) {
                $message = '<p>' . esc_html($custom_message) . '</p>';
            } else {
                $message = '<p>Well done, mate! You have unlocked the hidden content.</p>';
            }
        } else {
            $message = '<p>Sorry, that is not the correct pin code. Please try again.</p>';
        }
        // Increment the number of guesses for the user.
        update_user_meta( $user_id, 'ubersafebreak_guesses', $guesses + 1 );
    }

    // Generate the HTML for the game.
    $output = '';
    $output .= '<form method="post">';
    $output .= '<label for="ubersafebreak_guess">Enter the 6 digit pin code:</label>';
    $output .= '<input type="number" id="ubersafebreak_guess" name="ubersafebreak_guess" min="000000" max="999999" required>';
    $output .= '<input type="submit" value="Submit">';
    $output .= $message;

    return $output;
}
// Add a settings page for the plugin.
add_action( 'admin_menu', 'ubersafebreak_settings_page' );
function ubersafebreak_settings_page() {
    add_options_page( 'Uber Safe Break Settings', 'Uber Safe Break', 'manage_options', 'ubersafebreak-settings', 'ubersafebreak_settings_page_callback' );
}

function ubersafebreak_settings_page_callback() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You do not have sufficient permissions to access this page.' );
    }

    // Update the settings if the form has been submitted.
    if ( isset( $_POST['ubersafebreak_max_guesses'] ) ) {
        $max_guesses = intval( $_POST['ubersafebreak_max_guesses'] );
        $pin = sanitize_text_field( $_POST['ubersafebreak_pin'] );
        $custom_message = sanitize_text_field( $_POST['ubersafebreak_custom_message'] );
        update_option( 'ubersafebreak_max_guesses', $max_guesses );
        update_option( 'ubersafebreak_pin', $pin );
        update_option( 'ubersafebreak_custom_message', $custom_message );
        echo '<div class="notice notice-success is-dismissible"><p>Settings have been updated.</p></div>';
    }

    // Get the current settings.
    $max_guesses = get_option( 'ubersafebreak_max_guesses', 4 );
    $pin = get_option( 'ubersafebreak_pin', '123456' );
    $custom_message = get_option( 'ubersafebreak_custom_message', '' );
    ?>
    <div class="wrap">
        <h1>Uber Safe Break Settings</h1>
        <form method="post">
            <label for="ubersafebreak_max_guesses">Maximum number of guesses per week:</label>
            <input type="number" id="ubersafebreak_max_guesses" name="ubersafebreak_max_guesses" min="1" value="<?php echo esc_attr( $max_guesses ); ?>" required><br><br>
            <label for="ubersafebreak_pin">Default 6 digit pin code:</label>
            <input type="number" id="ubersafebreak_pin" name="ubersafebreak_pin" min="000000" max="999999" value="<?php echo esc_attr( $pin ); ?>" required><br><br>
            <label for="ubersafebreak_custom_message">Custom success message:</label>
            <textarea id="ubersafebreak_custom_message" name="ubersafebreak_custom_message"><?php echo esc_html( $custom_message ); ?></textarea><br><br>
            <input type="submit" class="button-primary" value="Save Changes">
        </form>
    </div>
    <?php
}
// Add a link to the settings page in the plugin list.
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'ubersafebreak_settings_link' );
function ubersafebreak_settings_link( $links ) {
    $settings_link = '<a href="options-general.php?page=ubersafebreak-settings">' . __( 'Settings' ) . '</a>';
    array_push( $links, $settings_link );
    return $links;
}

 // Enqueue the plugin styles and scripts.
add_action( 'wp_enqueue_scripts', 'ubersafebreak_scripts' );
function ubersafebreak_scripts() {
    wp_enqueue_style( 'ubersafebreak-styles', plugins_url( 'ubersafebreak.css', __FILE__ ) );
    wp_enqueue_script( 'ubersafebreak-script', plugins_url( 'ubersafebreak.js', __FILE__ ), array( 'jquery' ), '1.0', true );
}
// Register the plugin options.
add_action( 'admin_init', 'ubersafebreak_register_settings' );
function ubersafebreak_register_settings() {
    register_setting( 'ubersafebreak_options', 'ubersafebreak_max_guesses', array(
        'type' => 'integer',
        'description' => 'The maximum number of guesses a user can make per week',
        'sanitize_callback' => 'absint',
        'default' => 4,
    ) );
    register_setting( 'ubersafebreak_options', 'ubersafebreak_pin', array(
        'type' => 'string',
        'description' => 'The default 6 digit pin code',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '123456',
    ) );
    register_setting( 'ubersafebreak_options', 'ubersafebreak_custom_message', array(
        'type' => 'string',
        'description' => 'A custom message to show when the user enters the correct pin code',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ) );
}
// Add a database upgrade routine.
add_action( 'plugins_loaded', 'ubersafebreak_upgrade_db' );
function ubersafebreak_upgrade_db() {
    $current_version = get_option( 'ubersafebreak_version', '1.0' );
    if ( version_compare( $current_version, '1.1', '<' ) ) {
        // Add a new option for the custom message.
        add_option( 'ubersafebreak_custom_message', '' );
    }
    update_option( 'ubersafebreak_version', '1.1' );
}
// Add a deactivation routine.
register_deactivation_hook( __FILE__, 'ubersafebreak_deactivate' );
function ubersafebreak_deactivate() {
    // Delete the plugin options.
    delete_option( 'ubersafebreak_max_guesses' );
    delete_option( 'ubersafebreak_pin' );
    delete_option( 'ubersafebreak_custom_message' );
    delete_option( 'ubersafebreak_version' );
    // Delete the user meta.
    $users = get_users();
    foreach ( $users as $user ) {
        delete_user_meta( $user->ID, 'ubersafebreak_guesses' );
    }
}
