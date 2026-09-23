<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Shared admin partial for Pro-only screens that Lite registers as upsells.
 * $abst_pro_page is set by the caller in admin/bt-bb-ab-admin.php.
 */
$abst_pro_page = isset( $abst_pro_page ) && is_array( $abst_pro_page ) ? $abst_pro_page : array();
$abst_pro_title = isset( $abst_pro_page['title'] ) ? $abst_pro_page['title'] : '';
$abst_pro_intro = isset( $abst_pro_page['intro'] ) ? $abst_pro_page['intro'] : '';
$abst_pro_points = isset( $abst_pro_page['points'] ) && is_array( $abst_pro_page['points'] ) ? $abst_pro_page['points'] : array();
?>
<div class="wrap abst-pro-feature-page">
    <h1><?php echo esc_html( $abst_pro_title ); ?> <span class="abst-mcp-pro-badge"><?php esc_html_e( 'Pro', 'ab-split-test-lite' ); ?></span></h1>
    <p class="abst-pro-feature-intro"><?php echo esc_html( $abst_pro_intro ); ?></p>
    <?php if ( ! empty( $abst_pro_points ) ) : ?>
    <ul class="abst-pro-feature-points">
        <?php foreach ( $abst_pro_points as $abst_pro_point ) : ?>
        <li><?php echo esc_html( $abst_pro_point ); ?></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    <p style="margin: 25px 0;">
        <a href="https://absplittest.com/repo-up/?utm_source=wporg-lite&utm_medium=plugin&utm_campaign=feature-link" target="_blank" class="button button-primary button-hero"><?php esc_html_e( 'Upgrade to Pro', 'ab-split-test-lite' ); ?></a>
    </p>
    <p class="description"><?php esc_html_e( 'This screen is part of AB Split Test Pro. It is shown here so you know what is available - nothing on this page is active in the Lite version.', 'ab-split-test-lite' ); ?></p>
</div>
<style>
.abst-pro-feature-page .abst-pro-feature-intro { font-size: 15px; max-width: 46em; }
.abst-pro-feature-page .abst-pro-feature-points { list-style: disc; margin: 18px 0 0 20px; max-width: 46em; }
.abst-pro-feature-page .abst-pro-feature-points li { margin: 7px 0; }
.abst-pro-feature-page .abst-mcp-pro-badge {
  background: #2271b1; color: #fff; font-size: 11px; font-weight: 600;
  text-transform: uppercase; letter-spacing: .04em; padding: 3px 9px;
  border-radius: 10px; vertical-align: middle;
}
</style>
