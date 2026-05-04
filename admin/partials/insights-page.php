<?php
/**
 * Admin partial for Insights page
 */


 if ( function_exists( 'is_woocommerce_active' ) && is_woocommerce_active() ) {

    global $wpdb;

    $one_year_ago = gmdate( 'Y-m-d H:i:s', strtotime( '-12 months' ) );

    $results = $wpdb->get_results( $wpdb->prepare(
        "SELECT order_items.order_item_name as name,
                order_item_meta.meta_value as product_id,
                SUM(order_item_meta_qty.meta_value) as quantity
        FROM {$wpdb->prefix}woocommerce_order_items as order_items
        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta
            ON order_items.order_item_id = order_item_meta.order_item_id
            AND order_item_meta.meta_key = '_product_id'
        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta_qty
            ON order_items.order_item_id = order_item_meta_qty.order_item_id
            AND order_item_meta_qty.meta_key = '_qty'
        LEFT JOIN {$wpdb->posts} AS posts
            ON posts.ID = order_items.order_id
        WHERE posts.post_type = 'shop_order'
            AND posts.post_status IN ('wc-completed','wc-processing')
            AND posts.post_date >= %s
        GROUP BY product_id
        ORDER BY quantity DESC
        LIMIT %d",
        $one_year_ago,
        5
    ) );
    $topProducts = [];

    foreach ( $results as $product ) {
        $product_obj = wc_get_product( $product->product_id );
        
        // Skip if product doesn't exist (deleted, invalid ID, etc.)
        if ( ! $product_obj || ! is_object( $product_obj ) ) {
            continue;
        }
        
        $topProducts[] = array(
            'name' => $product_obj->get_name(),
            'url' => $product_obj->get_permalink(),
            'quantity' => $product->quantity
        );
    }
    if(!empty($topProducts))
        echo "<script> window.topProducts = " . wp_json_encode($topProducts) . ";</script>";
}
if(get_option('abst_insights')){
    echo "<script>window.abstinsights = " . wp_json_encode(json_decode(get_option('abst_insights'))) . ";</script>";
}
echo "<script>window.abstTiNonce = '" . esc_js( wp_create_nonce( 'abst_ti_nonce' ) ) . "';</script>";


?>
<div class="wrap">
    <h1>CRO Hub</h1>
    <p class="abst-insights-page-description">The spot for analysis, context, and everything a CRO expert needs to grow.</p>
    <div id="abst-insights-content">
        <p>Loading...</p>
    </div>
</div>
<style>
/* CROAssist Insights Page - WPMU Panel Style */
#abst-insights-content {
    max-width: 800px;
}

.abst-insights-page-description {
    max-width: 800px;
    margin: 8px 0 18px 0;
    color: #64748b;
    font-size: 14px;
    line-height: 1.6;
}

/* Previously generated notice */
h3.prevgen {    
    background: #ecfdf5;
    padding: 12px 16px;
    border: 1px solid #a7f3d0;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    color: #065f46;
    margin: 12px 0;
}

h3.prevgen a {
    color: #059669;
    font-weight: 600;
}

/* Panel - the main card style */
.insights-panel {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 24px 30px;
    margin: 16px 0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

/* Panel headers - WPMU style bold titles */
.insights-panel h2 {
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 16px 0;
    padding: 0 0 16px 0;
    letter-spacing: -0.01em;
    border-bottom: 1px solid #e2e8f0;
}

.insights-panel h3 {
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
    margin: 20px 0 8px 0;
}

.insights-panel h3:first-of-type {
    margin-top: 0;
}

.insights-panel p {
    font-size: 14px;
    color: #64748b;
    line-height: 1.6;
    margin: 6px 0;
}

.insights-panel ul,
.insights-panel ol {
    margin: 8px 0 8px 20px;
    padding: 0;
}

.insights-panel li {
    font-size: 14px;
    color: #64748b;
    margin: 4px 0;
    line-height: 1.5;
}

/* Test suggestions section title */
.insights-section-title {
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
    margin: 32px 0 8px 0;
    letter-spacing: -0.01em;
}

.insights-section-desc {
    font-size: 14px;
    color: #64748b;
    margin: 0 0 16px 0;
    line-height: 1.5;
}

/* Test suggestion cards - each is a panel */
.insights-test-suggestion {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin: 16px 0;
    padding: 24px 30px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.insights-test-suggestion h3 {
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 18px 0;
    padding: 0;
    line-height: 1.4;
    border-bottom: none;
}

.insights-test-suggestion p {
    font-size: 14px;
    color: #64748b;
    margin: 8px 0;
    line-height: 1.5;
}

/* Variation items - simple list */
.item-variations {
    list-style-type: disc;
    padding: 0;
    margin: 8px 0 8px 20px;
}

.item-variations li {
    background: none;
    border: none;
    padding: 4px 0;
    margin: 2px 0;
    font-size: 13px;
    color: #334155;
}

/* Theory/thesis callout */
.item-thesis {
    font-size: 15px;
    padding: 18px 22px;
    color: #475569;
    background: #f8fafc;
    border-left: 3px solid #17A8E3;
    border-radius: 0 4px 4px 0;
    margin: 24px 0 18px;
}

/* Law/Theory badges */
.lawbadge {
    display: inline-block;
    background: #f0fdf4;
    color: #16a34a;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
    margin-right: 8px;
}

.lawdescription {
    font-size: 14px;
    color: #64748b;
    margin: 18px 0;
    padding: 14px 18px;
    background: #f8fafc;
    border-radius: 4px;
}

/* Seen on page badge */
.test-suggestion-seen-page {
    display: block;
    font-size: 11px;
    font-weight: 500;
    color: #64748b;
    margin-bottom: 4px;
}

.insights-test-actions {
    display: flex;
    align-items: center;
    gap: 24px;
    flex-wrap: wrap;
    margin-top: 24px;
}

.insights-test-actions .button {
    align-items: center;
    background: #17A8E3;
    color: #ffffff;
    border: none;
    border-radius: 6px;
    box-sizing: border-box;
    display: inline-flex;
    flex: 0 0 210px;
    height: 46px;
    justify-content: center;
    line-height: 1;
    min-height: 46px;
    min-width: 210px;
    padding: 0 22px;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    text-align: center;
    cursor: pointer;
    transition: all 0.15s ease;
    text-decoration: none;
    vertical-align: top;
    white-space: nowrap;
}

.insights-test-actions .button:hover {
    background: #1289ba;
    color: #ffffff;
}

.insights-test-actions .abst-add-to-test-ideas {
    background: #ffffff;
    color: #17A8E3;
    border: 2px solid #17A8E3;
    box-shadow: none;
}

.insights-test-actions .abst-add-to-test-ideas:hover {
    background: #effaff;
    border-color: #1289ba;
    color: #1289ba;
}

.insights-test-actions .abst-add-to-test-ideas:focus {
    border-color: #1289ba;
    box-shadow: 0 0 0 1px #1289ba;
    color: #1289ba;
}

.insights-hub-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.insights-hub-grid .full {
    grid-column: 1 / -1;
}

.insights-hub-grid label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
    color: #334155;
}

.insights-hub-grid textarea,
.insights-hub-grid input {
    width: 100%;
}

.icer-score-wrap {
    margin: 10px 0 18px;
    max-width: 100%;
}

.icer-score-card {
    display: grid;
    grid-template-columns: 88px repeat(4, 120px);
    align-items: stretch;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    box-shadow: none;
    margin: 0;
    max-width: 100%;
    overflow-x: auto;
    overflow-y: visible;
    width: max-content;
}

.icer-total {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 6px;
    min-width: 88px;
    padding: 10px 0;
    border-right: 1px solid #e2e8f0;
    background: #ffffff;
}

.icer-total span,
.icer-label {
    color: #334155;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.06em;
    line-height: 1.2;
    text-transform: uppercase;
}

.icer-total-label {
    align-items: center;
    display: inline-flex;
    gap: 4px;
    justify-content: center;
}

.icer-help-toggle {
    align-items: center;
    background: transparent;
    border: 1px solid #d7dee8;
    border-radius: 50%;
    color: #94a3b8;
    cursor: pointer;
    display: inline-flex;
    font-size: 9px;
    font-weight: 800;
    height: 14px;
    justify-content: center;
    line-height: 1;
    margin: 0;
    padding: 0;
    width: 14px;
}

.icer-help-toggle:hover,
.icer-help-toggle[aria-expanded="true"] {
    background: #f8fafc;
    border-color: #17A8E3;
    color: #1289ba;
}

.icer-help-panel {
    background: #f8fafc;
    border-left: 3px solid #e2e8f0;
    color: #475569;
    font-size: 13px;
    line-height: 1.45;
    margin-top: -1px;
    max-width: 568px;
    padding: 10px 12px;
}

.icer-help-panel strong {
    color: #0f172a;
    font-weight: 800;
}

.icer-total strong {
    align-items: baseline;
    color: #17A8E3;
    display: inline-flex;
    font-size: 25px;
    gap: 2px;
    font-weight: 800;
    line-height: 1;
}

.icer-total strong small {
    color: #64748b;
    font-size: 13px;
    font-weight: 700;
}

.icer-metrics {
    display: contents;
    background: #ffffff;
}

.icer-metric {
    display: grid;
    grid-template-rows: auto auto auto;
    min-width: 120px;
    padding: 10px 14px 12px;
    border-right: 1px solid #e2e8f0;
    box-sizing: border-box;
}

.icer-metric:last-child {
    border-right: none;
}

.icer-value {
    color: #0f172a;
    display: block;
    font-size: 22px;
    font-weight: 800;
    line-height: 1.2;
    margin-top: 2px;
}

.icer-desc {
    color: #64748b;
    display: block;
    font-size: 11px;
    line-height: 1.25;
    margin-top: 6px;
    max-width: 100%;
    overflow-wrap: anywhere;
    min-height: 14px;
}

@media (max-width: 1100px) {
    .icer-score-card {
        grid-template-columns: 88px repeat(4, 120px);
    }

    .icer-metrics {
        display: contents;
    }

    .icer-metric:nth-child(2n) {
        border-right: 1px solid #e2e8f0;
    }

    .icer-metric:nth-child(-n + 2) {
        border-bottom: none;
    }
}

@media (max-width: 640px) {
    .insights-test-actions {
        gap: 12px;
    }

    .insights-test-actions .button {
        width: 100%;
    }

    .icer-score-card {
        grid-template-columns: 78px repeat(4, 96px);
    }

    .icer-total {
        min-width: 78px;
        padding: 9px 0;
    }

    .icer-total strong {
        font-size: 22px;
    }

    .icer-metrics {
        display: contents;
    }

    .icer-metric {
        min-width: 96px;
        padding: 9px 10px;
        border-right: 1px solid #e2e8f0;
        border-bottom: none;
    }

    .icer-metric:last-child {
        border-right: none;
    }

    .icer-label {
        font-size: 9px;
    }

    .icer-value {
        font-size: 19px;
    }

    .icer-desc {
        font-size: 10px;
        line-height: 1.2;
        margin-top: 4px;
    }
}
</style>

<script>

    //hide body without jquery
    //get body css opacity o
    if (window.location.search.includes('abiframe=true')) {
        document.body.style.opacity = 0;
    }
jQuery(document).ready(function($){

    //if querystring is abiframe=true, runInsightsFetch();
    if (window.location.search.includes('abiframe=true')) {
        //hide everythign excepty #wpbody-content and contents
        //allow height to extend down min height 100% tho
        jQuery('#wpbody').css('height', 'fit-content');        
        jQuery('#wpbody').css('min-height', '100%');
        jQuery('#wpbody-content').css('min-height', '100%');
        jQuery('#wpbody').css('width', '100%');
        jQuery('#wpbody').css('padding', '0');
        jQuery('#wpbody').css('margin', '0');
        jQuery('#wpbody').css('box-sizing', 'border-box');
        jQuery('#wpbody').css('display', 'block');
        jQuery('#wpbody').css('position', 'absolute');
        jQuery('#wpbody').css('z-index', '999999');
        jQuery('#wpbody').css('top', '0');
        jQuery('#wpbody').css('left', '0');
        jQuery('#wpbody').css('right', '0');
        jQuery('#wpbody').css('bottom', '0');
        jQuery('#wpbody').css('background', '#f0f0f1');
        jQuery('#wpbody-content').css('padding', '0');
        jQuery('#wpadminbar').css('display', 'none');
        jQuery('html').removeClass('wp-toolbar');
        jQuery('h1, #adminmenuwrap, #adminmenubar, #wpfooter').remove();
        runInsightsFetch();
        document.body.style.transition = 'opacity 1s ease-in-out';
        document.body.style.opacity = 1;

    
        //keep scrolled to bottom of iframe every 1 second animate down ease in out
        var autoscroller = setInterval(function() {
            if(jQuery('#results-in').length)
        {
                clearInterval(autoscroller);    
                //post message to iframe parent
                window.parent.postMessage('aidone', '*');
        }
            scrollToBottom();
        }, 1000);
    }
});

function scrollToBottom() {
    const scrollHeight = document.documentElement.scrollHeight;
    window.scrollTo({
        top: scrollHeight,
        behavior: 'smooth'
    });
}

</script>
