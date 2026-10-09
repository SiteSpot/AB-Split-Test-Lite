<?php
/**
 * "Winner found" notification email.
 *
 * Sent once when a running test reaches a winner (see modules/winner-notify.php).
 * It mirrors the in-app report: hero, summary stats, variation table and projected
 * annual impact. The test itself is not changed: it keeps running until the site
 * owner ends it.
 *
 * Public entrypoint: abst_send_winner_found_email().
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'abst_email_brand_name' ) ) {
    /** Name shown in winner emails. */
    function abst_email_brand_name() {
        return 'AB Split Test';
    }
}

/**
 * Build, format and send the "winner found" email to the site admin.
 *
 * @param WP_Post $experiment   The running test.
 * @param array   $observations Analyzed observations (includes bt_bb_ab_stats).
 * @return bool wp_mail() result.
 */
function abst_send_winner_found_email( $experiment, $observations ) {
    $data = abst_build_winner_email_data( $experiment, $observations );
    if ( empty( $data ) ) {
        return false;
    }

    $notify_to = apply_filters( 'abst_winner_email_recipients', get_option( 'admin_email' ), $experiment );
    if ( empty( $notify_to ) ) {
        return false;
    }

    $subject = abst_email_brand_name() . ': ' . $experiment->post_title . ' has a winner';
    $subject = apply_filters( 'abst_winner_email_subject', $subject, $data, $experiment );

    $html = abst_render_winner_email_html( $data );
    $html = apply_filters( 'abst_winner_email_html', $html, $data, $experiment );

    $text = abst_render_winner_email_text( $data );
    $text = apply_filters( 'abst_winner_email_text', $text, $data, $experiment );

    $headers = array( 'Content-Type: text/html; charset=UTF-8' );
    $headers = apply_filters( 'abst_winner_email_headers', $headers, $data, $experiment );

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
function abst_build_winner_email_data( $experiment, $observations ) {
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
        $variations[ (string) $key ] = array(
            'key'         => (string) $key,
            'label'       => abst_get_variation_label( $key, $variation_meta ),
            'visits'      => $visits,
            'conversions' => $conversions,
            'rate'        => ( $visits > 0 ) ? ( $conversions / $visits ) : 0.0, // fraction (0..1)
            'confidence'  => isset( $row['probability'] ) ? (float) $row['probability'] : 0.0,
        );
    }

    if ( empty( $variations ) ) {
        return null;
    }

    // Identify the control via the same helper the report uses.
    global $abst_btab;
    $control_key = null;
    if ( is_object( $abst_btab ) && method_exists( $abst_btab, 'identify_control_variation' ) ) {
        $control_key = $abst_btab->identify_control_variation( $variations, $experiment );
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
    $overall_rate = ( $total_visits > 0 ) ? ( $total_conversions / $total_visits ) : 0.0;

    // Winner uplift and projected annual impact.
    $winner_uplift = 0.0;
    $winner_is_control = ( (string) $winner_key === (string) $control_key );
    if ( ! $winner_is_control && $control_rate > 0 && isset( $variations[ $winner_key ] ) ) {
        $winner_uplift = ( ( $variations[ $winner_key ]['rate'] - $control_rate ) / $control_rate ) * 100.0;
    }

    $impact_kind  = 'none'; // 'extra' | 'avoided' | 'none'
    $impact_value = 0.0;
    $runner_up_key = null;

    // Same projection as the results screen: a year of the busier arm's daily traffic.
    $annual_visits = 0.0;
    if ( isset( $variations[ $winner_key ] ) ) {
        $annual_visits = max( $variations[ $winner_key ]['visits'], $variations[ $control_key ]['visits'] ) / $test_age_days * 365;
    }

    if ( ! $winner_is_control && $winner_uplift > 0 && $total_visits > 0 ) {
        $impact_kind  = 'extra';
        $impact_value = round( ( $variations[ $winner_key ]['rate'] - $control_rate ) * $annual_visits );
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
            $impact_kind  = 'avoided';
            $impact_value = round( ( $control_rate - $runner_up_rate ) * $annual_visits );
        }
    }

    return array(
        'test_id'           => $test_id,
        'test_name'         => $experiment->post_title,
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
        'edit_url'          => admin_url( 'post.php?post=' . $test_id . '&action=edit' ),
        'brand_name'           => abst_email_brand_name(),
    );
}

/**
 * Render the HTML body. Inline CSS only - email clients strip <style>.
 */
function abst_render_winner_email_html( $d ) {
    $font   = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif";
    $green  = '#10b981';
    $red    = '#ef4444';
    $text   = '#1f2937';
    $muted  = '#6b7280';
    $border = '#e5e7eb';
    $bg     = '#f9fafb';

    $esc = function( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); };

    // ---- Hero lines ----
    $hero_lines = array();
    $hero_lines[] = sprintf(
        '<span style="font-size:16px;color:%s;">%s is winning with <strong>%s%% confidence</strong></span>',
        $esc( $text ),
        $esc( $d['winner_label'] ),
        $esc( number_format( $d['winner_conf'], 0 ) )
    );

    if ( $d['winner_is_control'] ) {
        $hero_lines[] = sprintf(
            '<span style="color:%s;">&#128737;&#65039; The original beat every variation &mdash; keep it</span>',
            $esc( $text )
        );
    } else {
        $hero_lines[] = sprintf(
            '<span style="color:%s;">&#128200; <strong style="color:%s;">+%s%%</strong> conversion rate vs. the original</span>',
            $esc( $text ),
            $esc( $green ),
            $esc( number_format( $d['winner_uplift'], 1 ) )
        );
    }

    if ( $d['impact_kind'] === 'extra' && $d['impact_value'] > 0 ) {
        $hero_lines[] = sprintf(
            '<span style="color:%s;">&#128176; <strong>About %s extra conversions per year</strong></span>',
            $esc( $text ),
            $esc( number_format( $d['impact_value'], 0 ) )
        );
    } elseif ( $d['impact_kind'] === 'avoided' && $d['impact_value'] > 0 ) {
        $runner = $d['runner_up_label'] ? ' (' . $esc( $d['runner_up_label'] ) . ')' : '';
        $hero_lines[] = sprintf(
            '<span style="color:%s;">&#128737;&#65039; Keeping the original avoids losing <strong>about %s conversions per year</strong> to the best variation%s</span>',
            $esc( $text ),
            $esc( number_format( $d['impact_value'], 0 ) ),
            $runner
        );
    }

    // ---- Summary cards ----
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
        . $card_cell( 'Visitors', number_format( $d['total_visits'] ) )
        . $card_cell( 'Conversions', number_format( $d['total_conversions'], 0 ) )
        . $card_cell( 'Conv. rate', number_format( $d['overall_rate'] * 100, 2 ) . '%' )
        . $card_cell( 'Running for', $d['test_age_days'] . ( $d['test_age_days'] === 1 ? ' day' : ' days' ) )
        . '</tr></table>';

    // ---- Variation table ----
    $th = function( $label, $align ) use ( $esc, $muted, $border ) {
        return '<th align="' . $align . '" style="padding:10px 12px;font-size:11px;color:' . $esc( $muted ) . ';text-transform:uppercase;letter-spacing:0.04em;border-bottom:1px solid ' . $esc( $border ) . ';">' . $esc( $label ) . '</th>';
    };
    $thead = '<thead><tr style="background:' . $esc( $bg ) . ';">'
        . $th( 'Variation', 'left' )
        . $th( 'Visitors', 'right' )
        . $th( 'Conv.', 'right' )
        . $th( 'Rate', 'right' )
        . $th( 'vs. Original', 'right' )
        . $th( 'Conf.', 'right' )
        . '</tr></thead>';

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
            $badges .= '<span style="display:inline-block;background:#eef2ff;color:#4338ca;font-size:10px;font-weight:600;padding:2px 8px;border-radius:10px;margin-left:6px;">ORIGINAL</span>';
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
            $esc( $border ), $esc( $text ), $esc( number_format( $v['conversions'], 0 ) ),
            $esc( $border ), $esc( $text ), $esc( number_format( $v['rate'] * 100, 2 ) . '%' ),
            $esc( $border ), $uplift_cell,
            $esc( $border ), $esc( $text ), $conf_cell
        );
    }

    $variation_table = '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid ' . $esc( $border ) . ';border-radius:8px;border-collapse:separate;overflow:hidden;">' . $thead . '<tbody>' . $rows_html . '</tbody></table>';

    // ---- CTA ----
    $cta_block = sprintf(
        '<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center"><tr><td align="center" style="border-radius:6px;background:%s;"><a href="%s" style="display:inline-block;padding:12px 24px;color:#ffffff;font-weight:600;font-size:15px;text-decoration:none;border-radius:6px;">Open the test</a></td></tr></table>',
        $esc( $green ),
        $esc( $d['edit_url'] )
    );

    // ---- Assemble ----
    $html  = '<!DOCTYPE html><html><body style="margin:0;padding:0;background:' . $esc( $bg ) . ';font-family:' . $font . ';color:' . $esc( $text ) . ';">';
    $html .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:' . $esc( $bg ) . ';"><tr><td align="center" style="padding:24px 12px;">';
    $html .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid ' . $esc( $border ) . ';">';

    // Header band
    $html .= '<tr><td style="background:' . $esc( $green ) . ';padding:14px 24px;">';
    $html .= '<div style="color:#ffffff;font-weight:700;font-size:14px;letter-spacing:0.06em;text-transform:uppercase;">' . $esc( $d['brand_name'] ) . '</div>';
    $html .= '</td></tr>';

    // Hero
    $html .= '<tr><td style="padding:28px 24px 8px;">';
    $html .= '<h1 style="margin:0 0 8px;font-size:22px;font-weight:700;color:' . $esc( $text ) . ';line-height:1.3;">&#127881; "' . $esc( $d['test_name'] ) . '" has a winner</h1>';
    foreach ( $hero_lines as $line ) {
        $html .= '<p style="margin:8px 0 0;font-size:15px;line-height:1.5;">' . $line . '</p>';
    }
    $html .= '<p style="margin:14px 0 0;font-size:14px;line-height:1.5;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:12px 14px;color:' . $esc( $text ) . ';">';
    $html .= 'The test is <strong>still running</strong> and nothing has changed on your site: visitors still see every version. When you are ready, use Mark complete in the test\'s status box, and put the winning version on your page.';
    $html .= '</p>';
    $html .= '</td></tr>';

    // Stats
    $html .= '<tr><td style="padding:20px 18px 12px;">' . $summary_table . '</td></tr>';

    // Variation table
    $html .= '<tr><td style="padding:8px 24px 24px;">';
    $html .= '<h2 style="margin:8px 0 12px;font-size:16px;font-weight:600;color:' . $esc( $text ) . ';">Variation Performance</h2>';
    $html .= $variation_table;
    $html .= '</td></tr>';

    // CTA
    $html .= '<tr><td style="padding:8px 24px 28px;text-align:center;">' . $cta_block . '</td></tr>';

    // Footer
    $html .= '<tr><td style="padding:16px 24px;background:' . $esc( $bg ) . ';border-top:1px solid ' . $esc( $border ) . ';font-size:12px;color:' . $esc( $muted ) . ';">';
    $html .= 'You received this because you are the admin of this site. You get one email each time a test finds a winner.';
    $html .= '</td></tr>';

    $html .= '</table>';
    $html .= '</td></tr></table>';
    $html .= '</body></html>';

    return $html;
}

/**
 * Plain-text fallback for clients that strip HTML.
 */
function abst_render_winner_email_text( $d ) {
    $lines = array();
    $lines[] = $d['brand_name'] . ': ' . $d['test_name'] . ' has a winner.';
    $lines[] = '';
    $lines[] = 'Winning: ' . $d['winner_label'] . ' (' . number_format( $d['winner_conf'], 0 ) . '% confidence)';

    if ( $d['winner_is_control'] ) {
        $lines[] = 'The original beat every variation - keep it.';
    } else {
        $lines[] = '+' . number_format( $d['winner_uplift'], 1 ) . '% conversion rate vs. the original over ' . $d['test_age_days'] . ' days';
    }

    if ( $d['impact_kind'] === 'extra' && $d['impact_value'] > 0 ) {
        $lines[] = 'Projected: about ' . number_format( $d['impact_value'], 0 ) . ' extra conversions per year';
    } elseif ( $d['impact_kind'] === 'avoided' && $d['impact_value'] > 0 ) {
        $tail = $d['runner_up_label'] ? ' to the best variation (' . $d['runner_up_label'] . ')' : ' to the best variation';
        $lines[] = 'Keeping the original avoids losing about ' . number_format( $d['impact_value'], 0 ) . ' conversions per year' . $tail;
    }

    $lines[] = number_format( $d['total_visits'] ) . ' visitors, ' . number_format( $d['total_conversions'], 0 ) . ' conversions, ' . number_format( $d['overall_rate'] * 100, 2 ) . '% rate';
    $lines[] = '';
    $lines[] = 'The test is still running and nothing has changed on your site: visitors still see every version. When you are ready, use Mark complete in the test\'s status box, and put the winning version on your page.';
    $lines[] = '';
    $lines[] = 'Open the test:';
    $lines[] = $d['edit_url'];

    return implode( "\n", $lines );
}
