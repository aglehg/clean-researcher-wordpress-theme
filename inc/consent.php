<?php
/**
 * Lightweight consent banner for Google Consent Mode v2.
 *
 * This is a minimal replacement path for heavy CMP front-end payloads.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return the current consent cookie value.
 */
function clean_researcher_get_consent_cookie(): string {
    if ( ! isset( $_COOKIE['cr_consent'] ) ) {
        return '';
    }

    $value = sanitize_text_field( wp_unslash( (string) $_COOKIE['cr_consent'] ) );

    return in_array( $value, [ 'granted', 'denied' ], true ) ? $value : '';
}

/**
 * Print default consent state early so Site Kit's gtag reads the state.
 */
function clean_researcher_print_consent_defaults(): void {
    if ( is_admin() ) {
        return;
    }

    $cookie_state = clean_researcher_get_consent_cookie();
    $state        = 'granted' === $cookie_state ? 'granted' : 'denied';
    ?>
    <script id="clean-researcher-consent-default">
      window.dataLayer = window.dataLayer || [];
      window.gtag = window.gtag || function(){ window.dataLayer.push(arguments); };
      window.gtag('consent', 'default', {
        ad_storage: 'denied',
        analytics_storage: '<?php echo esc_js( $state ); ?>',
        ad_user_data: 'denied',
        ad_personalization: 'denied',
        wait_for_update: 500
      });
    </script>
    <?php
}
add_action( 'wp_head', 'clean_researcher_print_consent_defaults', 1 );

/**
 * Print lightweight consent banner markup and script.
 */
function clean_researcher_print_consent_banner(): void {
    if ( is_admin() ) {
        return;
    }

    if ( '' !== clean_researcher_get_consent_cookie() ) {
        return;
    }

  $cookie_attributes = is_ssl() ? '; SameSite=Lax; Secure' : '; SameSite=Lax';
    ?>
    <div id="cr-consent-banner" style="position:fixed;right:1rem;bottom:1rem;z-index:9999;max-width:26rem;background:#111827;color:#fff;padding:1rem;border-radius:.75rem;box-shadow:0 10px 25px rgba(0,0,0,.25);font-size:.875rem;line-height:1.4;">
      <p style="margin:0 0 .75rem;">We use analytics cookies to understand site usage. You can accept or reject tracking.</p>
      <div style="display:flex;gap:.5rem;">
        <button type="button" data-cr-consent="granted" style="appearance:none;border:0;background:#fff;color:#111827;padding:.5rem .75rem;border-radius:.5rem;font-weight:600;cursor:pointer;">Accept</button>
        <button type="button" data-cr-consent="denied" style="appearance:none;border:1px solid rgba(255,255,255,.5);background:transparent;color:#fff;padding:.5rem .75rem;border-radius:.5rem;font-weight:600;cursor:pointer;">Reject</button>
      </div>
    </div>
    <script id="clean-researcher-consent-banner-js">
      (function () {
        var banner = document.getElementById('cr-consent-banner');
        if (!banner) { return; }

        function setCookie(value) {
          var maxAge = 60 * 60 * 24 * 180; // 180 days
          document.cookie = 'cr_consent=' + value + '; path=/; max-age=' + maxAge + '<?php echo esc_js( $cookie_attributes ); ?>';
        }

        function updateConsent(value) {
          var state = value === 'granted' ? 'granted' : 'denied';
          window.dataLayer = window.dataLayer || [];
          window.gtag = window.gtag || function(){ window.dataLayer.push(arguments); };
          window.gtag('consent', 'update', {
            ad_storage: 'denied',
            analytics_storage: state,
            ad_user_data: 'denied',
            ad_personalization: 'denied'
          });
        }

        banner.addEventListener('click', function (event) {
          var button = event.target.closest('[data-cr-consent]');
          if (!button) { return; }

          var value = button.getAttribute('data-cr-consent') || 'denied';
          setCookie(value);
          updateConsent(value);
          banner.remove();
        });
      })();
    </script>
    <?php
}
add_action( 'wp_footer', 'clean_researcher_print_consent_banner', 100 );
