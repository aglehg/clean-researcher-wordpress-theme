<?php
/**
 * User profile: Twitter/X handle field for per-author twitter:creator meta tag.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render the Twitter/X handle field on the user profile edit screen.
 */
function clean_researcher_user_twitter_field( WP_User $user ): void {
    $handle = (string) get_user_meta( $user->ID, 'clean_researcher_twitter_handle', true );
    ?>
    <h3><?php esc_html_e( 'Social Profiles', 'clean-researcher' ); ?></h3>
    <table class="form-table" role="presentation">
        <tr>
            <th><label for="clean_researcher_twitter_handle"><?php esc_html_e( 'Twitter / X Handle', 'clean-researcher' ); ?></label></th>
            <td>
                <input type="text"
                       name="clean_researcher_twitter_handle"
                       id="clean_researcher_twitter_handle"
                       value="<?php echo esc_attr( $handle ); ?>"
                       class="regular-text"
                       placeholder="@username" />
                <p class="description"><?php esc_html_e( 'Used for twitter:creator meta tag on posts you author. Include or omit the @ sign.', 'clean-researcher' ); ?></p>
            </td>
        </tr>
    </table>
    <?php
}
add_action( 'show_user_profile', 'clean_researcher_user_twitter_field' );
add_action( 'edit_user_profile', 'clean_researcher_user_twitter_field' );

/**
 * Save the Twitter/X handle when the user profile form is submitted.
 */
function clean_researcher_save_user_twitter_field( int $user_id ): void {
    if ( ! current_user_can( 'edit_user', $user_id ) ) {
        return;
    }

    if ( ! isset( $_POST['clean_researcher_twitter_handle'] ) ) {
        return;
    }

    $handle = sanitize_text_field( wp_unslash( (string) $_POST['clean_researcher_twitter_handle'] ) );
    $handle = ltrim( preg_replace( '/\s+/', '', $handle ), '@' );

    if ( '' === $handle ) {
        delete_user_meta( $user_id, 'clean_researcher_twitter_handle' );
    } elseif ( preg_match( '/^[A-Za-z0-9_]{1,15}$/', $handle ) ) {
        update_user_meta( $user_id, 'clean_researcher_twitter_handle', $handle );
    }
}
add_action( 'personal_options_update', 'clean_researcher_save_user_twitter_field' );
add_action( 'edit_user_profile_update', 'clean_researcher_save_user_twitter_field' );
