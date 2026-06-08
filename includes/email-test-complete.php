<?php
/**
 * "Test complete" notification email.
 *
 * Replaces the plain-text three-liner the autocompleter used to send with
 * a styled HTML email that mirrors the in-app report: hero, summary stats,
 * variation table, projected annual impact, and a link back to the full report.
 *
 * Public entrypoint: abst_send_test_complete_email().
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'BT_AB_TEST_WL_NAME' ) ) {
    define( 'BT_AB_TEST_WL_NAME', defined( 'BT_AB_TEST_WL_ABTEST' ) ? BT_AB_TEST_WL_ABTEST : 'AB Split Test' );
}

/**
 * Build, format and send the "test complete" email.
 *
 * @param mixed  $notify_to                 String or array of recipient addresses.
 * @param WP_Post $experiment               The completed test post.
 * @param array  $observations              Analyzed observations (includes bt_bb_ab_stats).
 * @param bool   $conversion_use_order_value Revenue mode flag.
 * @return bool wp_mail() result.
 */
function abst_send_test_complete_email( $notify_to, $experiment, $observations, $conversion_use_order_value ) {
    $data = abst_build_test_complete_email_data( $experiment, $observations, (bool) $conversion_use_order_value );
    if ( empty( $data ) ) {
        return false;
    }

    $subject = BT_AB_TEST_WL_NAME . ': ' . $experiment->post_title . ', Complete.';
    $subject = apply_filters( 'abst_email_complete_subject', $subject, $data, $experiment );

    $html = abst_render_test_complete_email_html( $data );
    $html = apply_filters( 'abst_email_complete_html', $html, $data, $experiment );

    $text = abst_render_test_complete_email_text( $data );
    $text = apply_filters( 'abst_email_complete_text', $text, $data, $experiment );

    $headers = array( 'Content-Type: text/html; charset=UTF-8' );
    $headers = apply_filters( 'abst_email_complete_headers', $headers, $data, $experiment );

    // Attach plain-text alternative once PHPMailer is initialised.
    $attach_alt_body = function( $phpmailer ) use ( $text ) {
        $phpmailer->AltBody = $text;
    };
    add_action( 'phpmailer_init', $attach_alt_body );
    $sent = wp_mail( $notify_to, $subject, $html, $headers );
    remove_action( 'phpmailer_init', $attach_alt_body );

    return $sent;
}

/**
 * Pull everything the renderer needs out of the test + observations.
 *
 * Returns an array shaped for the renderer; null if the data is too
 * incomplete to email about.
 */
function abst_build_test_complete_email_data( $experiment, $observations, $is_revenue ) {
    if ( ! is_object( $experiment ) || empty( $observations['bt_bb_ab_stats']['best'] ) ) {
        return null;
    }

    $test_id        = (int) $experiment->ID;
    $winner_key     = (string) $observations['bt_bb_ab_stats']['best'];
    $winner_conf    = isset( $observations['bt_bb_ab_stats']['probability'] )
        ? (float) $observations['bt_bb_ab_stats']['probability']
        : 0.0;
    $variation_meta = get_post_meta( $test_id, 'variation_meta', true );
    if ( ! is_array( $variation_meta ) ) {
        $variation_meta = array();
    }

    $test_age_days = max( 1, (int) ( ( time() - get_post_time( 'U', true, $experiment ) ) / 86400 ) );

    // Build a per-variation list with the fields the renderer cares about.
    $variations = array();
    foreach ( $observations as $key => $row ) {
        if ( $key === 'bt_bb_ab_stats' || ! is_array( $row ) ) {
            continue;
        }
        $visits      = isset( $row['visit'] ) ? (int) $row['visit'] : 0;
        $conversions = isset( $row['conversion'] ) ? (float) $row['conversion'] : 0.0;
        // For revenue tests `rate` is stored as RPV * 100; the report divides by 100
        // (see bt-bb-ab.php:4047). For conv-rate tests we recompute from conversions/visits.
        if ( $is_revenue ) {
            $rate = isset( $row['rate'] ) ? (float) $row['rate'] / 100.0 : 0.0;
        } else {
            $rate = ( $visits > 0 ) ? ( $conversions / $visits ) : 0.0;
        }
        $variations[ (string) $key ] = array(
            'key'         => (string) $key,
            'label'       => abst_get_variation_label( $key, $variation_meta ),
            'visits'      => $visits,
            'conversions' => $conversions,
            'rate'        => $rate, // fraction (0..1) for conv-rate tests; dollars-per-visit for revenue
            'confidence'  => isset( $row['probability'] ) ? (float) $row['probability'] : 0.0,
        );
    }

    if ( empty( $variations ) ) {
        return null;
    }

    // Identify the control via the same helper the report uses.
    global $btab;
    $control_key = null;
    if ( is_object( $btab ) && method_exists( $btab, 'identify_control_variation' ) ) {
        $control_key = $btab->identify_control_variation( $variations, $experiment );
    }
    if ( ! $control_key || ! isset( $variations[ $control_key ] ) ) {
        // Fall back to the first variation.
        $control_key = array_key_first( $variations );
    }
    $control_rate = $variations[ $control_key ]['rate'];

    // Uplift vs control per variation (as percentage points, e.g. +38.9 or -14.5).
    // Compare via the row's own 'key' field, not the array key: PHP coerces
    // numeric-string array keys to ints, breaking strict comparison against
    // $control_key (e.g. full_page tests where keys are page IDs).
    foreach ( $variations as &$v ) {
        if ( (string) $v['key'] === (string) $control_key || $control_rate <= 0 ) {
            $v['uplift_vs_control'] = null;
        } else {
            $v['uplift_vs_control'] = ( ( $v['rate'] - $control_rate ) / $control_rate ) * 100.0;
        }
    }
    unset( $v );

    // Totals.
    $total_visits      = 0;
    $total_conversions = 0.0;
    foreach ( $variations as $v ) {
        $total_visits      += $v['visits'];
        $total_conversions += $v['conversions'];
    }
    $overall_rate = 0.0;
    if ( $total_visits > 0 ) {
        if ( $is_revenue ) {
            $overall_rate = $total_conversions / $total_visits;
        } else {
            $overall_rate = $total_conversions / $total_visits;
        }
    }

    // Winner uplift and projected annual impact (mirrors report-template.php:304-322).
    $winner_uplift = 0.0;
    $winner_is_control = ( (string) $winner_key === (string) $control_key );
    if ( ! $winner_is_control && $control_rate > 0 && isset( $variations[ $winner_key ] ) ) {
        $winner_uplift = ( ( $variations[ $winner_key ]['rate'] - $control_rate ) / $control_rate ) * 100.0;
    }

    $impact_kind  = 'none'; // 'extra' | 'avoided' | 'none'
    $impact_value = 0.0;
    $runner_up_key = null;

    if ( ! $winner_is_control && $winner_uplift > 0 && $total_visits > 0 ) {
        $impact_kind  = 'extra';
        $impact_value = abst_compute_projected_annual_impact(
            $winner_uplift,
            $total_visits,
            $total_conversions,
            $test_age_days,
            $is_revenue
        );
    } elseif ( $winner_is_control && $total_visits > 0 ) {
        // Avoided loss: pick the runner-up (highest-rate non-control variant).
        $runner_up_rate = -INF;
        foreach ( $variations as $v ) {
            if ( (string) $v['key'] === (string) $control_key ) continue;
            if ( $v['rate'] > $runner_up_rate ) {
                $runner_up_rate = $v['rate'];
                $runner_up_key  = $v['key'];
            }
        }
        if ( $runner_up_key !== null && $control_rate > 0 && $runner_up_rate < $control_rate ) {
            $loss_pct = ( ( $control_rate - $runner_up_rate ) / $control_rate ) * 100.0;
            $impact_kind  = 'avoided';
            $impact_value = abst_compute_projected_annual_impact(
                $loss_pct,
                $total_visits,
                $total_conversions,
                $test_age_days,
                $is_revenue
            );
        }
    }

    // Currency symbol for revenue tests.
    $currency_symbol = '$';
    if ( $is_revenue && is_object( $btab ) && method_exists( $btab, 'value_currency_symbol' ) ) {
        $currency_symbol = $btab->value_currency_symbol();
    } elseif ( $is_revenue && function_exists( 'ab_get_admin_setting' ) ) {
        $saved_currency_symbol = trim( (string) ab_get_admin_setting( 'abst_revenue_currency_symbol' ) );
        if ( $saved_currency_symbol !== '' ) {
            $currency_symbol = html_entity_decode( $saved_currency_symbol, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        }
    }

    // Report and edit URLs.
    $report_url = null;
    global $abst_public_reports;
    if ( class_exists( 'ABST_Public_Reports' ) && isset( $abst_public_reports ) && is_object( $abst_public_reports ) ) {
        if ( method_exists( $abst_public_reports, 'is_shareable_reports_enabled' ) && $abst_public_reports->is_shareable_reports_enabled() ) {
            $share = $abst_public_reports->get_or_create_share_link( $test_id );
            $report_url = isset( $share['url'] ) ? $share['url'] : null;
        }
    }
    $edit_url = admin_url( 'post.php?post=' . $test_id . '&action=edit' );

    $settings_url = admin_url( 'edit.php?post_type=bt_experiments&page=bt_bb_ab_test' );

    return array(
        'test_id'           => $test_id,
        'test_name'         => $experiment->post_title,
        'is_revenue'        => $is_revenue,
        'currency_symbol'   => $currency_symbol,
        'test_age_days'     => $test_age_days,
        'total_visits'      => $total_visits,
        'total_conversions' => $total_conversions,
        'overall_rate'      => $overall_rate,
        'variations'        => $variations,
        'control_key'       => $control_key,
        'winner_key'        => $winner_key,
        'winner_label'      => $variations[ $winner_key ]['label'] ?? abst_get_variation_label( $winner_key, $variation_meta ),
        'winner_conf'       => $winner_conf,
        'winner_is_control' => $winner_is_control,
        'winner_uplift'     => $winner_uplift,
        'impact_kind'       => $impact_kind,
        'impact_value'      => $impact_value,
        'runner_up_key'     => $runner_up_key,
        'runner_up_label'   => $runner_up_key ? $variations[ $runner_up_key ]['label'] : null,
        'report_url'        => $report_url,
        'edit_url'          => $edit_url,
        'settings_url'      => $settings_url,
        'wl_name'           => BT_AB_TEST_WL_NAME,
    );
}

/**
 * Project a percentage uplift forward to a yearly figure using the test's
 * observed daily rate. Matches modules/public-reports/templates/report-template.php:304-322.
 *
 * @param float $pct          Percentage (e.g. 38.9 for +38.9%, or 14.5 for a -14.5% loss).
 * @param int   $total_visits Total visits across all variations.
 * @param float $total_value  Total conversions, or total revenue for revenue tests.
 * @param int   $test_age     Days the test has been running (must be >= 1).
 * @param bool  $is_revenue   True if `$total_value` is revenue (dollars), not conversion count.
 * @return float Conversions/year or revenue/year, depending on $is_revenue.
 */
function abst_compute_projected_annual_impact( $pct, $total_visits, $total_value, $test_age, $is_revenue ) {
    $test_age = max( 1, (int) $test_age );
    if ( $is_revenue ) {
        $daily_visits   = $total_visits / $test_age;
        $annual_visits  = $daily_visits * 365;
        $avg_per_visit  = $total_visits > 0 ? ( $total_value / $total_visits ) : 0.0;
        $baseline       = $annual_visits * $avg_per_visit;
        return $baseline * ( $pct / 100.0 );
    }
    $daily_conversions  = $total_value / $test_age;
    $annual_conversions = $daily_conversions * 365;
    return round( $annual_conversions * ( $pct / 100.0 ) );
}

/**
 * Render the HTML body. Inline CSS only - email clients strip <style>.
 */
function abst_render_test_complete_email_html( $d ) {
    $font   = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif";
    $green  = '#10b981';
    $red    = '#ef4444';
    $text   = '#1f2937';
    $muted  = '#6b7280';
    $border = '#e5e7eb';
    $bg     = '#f9fafb';

    $esc = function( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); };

    // Format helpers.
    $fmt_rate_or_currency = function( $value ) use ( $d ) {
        if ( $d['is_revenue'] ) {
            return $d['currency_symbol'] . number_format( $value, 2 );
        }
        return number_format( $value * 100, 2 ) . '%';
    };
    $fmt_conv_or_rev = function( $value ) use ( $d ) {
        if ( $d['is_revenue'] ) {
            return $d['currency_symbol'] . number_format( $value, 0 );
        }
        return number_format( $value, 0 );
    };
    $fmt_impact = function( $value ) use ( $d ) {
        if ( $d['is_revenue'] ) {
            return $d['currency_symbol'] . number_format( $value, 0 );
        }
        return number_format( $value, 0 ) . ' conversions';
    };

    // ---- Hero lines ----
    $hero_lines = array();
    $hero_lines[] = sprintf(
        '<span style="font-size:16px;color:%s;">%s won with <strong>%s%% confidence</strong></span>',
        $esc( $text ),
        $esc( $d['winner_label'] ),
        $esc( number_format( $d['winner_conf'], 0 ) )
    );

    if ( $d['winner_is_control'] ) {
        $hero_lines[] = sprintf(
            '<span style="color:%s;">&#128737;&#65039; Control beat all variants &mdash; keep it</span>',
            $esc( $text )
        );
    } else {
        $label = $d['is_revenue'] ? 'revenue per visit' : 'conversion rate';
        $hero_lines[] = sprintf(
            '<span style="color:%s;">&#128200; <strong style="color:%s;">+%s%%</strong> %s vs. control</span>',
            $esc( $text ),
            $esc( $green ),
            $esc( number_format( $d['winner_uplift'], 1 ) ),
            $esc( $label )
        );
    }

    if ( $d['impact_kind'] === 'extra' && $d['impact_value'] > 0 ) {
        $hero_lines[] = sprintf(
            '<span style="color:%s;">&#128176; <strong>Extra %s per year</strong></span>',
            $esc( $text ),
            $esc( $fmt_impact( $d['impact_value'] ) )
        );
    } elseif ( $d['impact_kind'] === 'avoided' && $d['impact_value'] > 0 ) {
        $runner = $d['runner_up_label'] ? ' (' . $esc( $d['runner_up_label'] ) . ')' : '';
        $hero_lines[] = sprintf(
            '<span style="color:%s;">&#128737;&#65039; Avoided losing <strong>%s per year</strong> to the next best variant%s</span>',
            $esc( $text ),
            $esc( $fmt_impact( $d['impact_value'] ) ),
            $runner
        );
    }

    // ---- Summary cards ----
    $card1_label = 'Visitors';
    $card1_value = number_format( $d['total_visits'] );
    $card2_label = $d['is_revenue'] ? 'Revenue' : 'Conversions';
    $card2_value = $fmt_conv_or_rev( $d['total_conversions'] );
    $card3_label = $d['is_revenue'] ? 'Revenue / visit' : 'Conv. rate';
    $card3_value = $fmt_rate_or_currency( $d['overall_rate'] );
    $card4_label = 'Duration';
    $card4_value = $d['test_age_days'] . ( $d['test_age_days'] === 1 ? ' day' : ' days' );

    $card_cell = function( $label, $value ) use ( $muted, $text, $border, $esc ) {
        return sprintf(
            '<td align="center" valign="middle" style="padding:16px 8px;border:1px solid %s;border-radius:8px;background:#ffffff;width:25%%;">'
            . '<div style="font-size:11px;color:%s;text-transform:uppercase;letter-spacing:0.05em;font-weight:600;">%s</div>'
            . '<div style="font-size:22px;color:%s;font-weight:700;margin-top:6px;line-height:1.2;">%s</div>'
            . '</td>',
            $esc( $border ),
            $esc( $muted ),
            $esc( $label ),
            $esc( $text ),
            $esc( $value )
        );
    };

    $summary_table = '<table role="presentation" cellpadding="0" cellspacing="6" border="0" width="100%" style="border-collapse:separate;">'
        . '<tr>'
        . $card_cell( $card1_label, $card1_value )
        . $card_cell( $card2_label, $card2_value )
        . $card_cell( $card3_label, $card3_value )
        . $card_cell( $card4_label, $card4_value )
        . '</tr></table>';

    // ---- Variation table ----
    $rate_header = $d['is_revenue'] ? 'RPV' : 'Rate';
    $value_header = $d['is_revenue'] ? 'Revenue' : 'Conv.';
    $thead = sprintf(
        '<thead><tr style="background:%s;">'
        . '<th align="left"  style="padding:10px 12px;font-size:11px;color:%s;text-transform:uppercase;letter-spacing:0.04em;border-bottom:1px solid %s;">Variation</th>'
        . '<th align="right" style="padding:10px 12px;font-size:11px;color:%s;text-transform:uppercase;letter-spacing:0.04em;border-bottom:1px solid %s;">Visitors</th>'
        . '<th align="right" style="padding:10px 12px;font-size:11px;color:%s;text-transform:uppercase;letter-spacing:0.04em;border-bottom:1px solid %s;">%s</th>'
        . '<th align="right" style="padding:10px 12px;font-size:11px;color:%s;text-transform:uppercase;letter-spacing:0.04em;border-bottom:1px solid %s;">%s</th>'
        . '<th align="right" style="padding:10px 12px;font-size:11px;color:%s;text-transform:uppercase;letter-spacing:0.04em;border-bottom:1px solid %s;">vs. Ctrl</th>'
        . '<th align="right" style="padding:10px 12px;font-size:11px;color:%s;text-transform:uppercase;letter-spacing:0.04em;border-bottom:1px solid %s;">Conf.</th>'
        . '</tr></thead>',
        $esc( $bg ),
        $esc( $muted ), $esc( $border ),
        $esc( $muted ), $esc( $border ),
        $esc( $muted ), $esc( $border ), $esc( $value_header ),
        $esc( $muted ), $esc( $border ), $esc( $rate_header ),
        $esc( $muted ), $esc( $border ),
        $esc( $muted ), $esc( $border )
    );

    // Sort: winner first, then by rate desc.
    uasort( $d['variations'], function( $a, $b ) use ( $d ) {
        if ( $a['key'] === $d['winner_key'] ) return -1;
        if ( $b['key'] === $d['winner_key'] ) return  1;
        if ( $a['rate'] === $b['rate'] ) return 0;
        return ( $a['rate'] < $b['rate'] ) ? 1 : -1;
    });

    $rows_html = '';
    foreach ( $d['variations'] as $v ) {
        $is_winner  = ( (string) $v['key'] === (string) $d['winner_key'] );
        $is_control = ( (string) $v['key'] === (string) $d['control_key'] );

        $badges = '';
        if ( $is_control ) {
            $badges .= '<span style="display:inline-block;background:#eef2ff;color:#4338ca;font-size:10px;font-weight:600;padding:2px 8px;border-radius:10px;margin-left:6px;">CONTROL</span>';
        }
        if ( $is_winner ) {
            $badges .= '<span style="display:inline-block;background:#dcfce7;color:#065f46;font-size:10px;font-weight:600;padding:2px 8px;border-radius:10px;margin-left:6px;">WINNER</span>';
        }

        $uplift_cell = '<span style="color:' . $esc( $muted ) . ';">&mdash;</span>';
        if ( $v['uplift_vs_control'] !== null ) {
            $up      = (float) $v['uplift_vs_control'];
            $color   = ( $up >= 0 ) ? $green : $red;
            $sign    = ( $up >= 0 ) ? '+' : '';
            $uplift_cell = '<span style="color:' . $esc( $color ) . ';font-weight:600;">' . $sign . number_format( $up, 1 ) . '%</span>';
        }

        $conf_cell = number_format( $v['confidence'], 0 ) . '%';
        if ( $v['confidence'] >= 95 ) {
            $conf_cell = '<span style="color:' . $esc( $green ) . ';font-weight:600;">' . $conf_cell . '</span>';
        }

        $row_bg = $is_winner ? '#f0fdf4' : '#ffffff';

        $rows_html .= sprintf(
            '<tr style="background:%s;">'
            . '<td style="padding:12px;border-bottom:1px solid %s;font-size:14px;color:%s;">%s%s</td>'
            . '<td align="right" style="padding:12px;border-bottom:1px solid %s;font-size:14px;color:%s;">%s</td>'
            . '<td align="right" style="padding:12px;border-bottom:1px solid %s;font-size:14px;color:%s;">%s</td>'
            . '<td align="right" style="padding:12px;border-bottom:1px solid %s;font-size:14px;color:%s;">%s</td>'
            . '<td align="right" style="padding:12px;border-bottom:1px solid %s;font-size:14px;">%s</td>'
            . '<td align="right" style="padding:12px;border-bottom:1px solid %s;font-size:14px;color:%s;">%s</td>'
            . '</tr>',
            $esc( $row_bg ),
            $esc( $border ), $esc( $text ), $esc( $v['label'] ), $badges,
            $esc( $border ), $esc( $text ), $esc( number_format( $v['visits'] ) ),
            $esc( $border ), $esc( $text ), $esc( $fmt_conv_or_rev( $v['conversions'] ) ),
            $esc( $border ), $esc( $text ), $esc( $fmt_rate_or_currency( $v['rate'] ) ),
            $esc( $border ), $uplift_cell,
            $esc( $border ), $esc( $text ), $conf_cell
        );
    }

    $variation_table = '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid ' . $esc( $border ) . ';border-radius:8px;border-collapse:separate;overflow:hidden;">' . $thead . '<tbody>' . $rows_html . '</tbody></table>';

    // ---- CTA ----
    $cta_block = '';
    if ( ! empty( $d['report_url'] ) ) {
        $cta_block = sprintf(
            '<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center"><tr><td align="center" style="border-radius:6px;background:%s;"><a href="%s" style="display:inline-block;padding:12px 24px;color:#ffffff;font-weight:600;font-size:15px;text-decoration:none;border-radius:6px;">View full report</a></td></tr></table>',
            $esc( $green ),
            $esc( $d['report_url'] )
        );
    }
    $edit_link = sprintf(
        '<p style="margin:16px 0 0;text-align:center;font-size:13px;"><a href="%s" style="color:%s;text-decoration:none;">Edit this test</a></p>',
        $esc( $d['edit_url'] ),
        $esc( $muted )
    );

    // ---- Assemble ----
    $html  = '<!DOCTYPE html><html><body style="margin:0;padding:0;background:' . $esc( $bg ) . ';font-family:' . $font . ';color:' . $esc( $text ) . ';">';
    $html .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:' . $esc( $bg ) . ';"><tr><td align="center" style="padding:24px 12px;">';
    $html .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid ' . $esc( $border ) . ';">';

    // Header band
    $html .= '<tr><td style="background:' . $esc( $green ) . ';padding:14px 24px;">';
    $html .= '<div style="color:#ffffff;font-weight:700;font-size:14px;letter-spacing:0.06em;text-transform:uppercase;">' . $esc( $d['wl_name'] ) . '</div>';
    $html .= '</td></tr>';

    // Hero
    $html .= '<tr><td style="padding:28px 24px 8px;">';
    $html .= '<h1 style="margin:0 0 8px;font-size:22px;font-weight:700;color:' . $esc( $text ) . ';line-height:1.3;">&#127881; "' . $esc( $d['test_name'] ) . '" has a winner</h1>';
    foreach ( $hero_lines as $line ) {
        $html .= '<p style="margin:8px 0 0;font-size:15px;line-height:1.5;">' . $line . '</p>';
    }
    $html .= '</td></tr>';

    // Stats
    $html .= '<tr><td style="padding:20px 18px 12px;">' . $summary_table . '</td></tr>';

    // Variation table
    $html .= '<tr><td style="padding:8px 24px 24px;">';
    $html .= '<h2 style="margin:8px 0 12px;font-size:16px;font-weight:600;color:' . $esc( $text ) . ';">Variation Performance</h2>';
    $html .= $variation_table;
    $html .= '</td></tr>';

    // CTA
    $html .= '<tr><td style="padding:8px 24px 28px;text-align:center;">' . $cta_block . $edit_link . '</td></tr>';

    // Footer
    $html .= '<tr><td style="padding:16px 24px;background:' . $esc( $bg ) . ';border-top:1px solid ' . $esc( $border ) . ';font-size:12px;color:' . $esc( $muted ) . ';">';
    $html .= 'You received this because autocomplete found a winner for this test. ';
    $html .= '<a href="' . $esc( $d['settings_url'] ) . '" style="color:' . $esc( $muted ) . ';">Manage notification settings</a>.';
    $html .= '</td></tr>';

    $html .= '</table>';
    $html .= '</td></tr></table>';
    $html .= '</body></html>';

    return $html;
}

/**
 * Plain-text fallback for clients that strip HTML.
 */
function abst_render_test_complete_email_text( $d ) {
    $lines = array();
    $lines[] = $d['wl_name'] . ': ' . $d['test_name'] . ' is complete.';
    $lines[] = '';
    $lines[] = 'Winner: ' . $d['winner_label'] . ' (' . number_format( $d['winner_conf'], 0 ) . '% confidence)';

    if ( $d['winner_is_control'] ) {
        $lines[] = 'Control beat all variants - keep it.';
    } else {
        $label = $d['is_revenue'] ? 'revenue per visit' : 'conversion rate';
        $lines[] = '+' . number_format( $d['winner_uplift'], 1 ) . '% ' . $label . ' vs. control over ' . $d['test_age_days'] . ' days';
    }

    if ( $d['impact_kind'] === 'extra' && $d['impact_value'] > 0 ) {
        if ( $d['is_revenue'] ) {
            $lines[] = 'Projected annual impact: extra ' . $d['currency_symbol'] . number_format( $d['impact_value'], 0 ) . '/year';
        } else {
            $lines[] = 'Projected annual impact: extra ' . number_format( $d['impact_value'], 0 ) . ' conversions/year';
        }
    } elseif ( $d['impact_kind'] === 'avoided' && $d['impact_value'] > 0 ) {
        $tail = $d['runner_up_label'] ? ' to the next best variant (' . $d['runner_up_label'] . ')' : ' to the next best variant';
        if ( $d['is_revenue'] ) {
            $lines[] = 'Avoided losing ' . $d['currency_symbol'] . number_format( $d['impact_value'], 0 ) . '/year' . $tail;
        } else {
            $lines[] = 'Avoided losing ' . number_format( $d['impact_value'], 0 ) . ' conversions/year' . $tail;
        }
    }

    $totals_value = $d['is_revenue']
        ? $d['currency_symbol'] . number_format( $d['total_conversions'], 0 ) . ' revenue'
        : number_format( $d['total_conversions'], 0 ) . ' conversions';
    $totals_rate = $d['is_revenue']
        ? $d['currency_symbol'] . number_format( $d['overall_rate'], 2 ) . ' / visit'
        : number_format( $d['overall_rate'] * 100, 2 ) . '% rate';
    $lines[] = number_format( $d['total_visits'] ) . ' visitors, ' . $totals_value . ', ' . $totals_rate;

    $lines[] = '';
    if ( ! empty( $d['report_url'] ) ) {
        $lines[] = 'View the full report:';
        $lines[] = $d['report_url'];
    } else {
        $lines[] = 'Edit this test:';
        $lines[] = $d['edit_url'];
    }

    return implode( "\n", $lines );
}
