# Plugin Check Report

**Plugin:** AB Split Test Lite
**Generated at:** 2026-05-04 14:40:30


## `C:\laragon\www\tom\wp-content\plugins\ab-split-test-lite\admin\bt-bb-ab-admin.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 52 | 64 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;bt-bb-ab-nonce&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 52 | 64 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_POST[&#039;bt-bb-ab-nonce&#039;] |  |
| 69 | 102 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;selected_post_types&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 108 | 18 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;heatmap_pages&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 108 | 18 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_POST[&#039;heatmap_pages&#039;] |  |
| 353 | 43 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bb_bt_ab_licence_key&quot;. |  |
| 406 | 36 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bb_bt_ab_licence_key&quot;. |  |
| 434 | 7 | WARNING | WordPress.Security.SafeRedirect.wp_redirect_wp_redirect | wp_redirect() found. Using wp_safe_redirect(), along with the &quot;allowed_redirect_hosts&quot; filter if needed, can help avoid any chances of malicious redirects within code. It is also important to remember to call exit() after a redirect so that no other unwanted code is executed. | [Docs](https://developer.wordpress.org/reference/functions/wp_safe_redirect/) |
| 445 | 9 | WARNING | WordPress.Security.SafeRedirect.wp_redirect_wp_redirect | wp_redirect() found. Using wp_safe_redirect(), along with the &quot;allowed_redirect_hosts&quot; filter if needed, can help avoid any chances of malicious redirects within code. It is also important to remember to call exit() after a redirect so that no other unwanted code is executed. | [Docs](https://developer.wordpress.org/reference/functions/wp_safe_redirect/) |
| 486 | 37 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bb_bt_ab_licence_key&quot;. |  |
| 604 | 15 | ERROR | WordPress.WP.I18n.MissingTranslatorsComment | A function call to __() with texts containing placeholders was found, but was not accompanied by a "translators:" comment on the line above to clarify the meaning of the placeholders. | [Docs](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#descriptions) |
| 659 | 38 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bb_bt_ab_licence_key&quot;. |  |
| 693 | 9 | WARNING | WordPress.Security.SafeRedirect.wp_redirect_wp_redirect | wp_redirect() found. Using wp_safe_redirect(), along with the &quot;allowed_redirect_hosts&quot; filter if needed, can help avoid any chances of malicious redirects within code. It is also important to remember to call exit() after a redirect so that no other unwanted code is executed. | [Docs](https://developer.wordpress.org/reference/functions/wp_safe_redirect/) |
| 703 | 7 | WARNING | WordPress.Security.SafeRedirect.wp_redirect_wp_redirect | wp_redirect() found. Using wp_safe_redirect(), along with the &quot;allowed_redirect_hosts&quot; filter if needed, can help avoid any chances of malicious redirects within code. It is also important to remember to call exit() after a redirect so that no other unwanted code is executed. | [Docs](https://developer.wordpress.org/reference/functions/wp_safe_redirect/) |
| 741 | 17 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 741 | 54 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 743 | 15 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 746 | 33 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 746 | 33 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_GET[&#039;message&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 746 | 33 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_GET[&#039;message&#039;] |  |
| 888 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$activateAbSplitTest&quot;. |  |

## `C:\laragon\www\tom\wp-content\plugins\ab-split-test-lite\bt-bb-ab.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 52 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$parts&quot;. |  |
| 54 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$folder_path&quot;. |  |
| 59 | 84 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;ab_wl_ab_test&quot;. |  |
| 395 | 24 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 759 | 15 | WARNING | WordPress.Security.SafeRedirect.wp_redirect_wp_redirect | wp_redirect() found. Using wp_safe_redirect(), along with the &quot;allowed_redirect_hosts&quot; filter if needed, can help avoid any chances of malicious redirects within code. It is also important to remember to call exit() after a redirect so that no other unwanted code is executed. | [Docs](https://developer.wordpress.org/reference/functions/wp_safe_redirect/) |
| 763 | 15 | WARNING | WordPress.Security.SafeRedirect.wp_redirect_wp_redirect | wp_redirect() found. Using wp_safe_redirect(), along with the &quot;allowed_redirect_hosts&quot; filter if needed, can help avoid any chances of malicious redirects within code. It is also important to remember to call exit() after a redirect so that no other unwanted code is executed. | [Docs](https://developer.wordpress.org/reference/functions/wp_safe_redirect/) |
| 1355 | 33 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 1367 | 17 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 1483 | 19 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 1483 | 19 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 1483 | 32 | ERROR | WordPress.DB.PreparedSQL.NotPrepared | Use placeholders and $wpdb->prepare(); found $sql |  |
| 1713 | 21 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 1713 | 53 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 2025 | 15 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 2510 | 72 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 2510 | 93 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 2642 | 16 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 2642 | 16 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;bt_experiments_inner_custom_box_nonce&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 2642 | 16 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_POST[&#039;bt_experiments_inner_custom_box_nonce&#039;] |  |
| 3076 | 20 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;breeze_clear_all_cache&quot;. |  |
| 3322 | 15 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 3332 | 101 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_SERVER[&#039;HTTP_ACCEPT&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 3332 | 101 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_SERVER[&#039;HTTP_ACCEPT&#039;] |  |
| 3502 | 21 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 3502 | 21 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotValidated | Detected usage of a possibly undefined superglobal array index: $_POST[&#039;pid&#039;]. Check that the array index exists before using it. |  |
| 3504 | 45 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 3504 | 45 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotValidated | Detected usage of a possibly undefined superglobal array index: $_POST[&#039;variation_name&#039;]. Check that the array index exists before using it. |  |
| 3504 | 45 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;variation_name&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 3506 | 43 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 3506 | 43 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotValidated | Detected usage of a possibly undefined superglobal array index: $_POST[&#039;variation_id&#039;]. Check that the array index exists before using it. |  |
| 3506 | 43 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;variation_id&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 3598 | 12 | ERROR | WordPress.WP.EnqueuedResources.NonEnqueuedScript | Scripts must be registered/enqueued via wp_enqueue_script() |  |
| 3600 | 12 | ERROR | WordPress.WP.EnqueuedResources.NonEnqueuedScript | Scripts must be registered/enqueued via wp_enqueue_script() |  |
| 3602 | 12 | ERROR | WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet | Stylesheets must be registered/enqueued via wp_enqueue_style() |  |
| 3604 | 12 | ERROR | WordPress.WP.EnqueuedResources.NonEnqueuedScript | Scripts must be registered/enqueued via wp_enqueue_script() |  |
| 3606 | 12 | ERROR | WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet | Stylesheets must be registered/enqueued via wp_enqueue_style() |  |
| 3608 | 12 | ERROR | WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet | Stylesheets must be registered/enqueued via wp_enqueue_style() |  |
| 4698 | 18 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'abst_get_form_optgroups'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 5952 | 50 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'BT_AB_TEST_WL_ABTEST'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 7058 | 70 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$magic_selected'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 7072 | 78 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$full_page_test_selected'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 7080 | 74 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$ab_test_selected'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 7088 | 76 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$css_test_selected'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 7216 | 21 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 7216 | 21 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotValidated | Detected usage of a possibly undefined superglobal array index: $_POST[&#039;pid&#039;]. Check that the array index exists before using it. |  |
| 7218 | 40 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 7218 | 40 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotValidated | Detected usage of a possibly undefined superglobal array index: $_POST[&#039;variation&#039;]. Check that the array index exists before using it. |  |
| 7218 | 40 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;variation&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 7384 | 26 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bt_ab_shortcode&quot;. |  |
| 7424 | 31 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 7424 | 31 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;email&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 7618 | 46 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;ab_complete_confidence&quot;. |  |
| 7620 | 46 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;ab_min_visits_for_winner&quot;. |  |
| 8182 | 27 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 8216 | 30 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotValidated | Detected usage of a possibly undefined superglobal array index: $_SERVER[&#039;REQUEST_URI&#039;]. Check that the array index exists before using it. |  |
| 8216 | 30 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_SERVER[&#039;REQUEST_URI&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 8216 | 30 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_SERVER[&#039;REQUEST_URI&#039;] |  |
| 8218 | 120 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$cleanHref'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 8234 | 30 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 8262 | 30 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotValidated | Detected usage of a possibly undefined superglobal array index: $_SERVER[&#039;REQUEST_URI&#039;]. Check that the array index exists before using it. |  |
| 8262 | 30 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_SERVER[&#039;REQUEST_URI&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 8262 | 30 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_SERVER[&#039;REQUEST_URI&#039;] |  |
| 8270 | 31 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$invalid_count'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 8274 | 26 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$cleanupHref'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 8286 | 38 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;ab_complete_confidence&quot;. |  |
| 8406 | 17 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$test_type_label'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 8724 | 39 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$test_age'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 8724 | 79 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$likelyDuration'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 9538 | 10 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$experiment_status'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 9804 | 50 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;ab_complete_confidence&quot;. |  |
| 9990 | 47 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$class'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 9990 | 78 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$mk'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 9990 | 82 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '"{$uplift_html}"'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 9992 | 45 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$mv['visit']'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 10164 | 14 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '"$mk"'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 10166 | 49 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$mv['rate']'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 10168 | 49 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$chance_of_winning'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 10170 | 49 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$mv['visit']'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 10172 | 49 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$mv['conversion']'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 10300 | 88 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$pid'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 10424 | 48 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;ab_min_visits_for_winner&quot;. |  |
| 10770 | 97 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'abst_get_detected_caches'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 10844 | 28 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$eid'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 10844 | 35 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '";\r\n'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 10944 | 7 | ERROR | WordPress.Security.EscapeOutput.UnsafePrintingFunction | All output should be run through an escaping function (like esc_html_e() or esc_attr_e()), found '_e'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-with-localization) |
| 10954 | 12 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$select'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 10964 | 94 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$post'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 11180 | 34 | ERROR | WordPress.WP.EnqueuedResources.NonEnqueuedScript | Scripts must be registered/enqueued via wp_enqueue_script() |  |
| 11208 | 23 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'ABST_CACHE_EXCLUDES'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 11242 | 55 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '"';\r\n'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 11448 | 26 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_SERVER[&#039;HTTP_CLIENT_IP&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 11448 | 26 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_SERVER[&#039;HTTP_CLIENT_IP&#039;] |  |
| 11452 | 26 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_SERVER[&#039;HTTP_X_FORWARDED_FOR&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 11452 | 26 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_SERVER[&#039;HTTP_X_FORWARDED_FOR&#039;] |  |
| 11456 | 26 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_SERVER[&#039;HTTP_X_FORWARDED&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 11456 | 26 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_SERVER[&#039;HTTP_X_FORWARDED&#039;] |  |
| 11460 | 26 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_SERVER[&#039;HTTP_FORWARDED_FOR&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 11460 | 26 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_SERVER[&#039;HTTP_FORWARDED_FOR&#039;] |  |
| 11464 | 26 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_SERVER[&#039;HTTP_FORWARDED&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 11464 | 26 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_SERVER[&#039;HTTP_FORWARDED&#039;] |  |
| 11468 | 26 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_SERVER[&#039;REMOTE_ADDR&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 11468 | 26 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_SERVER[&#039;REMOTE_ADDR&#039;] |  |
| 11528 | 23 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'ABST_CACHE_EXCLUDES'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 11626 | 55 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_COOKIE[&#039;ab-advanced-id&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 11626 | 55 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_COOKIE[&#039;ab-advanced-id&#039;] |  |
| 11632 | 48 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_COOKIE[&#039;btab_&#039; . $eid] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 11632 | 48 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_COOKIE[&#039;btab_&#039; . $eid] |  |
| 11726 | 48 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_COOKIE[&#039;btab_&#039; . $eid] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 11726 | 48 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_COOKIE[&#039;btab_&#039; . $eid] |  |
| 11780 | 55 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;nonce&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 11780 | 55 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_POST[&#039;nonce&#039;] |  |
| 11796 | 25 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotValidated | Detected usage of a possibly undefined superglobal array index: $_POST[&#039;test_id&#039;]. Check that the array index exists before using it. |  |
| 11810 | 20 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 11810 | 20 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 12032 | 46 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_COOKIE[&#039;btab_&#039; . $eid] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 12032 | 46 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_COOKIE[&#039;btab_&#039; . $eid] |  |
| 12120 | 44 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_COOKIE[&#039;abst_server_events&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 12120 | 44 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_COOKIE[&#039;abst_server_events&#039;] |  |
| 12290 | 14 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$out'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 12344 | 34 | ERROR | WordPress.WP.I18n.TooManyFunctionArgs | Too many parameters passed to function "__()". Expected: 2 parameters, received: 3 | [Docs](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#basic-strings) |
| 12344 | 60 | ERROR | WordPress.WP.I18n.TextDomainMismatch | Mismatched text domain. Expected 'ab-split-test-lite' but got 'Post Type General Name'. | [Docs](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/) |
| 12346 | 34 | ERROR | WordPress.WP.I18n.TooManyFunctionArgs | Too many parameters passed to function "__()". Expected: 2 parameters, received: 3 | [Docs](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#basic-strings) |
| 12346 | 55 | ERROR | WordPress.WP.I18n.TextDomainMismatch | Mismatched text domain. Expected 'ab-split-test-lite' but got 'Post Type Singular Name'. | [Docs](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/) |
| 12460 | 40 | ERROR | WordPress.WP.I18n.MissingArgDomain | Missing $domain parameter in function call to _n_noop(). | [Docs](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/) |
| 12460 | 40 | ERROR | WordPress.WP.I18n.MissingTranslatorsComment | A function call to _n_noop() with texts containing placeholders was found, but was not accompanied by a "translators:" comment on the line above to clarify the meaning of the placeholders. | [Docs](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#descriptions) |
| 12482 | 40 | ERROR | WordPress.WP.I18n.MissingArgDomain | Missing $domain parameter in function call to _n_noop(). | [Docs](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/) |
| 12482 | 40 | ERROR | WordPress.WP.I18n.MissingTranslatorsComment | A function call to _n_noop() with texts containing placeholders was found, but was not accompanied by a "translators:" comment on the line above to clarify the meaning of the placeholders. | [Docs](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#descriptions) |
| 12842 | 30 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'sanitize_text_field'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 12852 | 54 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 12942 | 48 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 12942 | 85 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 13212 | 40 | ERROR | PluginCheck.CodeAnalysis.EnqueuedResourceOffloading.OffloadedContent | Found call to wp_enqueue_style() with external resource. Offloading styles to your servers or any remote service is disallowed. |  |
| 13214 | 40 | ERROR | PluginCheck.CodeAnalysis.EnqueuedResourceOffloading.OffloadedContent | Found call to wp_enqueue_script() with external resource. Offloading scripts to your servers or any remote service is disallowed. |  |
| 13316 | 44 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 13316 | 44 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_GET[&#039;q&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 13650 | 40 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bb_bt_ab_licence_key&quot;. |  |
| 13658 | 51 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 13658 | 51 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotValidated | Detected usage of a possibly undefined superglobal array index: $_POST[&#039;markdown&#039;]. Check that the array index exists before using it. |  |
| 13658 | 51 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;markdown&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 13730 | 26 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 13730 | 67 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 13730 | 67 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;page_id&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 13802 | 23 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 13802 | 48 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 14098 | 40 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bb_bt_ab_licence_key&quot;. |  |
| 14106 | 51 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 14106 | 51 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotValidated | Detected usage of a possibly undefined superglobal array index: $_POST[&#039;markdown&#039;]. Check that the array index exists before using it. |  |
| 14106 | 51 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;markdown&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 14108 | 37 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 14108 | 86 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 14108 | 86 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;heatmapData&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 14192 | 31 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 14192 | 71 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 14192 | 71 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;search&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 14194 | 27 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 14194 | 69 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 14194 | 69 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;exact_id&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 14304 | 28 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotValidated | Detected usage of a possibly undefined superglobal array index: $_POST[&#039;nonce&#039;]. Check that the array index exists before using it. |  |
| 14304 | 28 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;nonce&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 14304 | 28 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_POST[&#039;nonce&#039;] |  |
| 14308 | 79 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotValidated | Detected usage of a possibly undefined superglobal array index: $_POST[&#039;eid&#039;]. Check that the array index exists before using it. |  |
| 14316 | 12 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$embed_code'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 14330 | 28 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 14334 | 25 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'ABST_CACHE_EXCLUDES'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 14604 | 37 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bt_can_user_view_variations&quot;. |  |
| 14874 | 45 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 14894 | 37 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bt_ab_tagging&quot;. |  |
| 15080 | 49 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$hide_css'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 15086 | 38 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$noscript_css'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 15314 | 45 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 15364 | 40 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bt_ab_fingerprint&quot;. |  |
| 15368 | 43 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bt_ab_use_uuid&quot;. |  |
| 15398 | 37 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bt_ab_tagging&quot;. |  |
| 15618 | 28 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 15640 | 25 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'ABST_CACHE_EXCLUDES'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 15646 | 35 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'plugin_dir_url'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 15648 | 33 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'home_url'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 15670 | 12 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$js'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 15674 | 12 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$style'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 15732 | 44 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 15732 | 67 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 15744 | 29 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 15744 | 56 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 15756 | 17 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 15756 | 35 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 15766 | 17 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 15766 | 35 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 15950 | 18 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 15950 | 39 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 15980 | 84 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 15980 | 84 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotValidated | Detected usage of a possibly undefined superglobal array index: $_POST[&#039;eid&#039;]. Check that the array index exists before using it. |  |
| 15982 | 70 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 15982 | 70 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;variation&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 15984 | 76 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 15984 | 76 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotValidated | Detected usage of a possibly undefined superglobal array index: $_POST[&#039;type&#039;]. Check that the array index exists before using it. |  |
| 15984 | 76 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;type&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 15990 | 55 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 15990 | 55 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;location&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 15992 | 62 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 15992 | 62 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;size&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 15994 | 96 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 15994 | 96 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;orderValue&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 15996 | 71 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 15996 | 71 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;uuid&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 15998 | 83 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 15998 | 83 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;ab_advanced_id&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 16662 | 19 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;log_experiment_activity&quot;. |  |
| 16726 | 52 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 16726 | 52 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_GET[&#039;location&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 16728 | 58 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 16728 | 58 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_GET[&#039;device_size&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 16790 | 29 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 16790 | 29 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 16790 | 36 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $table_name used in $wpdb-&gt;get_col()\n$table_name assigned unsafely at line 16738. |  |
| 16790 | 44 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable $table_name at &quot;DESC $table_name&quot; |  |
| 16796 | 13 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 16796 | 13 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 16796 | 20 | ERROR | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $alter_sql used in $wpdb->query()\n$alter_sql used without escaping. |  |
| 16796 | 27 | ERROR | WordPress.DB.PreparedSQL.NotPrepared | Use placeholders and $wpdb->prepare(); found $alter_sql |  |
| 16816 | 31 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable $table_name at &quot;SELECT * FROM $table_name WHERE uuid = %s AND testId = %d&quot; |  |
| 16818 | 19 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 16818 | 19 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 16818 | 26 | ERROR | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $sql used in $wpdb->get_row()\n$sql assigned unsafely at line 16816. |  |
| 16818 | 34 | ERROR | WordPress.DB.PreparedSQL.NotPrepared | Use placeholders and $wpdb->prepare(); found $sql |  |
| 16844 | 28 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 16880 | 19 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 16880 | 19 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 16894 | 21 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 16894 | 21 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 16906 | 24 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 16906 | 24 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 16906 | 31 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $table_name used in $wpdb-&gt;get_row()\n$table_name assigned unsafely at line 16738. |  |
| 16906 | 54 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable $table_name at &quot;SELECT variation FROM $table_name WHERE uuid = %s AND testId = %d&quot; |  |
| 16936 | 31 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable $table_name at &quot;SELECT * FROM $table_name WHERE uuid = %s AND testId = %d AND type = &#039;visit&#039;&quot; |  |
| 16938 | 19 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 16938 | 19 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 16938 | 26 | ERROR | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $sql used in $wpdb->get_row()\n$sql assigned unsafely at line 16936. |  |
| 16938 | 34 | ERROR | WordPress.DB.PreparedSQL.NotPrepared | Use placeholders and $wpdb->prepare(); found $sql |  |
| 16978 | 11 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 16978 | 11 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 17024 | 9 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable $table_name at &quot;DELETE FROM $table_name WHERE timestamp &lt; DATE_SUB(CURRENT_DATE, INTERVAL %d DAY)&quot; |  |
| 17030 | 17 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 17030 | 17 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 17030 | 24 | ERROR | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $query used in $wpdb->query()\n$query assigned unsafely at line 17022. |  |
| 17030 | 30 | ERROR | WordPress.DB.PreparedSQL.NotPrepared | Use placeholders and $wpdb->prepare(); found $query |  |
| 17334 | 18 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 17342 | 25 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 17342 | 46 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 17562 | 14 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$summary'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 20152 | 18 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 20174 | 32 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$results['created']'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 20194 | 43 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$results['errors']'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 20210 | 62 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$results['skipped']'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 20236 | 71 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;absplittest_mcp_nonce&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 20236 | 71 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_POST[&#039;absplittest_mcp_nonce&#039;] |  |
| 20288 | 9 | WARNING | WordPress.Security.SafeRedirect.wp_redirect_wp_redirect | wp_redirect() found. Using wp_safe_redirect(), along with the &quot;allowed_redirect_hosts&quot; filter if needed, can help avoid any chances of malicious redirects within code. It is also important to remember to call exit() after a redirect so that no other unwanted code is executed. | [Docs](https://developer.wordpress.org/reference/functions/wp_safe_redirect/) |
| 20298 | 9 | WARNING | WordPress.Security.SafeRedirect.wp_redirect_wp_redirect | wp_redirect() found. Using wp_safe_redirect(), along with the &quot;allowed_redirect_hosts&quot; filter if needed, can help avoid any chances of malicious redirects within code. It is also important to remember to call exit() after a redirect so that no other unwanted code is executed. | [Docs](https://developer.wordpress.org/reference/functions/wp_safe_redirect/) |
| 20318 | 9 | WARNING | WordPress.Security.SafeRedirect.wp_redirect_wp_redirect | wp_redirect() found. Using wp_safe_redirect(), along with the &quot;allowed_redirect_hosts&quot; filter if needed, can help avoid any chances of malicious redirects within code. It is also important to remember to call exit() after a redirect so that no other unwanted code is executed. | [Docs](https://developer.wordpress.org/reference/functions/wp_safe_redirect/) |
| 20328 | 7 | WARNING | WordPress.Security.SafeRedirect.wp_redirect_wp_redirect | wp_redirect() found. Using wp_safe_redirect(), along with the &quot;allowed_redirect_hosts&quot; filter if needed, can help avoid any chances of malicious redirects within code. It is also important to remember to call exit() after a redirect so that no other unwanted code is executed. | [Docs](https://developer.wordpress.org/reference/functions/wp_safe_redirect/) |
| 20340 | 15 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 20462 | 57 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$plugin['name']'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 20462 | 106 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$settings_link'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 20462 | 202 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'BT_AB_TEST_MERCHANT_URL'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 20490 | 38 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bb_bt_ab_licence_key&quot;. |  |
| 20514 | 84 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$message'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 20592 | 17 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_SERVER[&#039;HTTP_HOST&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 20592 | 17 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_SERVER[&#039;HTTP_HOST&#039;] |  |
| 20594 | 24 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_SERVER[&#039;REQUEST_URI&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 20594 | 24 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_SERVER[&#039;REQUEST_URI&#039;] |  |
| 20640 | 34 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 20644 | 38 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 20644 | 78 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 20644 | 78 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_GET[$exploded_query[0]] |  |
| 20700 | 20 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 20824 | 53 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_COOKIE[$cookie_name] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 20824 | 53 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_COOKIE[$cookie_name] |  |
| 20984 | 21 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_SERVER[&#039;HTTP_HOST&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 20984 | 21 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_SERVER[&#039;HTTP_HOST&#039;] |  |
| 21084 | 49 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_COOKIE[&#039;ab-advanced-id&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 21134 | 9 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$abtest_query_variation&quot;. |  |
| 21476 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$btab&quot;. |  |
| 21482 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;btab_user_level&quot;. |  |
| 21490 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bt_ab_settings&quot;. |  |
| 21512 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bt_bb_ab_defaults&quot;. |  |
| 21544 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bt_bb_ab_licence_status&quot;. |  |
| 21578 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bt_bb_ab_verify_licence&quot;. |  |
| 21596 | 24 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bb_bt_ab_licence_key&quot;. |  |
| 21666 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;getAiLicenceInfo&quot;. |  |
| 21706 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bt_bb_ab_licence_details&quot;. |  |
| 21752 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bb_bt_white_label_details&quot;. |  |
| 21758 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;btab_send_webhook&quot;. |  |
| 21768 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bt_bb_ab_filter_all_plugins&quot;. |  |
| 21824 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;update_admin_setting&quot;. |  |
| 21844 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;jc_append_post_status_list&quot;. |  |
| 21882 | 71 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$idea'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 21884 | 75 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$complete'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 21886 | 19 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$label'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 21894 | 112 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'BT_AB_TEST_WL_ABTEST'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 21914 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;send_to_openai_callback&quot;. |  |
| 21926 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;send_request_to_openai&quot;. |  |
| 21940 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;add_magnificPopup&quot;. |  |
| 21970 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;footer_ai_pieces&quot;. |  |
| 22006 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;ab_split_test_admin_bar_menu&quot;. |  |
| 22068 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;post_types_to_test&quot;. |  |
| 22164 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;ab_get_admin_setting&quot;. |  |
| 22710 | 15 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'BT_AB_TEST_WL_ABTEST'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 22720 | 26 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 22720 | 63 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 22720 | 63 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_GET[&#039;post&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 22732 | 25 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 22732 | 48 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 22734 | 31 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 22734 | 73 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 22734 | 73 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_GET[&#039;variation&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 22736 | 26 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 22736 | 63 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 22736 | 63 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_GET[&#039;size&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 22738 | 26 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 22738 | 63 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 22738 | 63 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_GET[&#039;mode&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 22740 | 26 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 22740 | 63 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 22740 | 63 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_GET[&#039;days&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 22742 | 41 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 22742 | 77 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 22742 | 77 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_GET[&#039;cto&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 22744 | 30 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 22744 | 71 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 22744 | 71 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_GET[&#039;referrer&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 22746 | 32 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 22746 | 75 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 22746 | 75 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_GET[&#039;utm_source&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 22748 | 32 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 22748 | 75 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 22748 | 75 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_GET[&#039;utm_medium&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 22750 | 34 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 22750 | 79 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 22750 | 79 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_GET[&#039;utm_campaign&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 22858 | 13 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 22858 | 31 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 22858 | 31 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_GET[&#039;post&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 22858 | 31 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_GET[&#039;post&#039;] |  |
| 23760 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;get_scroll_data&quot;. |  |
| 24138 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;logs_by_screen_size&quot;. |  |
| 24196 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;resolve_variation_name&quot;. |  |
| 24230 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;search_all_journey_logs&quot;. |  |
| 24488 | 23 | ERROR | WordPress.WP.AlternativeFunctions.parse_url_parse_url | parse_url() is discouraged because of inconsistency in the output across PHP versions; use wp_parse_url() instead. |  |
| 24504 | 23 | ERROR | WordPress.WP.AlternativeFunctions.parse_url_parse_url | parse_url() is discouraged because of inconsistency in the output across PHP versions; use wp_parse_url() instead. |  |
| 24830 | 23 | ERROR | WordPress.WP.AlternativeFunctions.parse_url_parse_url | parse_url() is discouraged because of inconsistency in the output across PHP versions; use wp_parse_url() instead. |  |
| 24846 | 23 | ERROR | WordPress.WP.AlternativeFunctions.parse_url_parse_url | parse_url() is discouraged because of inconsistency in the output across PHP versions; use wp_parse_url() instead. |  |
| 24992 | 13 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 25284 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;trim_abst_log&quot;. |  |
| 25526 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;create_sample_tests_on_activation&quot;. |  |
| 25544 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;load_sample_tests_from_json&quot;. |  |
| 25692 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;create_test_from_json_data&quot;. |  |
| 25726 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;create_test_from_results_data&quot;. |  |
| 25804 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;create_test_from_structured_data&quot;. |  |
| 26014 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;create_sample_magic_definition&quot;. |  |
| 26128 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;MondayMorningReport&quot;. |  |
| 26146 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;sendReportEmail&quot;. |  |
| 26234 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;ab_add_two_hour_schedule&quot;. |  |
| 26266 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;ab_update_thompson_weights&quot;. |  |
| 26276 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;ab_beta_random&quot;. |  |
| 26288 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;ab_gamma_random&quot;. |  |
| 26328 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;ab_normal_random&quot;. |  |
| 26356 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;ab_add_weekly_schedule&quot;. |  |
| 26422 | 37 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_SERVER[&#039;HTTP_HOST&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 26544 | 19 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_SERVER[&#039;HTTP_USER_AGENT&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 26544 | 19 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_SERVER[&#039;HTTP_USER_AGENT&#039;] |  |
| 27340 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;get_or_set_experiment_variation&quot;. |  |
| 27382 | 49 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_COOKIE[$cookie_name] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 27382 | 49 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_COOKIE[$cookie_name] |  |
| 27454 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;_parse_variation_result&quot;. |  |
| 27494 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;should_user_see_test&quot;. |  |
| 27586 | 24 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotValidated | Detected usage of a possibly undefined superglobal array index: $_SERVER[&#039;REQUEST_URI&#039;]. Check that the array index exists before using it. |  |
| 27586 | 24 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_SERVER[&#039;REQUEST_URI&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 27586 | 24 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_SERVER[&#039;REQUEST_URI&#039;] |  |
| 27626 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;select_variation_for_user&quot;. |  |
| 27676 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;select_variation_by_weights&quot;. |  |
| 27822 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;get_current_post_id&quot;. |  |

## `C:\laragon\www\tom\wp-content\plugins\ab-split-test-lite\admin\partials\single-site-display.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 34 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$license_key&quot;. |  |
| 36 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$license_status&quot;. |  |
| 40 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$price_id&quot;. |  |
| 48 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$creditLevels&quot;. |  |
| 50 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$aiCreditFrequency&quot;. |  |
| 52 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$aiCreditAmount&quot;. |  |
| 60 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$aiCreditFrequency&quot;. |  |
| 62 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$aiCreditAmount&quot;. |  |
| 68 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$fathom_api_key&quot;. |  |
| 70 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$webhook_global&quot;. |  |
| 72 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$ab_openapi_key&quot;. |  |
| 76 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$ab_openapi_model&quot;. |  |
| 78 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$post_types&quot;. |  |
| 80 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$selected_post_types&quot;. |  |
| 82 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$add_canonical&quot;. |  |
| 84 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$use_fingerprint&quot;. |  |
| 86 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$fingerprint_length&quot;. |  |
| 88 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$use_uuid&quot;. |  |
| 90 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$uuid_length&quot;. |  |
| 92 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$enable_user_journeys&quot;. |  |
| 94 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$enable_session_replays&quot;. |  |
| 104 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$heatmap_retention_length&quot;. |  |
| 108 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$weekly_report_emails&quot;. |  |
| 112 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$weekly_reports_checked&quot;. |  |
| 116 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$thompson_sampling_checked&quot;. |  |
| 118 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$detected_caches&quot;. |  |
| 120 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$delete_fingerprint_db_on_uninstall&quot;. |  |
| 122 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$wait_for_approval&quot;. |  |
| 158 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$mcpServerName&quot;. |  |
| 160 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$mcpServerName&quot;. |  |
| 162 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$mcpServerName&quot;. |  |
| 164 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$mcpServerName&quot;. |  |
| 178 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$upgrade_link&quot;. |  |
| 180 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$upgrade_link_teams&quot;. |  |
| 188 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$heatmap_upgrade&quot;. |  |
| 206 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$all_woo_order_statuses&quot;. |  |
| 212 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$all_woo_order_statuses&quot;. |  |
| 218 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$selected_statuses&quot;. |  |
| 224 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$selected_statuses&quot;. |  |
| 228 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$selected_woo_order_statuses&quot;. |  |
| 232 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$checked&quot;. |  |
| 234 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$status_label&quot;. |  |
| 236 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$selected_woo_order_statuses&quot;. |  |
| 254 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$selected_woo_order_statuses&quot;. |  |
| 256 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$selected_woo_order_statuses&quot;. |  |
| 290 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$dont_clear_cache_setting&quot;. |  |
| 294 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$enable_clear_cache&quot;. |  |
| 298 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$enable_clear_cache&quot;. |  |
| 308 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$wait_for_approval&quot;. |  |
| 312 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$wait_for_approval&quot;. |  |
| 318 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$visit_on_visible&quot;. |  |
| 322 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$visit_on_visible&quot;. |  |
| 328 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$use_fingerprint&quot;. |  |
| 334 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$use_uuid&quot;. |  |
| 340 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$add_canonical&quot;. |  |
| 348 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$enable_user_journeys&quot;. |  |
| 352 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$enable_user_journeys&quot;. |  |
| 362 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$enable_session_replays&quot;. |  |
| 366 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$enable_session_replays&quot;. |  |
| 370 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$enable_session_replays&quot;. |  |
| 378 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$heatmap_pages&quot;. |  |
| 382 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$heatmap_pages&quot;. |  |
| 388 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$heatmap_all_pages&quot;. |  |
| 394 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$heatmap_all_pages&quot;. |  |
| 398 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$heatmap_all_pages&quot;. |  |
| 410 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$delete_fingerprint_db_on_uninstall&quot;. |  |
| 414 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$delete_fingerprint_db_on_uninstall&quot;. |  |
| 434 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$weekly_report_emails&quot;. |  |
| 464 | 13 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$licence_level_nice&quot;. |  |
| 466 | 40 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$licence_level_nice&quot;. |  |
| 770 | 11 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$selected_post_types&quot;. |  |
| 776 | 11 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$checked&quot;. |  |
| 870 | 71 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$aiCreditAmount'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 870 | 149 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$aiCreditFrequency'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 872 | 27 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'ab_get_admin_setting'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 1076 | 101 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'home_url'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 1220 | 11 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$mcp_adapter_installed&quot;. |  |
| 1226 | 11 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$wp_cli_available&quot;. |  |
| 1548 | 29 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 1560 | 29 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 1564 | 85 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 1564 | 85 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_GET[&#039;mcp_install_error&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 1564 | 85 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_GET[&#039;mcp_install_error&#039;] |  |
| 1922 | 119 | ERROR | WordPress.Security.EscapeOutput.UnsafePrintingFunction | All output should be run through an escaping function (like esc_html_e() or esc_attr_e()), found '_e'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-with-localization) |

## `C:\laragon\www\tom\wp-content\plugins\ab-split-test-lite\modules\forms\form-conversions.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 1 | 1 | WARNING | Internal.LineEndings.Mixed | File has mixed line endings; this may cause incorrect results |  |
| 680 | 13 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 680 | 13 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 680 | 20 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $table used in $wpdb-&gt;get_var()\n$table assigned unsafely at line 677. |  |
| 680 | 28 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable $table at &quot;SHOW TABLES LIKE &#039;$table&#039;&quot; |  |
| 685 | 25 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 685 | 25 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 685 | 32 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $table used in $wpdb-&gt;get_results()\n$table assigned unsafely at line 677. |  |
| 685 | 44 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable $table at &quot;SELECT id, name FROM $table ORDER BY name ASC&quot; |  |
| 760 | 73 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_COOKIE[&#039;ab-advanced-id&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 768 | 48 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_COOKIE[&#039;btab_&#039; . $eid] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 768 | 48 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_COOKIE[&#039;btab_&#039; . $eid] |  |
| 786 | 23 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 786 | 53 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 787 | 36 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 787 | 59 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 787 | 59 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;abst_data&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 787 | 59 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_POST[&#039;abst_data&#039;] |  |
| 787 | 81 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 787 | 81 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_REQUEST[&#039;abst_data&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 787 | 81 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_REQUEST[&#039;abst_data&#039;] |  |
| 798 | 28 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 798 | 28 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 798 | 35 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $table_name used in $wpdb-&gt;get_row()\n$table_name assigned unsafely at line 797. |  |
| 799 | 25 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable $table_name at &quot;SELECT type FROM $table_name WHERE uuid = %s AND testId = %d ORDER BY timestamp DESC LIMIT 1&quot; |  |
| 816 | 20 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 816 | 20 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 816 | 27 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $table_name used in $wpdb-&gt;get_row()\n$table_name assigned unsafely at line 813. |  |
| 817 | 17 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable $table_name at &quot;SELECT variation, type FROM $table_name WHERE uuid = %s AND testId = %d ORDER BY timestamp DESC LIMIT 1&quot; |  |
| 910 | 73 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_COOKIE[&#039;ab-advanced-id&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 918 | 48 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_COOKIE[&#039;btab_&#039; . $eid] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 918 | 48 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_COOKIE[&#039;btab_&#039; . $eid] |  |
| 942 | 23 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 942 | 53 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 943 | 36 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 943 | 59 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 943 | 59 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;abst_data&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 943 | 59 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_POST[&#039;abst_data&#039;] |  |
| 943 | 81 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 943 | 81 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_REQUEST[&#039;abst_data&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 943 | 81 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_REQUEST[&#039;abst_data&#039;] |  |
| 954 | 28 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 954 | 28 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 954 | 35 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $table_name used in $wpdb-&gt;get_row()\n$table_name assigned unsafely at line 953. |  |
| 955 | 25 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable $table_name at &quot;SELECT goals FROM $table_name WHERE uuid = %s AND testId = %d ORDER BY timestamp DESC LIMIT 1&quot; |  |
| 973 | 20 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 973 | 20 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 973 | 27 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $table_name used in $wpdb-&gt;get_row()\n$table_name assigned unsafely at line 970. |  |
| 974 | 17 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable $table_name at &quot;SELECT variation, goals FROM $table_name WHERE uuid = %s AND testId = %d ORDER BY timestamp DESC LIMIT 1&quot; |  |
| 1046 | 57 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 1412 | 13 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 1412 | 13 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 1412 | 20 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $forms_table used in $wpdb-&gt;get_var()\n$forms_table assigned unsafely at line 1409. |  |
| 1412 | 28 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable $forms_table at &quot;SHOW TABLES LIKE &#039;$forms_table&#039;&quot; |  |
| 1414 | 31 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $forms_table used in $wpdb-&gt;get_results()\n$forms_table assigned unsafely at line 1409. |  |
| 1414 | 43 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$forms_table} at &quot;SHOW COLUMNS FROM {$forms_table} LIKE &#039;deleted_at&#039;&quot; |  |
| 1418 | 24 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 1418 | 24 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 1418 | 31 | ERROR | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $query used in $wpdb->get_results()\n$query assigned unsafely at line 1417. |  |
| 1418 | 43 | ERROR | WordPress.DB.PreparedSQL.NotPrepared | Use placeholders and $wpdb->prepare(); found $query |  |
| 1434 | 17 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 1434 | 17 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 1434 | 24 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $legacy_table used in $wpdb-&gt;get_var()\n$legacy_table assigned unsafely at line 1433. |  |
| 1434 | 32 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable $legacy_table at &quot;SHOW TABLES LIKE &#039;$legacy_table&#039;&quot; |  |
| 1435 | 35 | WARNING | PluginCheck.Security.DirectDB.UnescapedDBParameter | Unescaped parameter $legacy_table used in $wpdb-&gt;get_results()\n$legacy_table assigned unsafely at line 1433. |  |
| 1435 | 47 | WARNING | WordPress.DB.PreparedSQL.InterpolatedNotPrepared | Use placeholders and $wpdb-&gt;prepare(); found interpolated variable {$legacy_table} at &quot;SELECT form_id as id, name FROM {$legacy_table}&quot; |  |

## `C:\laragon\www\tom\wp-content\plugins\ab-split-test-lite\modules\journey.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 198 | 16 | ERROR | WordPress.WP.AlternativeFunctions.file_system_operations_mkdir | File operations should use WP_Filesystem methods instead of direct PHP filesystem calls. Found: mkdir(). |  |
| 757 | 35 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 757 | 35 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_POST[&#039;data&#039;] |  |
| 761 | 40 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bb_bt_ab_licence_key&quot;. |  |

## `C:\laragon\www\tom\wp-content\plugins\ab-split-test-lite\modules\session-replay\session-replay.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 159 | 77 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;converted&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 161 | 71 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;device&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 162 | 77 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;date_from&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 163 | 73 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;date_to&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 164 | 75 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;referrer&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 165 | 79 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;utm_source&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 166 | 79 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;utm_medium&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 167 | 83 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;utm_campaign&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 236 | 61 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;uuid&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 237 | 76 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;dates&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 372 | 33 | ERROR | WordPress.WP.AlternativeFunctions.parse_url_parse_url | parse_url() is discouraged because of inconsistency in the output across PHP versions; use wp_parse_url() instead. |  |

## `C:\laragon\www\tom\wp-content\plugins\ab-split-test-lite\modules\public-reports\templates\error-template.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 22 | 1 | ERROR | WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet | Stylesheets must be registered/enqueued via wp_enqueue_style() |  |

## `C:\laragon\www\tom\wp-content\plugins\ab-split-test-lite\modules\public-reports\templates\report-template.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 61 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$test_name&quot;. |  |
| 62 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$test_status&quot;. |  |
| 63 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$test_type&quot;. |  |
| 64 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$test_age&quot;. |  |
| 65 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$created_date&quot;. |  |
| 66 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$total_visits&quot;. |  |
| 67 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$total_conversions&quot;. |  |
| 68 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$overall_rate&quot;. |  |
| 69 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$variations&quot;. |  |
| 70 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$variation_count&quot;. |  |
| 71 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$time_remaining&quot;. |  |
| 72 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$best_variation&quot;. |  |
| 73 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$best_probability&quot;. |  |
| 74 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$test_winner&quot;. |  |
| 75 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$conversion_use_order_value&quot;. |  |
| 76 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$site_name&quot;. |  |
| 77 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$link_data&quot;. |  |
| 78 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$conversion_style&quot;. |  |
| 81 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$status_class&quot;. |  |
| 82 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$status_text&quot;. |  |
| 84 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$status_class&quot;. |  |
| 85 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$status_text&quot;. |  |
| 87 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$status_class&quot;. |  |
| 88 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$status_text&quot;. |  |
| 92 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$currency_symbol&quot;. |  |
| 94 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$currency_symbol&quot;. |  |
| 98 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$control_key&quot;. |  |
| 99 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$control_rate&quot;. |  |
| 103 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$bt_ab_test&quot;. |  |
| 104 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$control_key&quot;. |  |
| 109 | 9 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$control_key&quot;. |  |
| 113 | 13 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$first_key&quot;. |  |
| 114 | 13 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$control_key&quot;. |  |
| 121 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$control_rate&quot;. |  |
| 124 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$control_key_cmp&quot;. |  |
| 125 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$test_winner_cmp&quot;. |  |
| 126 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$best_variation_cmp&quot;. |  |
| 129 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$found_goals&quot;. |  |
| 131 | 32 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$key&quot;. |  |
| 131 | 40 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$value&quot;. |  |
| 133 | 13 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$goal_label&quot;. |  |
| 135 | 17 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$goal_label&quot;. |  |
| 137 | 17 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$goal_label&quot;. |  |
| 139 | 17 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$goal_label&quot;. |  |
| 142 | 17 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$found_goals&quot;. |  |
| 158 | 1 | ERROR | WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet | Stylesheets must be registered/enqueued via wp_enqueue_style() |  |
| 159 | 1 | ERROR | WordPress.WP.EnqueuedResources.NonEnqueuedScript | Scripts must be registered/enqueued via wp_enqueue_script() |  |
| 159 | 1 | ERROR | PluginCheck.CodeAnalysis.Offloading.OffloadedContent | Offloading images, js, css, and other scripts to your servers or any remote service is disallowed. |  |
| 840 | 25 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$revenue_per_visit&quot;. |  |
| 864 | 13 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$winner_label&quot;. |  |
| 865 | 13 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$winner_rate&quot;. |  |
| 866 | 13 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$winner_uplift&quot;. |  |
| 867 | 13 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$performance_multiplier&quot;. |  |
| 869 | 17 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$winner_uplift&quot;. |  |
| 870 | 17 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$performance_multiplier&quot;. |  |
| 874 | 13 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$projected_annual_impact&quot;. |  |
| 875 | 13 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$is_revenue&quot;. |  |
| 878 | 17 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$daily_visits&quot;. |  |
| 879 | 17 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$annual_visits&quot;. |  |
| 883 | 21 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$avg_order_value&quot;. |  |
| 884 | 21 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$control_revenue&quot;. |  |
| 885 | 21 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$projected_annual_impact&quot;. |  |
| 888 | 21 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$daily_conversions&quot;. |  |
| 889 | 21 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$annual_conversions&quot;. |  |
| 890 | 21 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$projected_annual_impact&quot;. |  |
| 926 | 13 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$potential_uplift&quot;. |  |
| 927 | 13 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$performance_multiplier&quot;. |  |
| 928 | 13 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$leader_label&quot;. |  |
| 929 | 13 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$leader_rate&quot;. |  |
| 930 | 13 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$leader_visits&quot;. |  |
| 931 | 13 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$leader_conversions&quot;. |  |
| 934 | 17 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$leader_label&quot;. |  |
| 935 | 17 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$leader_rate&quot;. |  |
| 936 | 17 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$leader_visits&quot;. |  |
| 937 | 17 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$leader_conversions&quot;. |  |
| 940 | 21 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$potential_uplift&quot;. |  |
| 941 | 21 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$performance_multiplier&quot;. |  |
| 946 | 13 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$projected_annual_impact&quot;. |  |
| 947 | 13 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$is_revenue&quot;. |  |
| 950 | 17 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$daily_visits&quot;. |  |
| 951 | 17 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$annual_visits&quot;. |  |
| 955 | 21 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$avg_order_value&quot;. |  |
| 956 | 21 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$control_revenue&quot;. |  |
| 957 | 21 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$projected_annual_impact&quot;. |  |
| 960 | 21 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$daily_conversions&quot;. |  |
| 961 | 21 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$annual_conversions&quot;. |  |
| 962 | 21 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$projected_annual_impact&quot;. |  |
| 1020 | 56 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$key&quot;. |  |
| 1020 | 64 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$goal&quot;. |  |
| 1037 | 56 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$goalNum&quot;. |  |
| 1037 | 68 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$goalLabel&quot;. |  |
| 1044 | 51 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$key&quot;. |  |
| 1044 | 59 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$var&quot;. |  |
| 1046 | 25 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$key_cmp&quot;. |  |
| 1047 | 25 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$is_control&quot;. |  |
| 1048 | 25 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$is_winner&quot;. |  |
| 1049 | 25 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$is_leading&quot;. |  |
| 1052 | 25 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$uplift&quot;. |  |
| 1054 | 29 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$uplift&quot;. |  |
| 1058 | 25 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$conf_value&quot;. |  |
| 1059 | 25 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$conf_class&quot;. |  |
| 1061 | 29 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$conf_class&quot;. |  |
| 1063 | 29 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$conf_class&quot;. |  |
| 1118 | 33 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$display_value&quot;. |  |
| 1127 | 54 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$goalNum&quot;. |  |
| 1127 | 66 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$goalLabel&quot;. |  |
| 1128 | 33 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$goalCount&quot;. |  |
| 1129 | 33 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$goalRate&quot;. |  |
| 1151 | 52 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$key&quot;. |  |
| 1151 | 60 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$goal&quot;. |  |
| 1178 | 47 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$key&quot;. |  |
| 1178 | 55 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$var&quot;. |  |
| 1180 | 21 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$iframe_url&quot;. |  |
| 1181 | 21 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$iframe_url&quot;. |  |
| 1182 | 21 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$iframe_url&quot;. |  |
| 1183 | 21 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$iframe_url&quot;. |  |
| 1191 | 29 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$heatmaps_enabled&quot;. |  |
| 1194 | 33 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$heatmap_url&quot;. |  |
| 1195 | 33 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$heatmap_url&quot;. |  |
| 1196 | 33 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$heatmap_url&quot;. |  |
| 1197 | 33 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$heatmap_url&quot;. |  |
| 1198 | 33 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$heatmap_url&quot;. |  |
| 1199 | 33 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$heatmap_url&quot;. |  |
| 1248 | 13 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$labels_map&quot;. |  |
| 1249 | 37 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$key&quot;. |  |
| 1249 | 45 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$var&quot;. |  |
| 1250 | 17 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$labels_map&quot;. |  |

## `README.md`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 0 | 0 | ERROR | missing_readme_header_tested | The "Tested up to" header is missing in the readme file. | [Docs](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/#readme-header-information) |
| 0 | 0 | ERROR | no_license | Missing "License". Please update your readme with a valid GPLv2 (or later) compatible license. | [Docs](https://developer.wordpress.org/plugins/wordpress-org/common-issues/#no-gpl-compatible-license-declared) |
| 0 | 0 | ERROR | no_stable_tag | Invalid or missing Stable Tag. Your Stable Tag is meant to be the stable version of your plugin and it needs to be exactly the same with the Version in your main plugin file's header. Any mismatch can prevent users from downloading the correct plugin files from WordPress.org. | [Docs](https://developer.wordpress.org/plugins/wordpress-org/common-issues/#incorrect-stable-tag) |
| 0 | 0 | WARNING | readme_parser_warnings_no_short_description_present | The "Short Description" section is missing. An excerpt was generated from your main plugin description. |  |

## `modules/page-redirect/includes/frontend.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 0 | 0 | ERROR | missing_direct_file_access_protection | PHP file should prevent direct access. Add a check like: if ( ! defined( 'ABSPATH' ) ) exit; | [Docs](https://developer.wordpress.org/plugins/wordpress-org/common-issues/#direct-file-access) |

## `modules/page-redirect/page-redirect.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 0 | 0 | ERROR | missing_direct_file_access_protection | PHP file should prevent direct access. Add a check like: if ( ! defined( 'ABSPATH' ) ) exit; | [Docs](https://developer.wordpress.org/plugins/wordpress-org/common-issues/#direct-file-access) |

## `.windsurf`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 0 | 0 | WARNING | ai_instruction_directory | AI instruction directory ".windsurf" detected. These directories should not be included in production plugins. |  |

## `agents.md`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 0 | 0 | WARNING | unexpected_markdown_file | Unexpected markdown file "agents.md" detected in plugin root. Only specific markdown files are expected in production plugins. |  |

## `gotchas.md`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 0 | 0 | WARNING | unexpected_markdown_file | Unexpected markdown file "gotchas.md" detected in plugin root. Only specific markdown files are expected in production plugins. |  |

## `implementation_details.md`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 0 | 0 | WARNING | unexpected_markdown_file | Unexpected markdown file "implementation_details.md" detected in plugin root. Only specific markdown files are expected in production plugins. |  |

## `mental_model.md`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 0 | 0 | WARNING | unexpected_markdown_file | Unexpected markdown file "mental_model.md" detected in plugin root. Only specific markdown files are expected in production plugins. |  |

## `quick_reference.md`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 0 | 0 | WARNING | unexpected_markdown_file | Unexpected markdown file "quick_reference.md" detected in plugin root. Only specific markdown files are expected in production plugins. |  |

## `styleguide.md`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 0 | 0 | WARNING | unexpected_markdown_file | Unexpected markdown file "styleguide.md" detected in plugin root. Only specific markdown files are expected in production plugins. |  |

## `bt-bb-ab.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 0 | 0 | WARNING | plugin_header_nonexistent_domain_path | The "Domain Path" header in the plugin file must point to an existing folder. Found: "languages" | [Docs](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#domain-path) |

## `C:\laragon\www\tom\wp-content\plugins\ab-split-test-lite\modules\conversion\conversion.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 40 | 21 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 40 | 21 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotValidated | Detected usage of a possibly undefined superglobal array index: $_POST[&#039;eid&#039;]. Check that the array index exists before using it. |  |
| 41 | 40 | WARNING | WordPress.Security.NonceVerification.Missing | Processing form data without nonce verification. |  |
| 41 | 40 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotValidated | Detected usage of a possibly undefined superglobal array index: $_POST[&#039;variation&#039;]. Check that the array index exists before using it. |  |
| 41 | 40 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;variation&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 46 | 19 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bt_log_experiment_activity&quot;. |  |
| 129 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$bt_conversion_module&quot;. |  |
| 135 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound | Classes declared by a theme/plugin should start with the theme/plugin prefix. Found: &quot;Bt_BB_ConversionModule&quot;. |  |
| 142 | 43 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bt_bb_ab_conversion_category&quot;. |  |
| 143 | 43 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bt_bb_ab_conversion_group&quot;. |  |

## `C:\laragon\www\tom\wp-content\plugins\ab-split-test-lite\modules\public-reports\public-reports.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 84 | 36 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 85 | 42 | WARNING | WordPress.Security.NonceVerification.Recommended | Processing form data without nonce verification. |  |
| 85 | 42 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_GET[&#039;abst_report&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 180 | 18 | WARNING | WordPress.DB.DirectDatabaseQuery.DirectQuery | Use of a direct database call is discouraged. |  |
| 180 | 18 | WARNING | WordPress.DB.DirectDatabaseQuery.NoCaching | Direct database call without caching detected. Consider using wp_cache_get() / wp_cache_set() or wp_cache_delete(). |  |
| 404 | 38 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;token&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |

## `C:\laragon\www\tom\wp-content\plugins\ab-split-test-lite\modules\support\agency_hub.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 18 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound | Classes declared by a theme/plugin should start with the theme/plugin prefix. Found: &quot;BT_BB_AB_Agency_Hub&quot;. |  |
| 113 | 54 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_POST[&#039;key&#039;] |  |

## `C:\laragon\www\tom\wp-content\plugins\ab-split-test-lite\modules\support\support.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 67 | 26 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotValidated | Detected usage of a possibly undefined superglobal array index: $_POST[&#039;nonce&#039;]. Check that the array index exists before using it. |  |
| 67 | 26 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;nonce&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 67 | 26 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_POST[&#039;nonce&#039;] |  |
| 71 | 12 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotValidated | Detected usage of a possibly undefined superglobal array index: $_POST[&#039;data&#039;]. Check that the array index exists before using it. |  |
| 71 | 12 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;data&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 71 | 12 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_POST[&#039;data&#039;] |  |
| 84 | 26 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotValidated | Detected usage of a possibly undefined superglobal array index: $_POST[&#039;nonce&#039;]. Check that the array index exists before using it. |  |
| 84 | 26 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;nonce&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 84 | 26 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_POST[&#039;nonce&#039;] |  |
| 88 | 12 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotValidated | Detected usage of a possibly undefined superglobal array index: $_POST[&#039;data&#039;]. Check that the array index exists before using it. |  |
| 88 | 12 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;data&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 88 | 12 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_POST[&#039;data&#039;] |  |
| 206 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$bt_bb_ab_support&quot;. |  |

## `C:\laragon\www\tom\wp-content\plugins\ab-split-test-lite\testideas.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 45 | 34 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;title&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 45 | 53 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;page&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 46 | 43 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;hypothesis&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 75 | 79 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;page&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 76 | 86 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;problem&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 77 | 79 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[&#039;nextstep&#039;] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 80 | 16 | WARNING | WordPress.Security.ValidatedSanitizedInput.MissingUnslash | $_POST[$score_key] not unslashed before sanitization. Use wp_unslash() or similar |  |
| 80 | 16 | WARNING | WordPress.Security.ValidatedSanitizedInput.InputNotSanitized | Detected usage of a non-sanitized input variable: $_POST[$score_key] |  |

## `C:\laragon\www\tom\wp-content\plugins\ab-split-test-lite\includes\class-absplittest-cli.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 13 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound | Classes declared by a theme/plugin should start with the theme/plugin prefix. Found: &quot;ABSPLITTEST_CLI&quot;. |  |
| 312 | 48 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;ab_min_visits_for_winner&quot;. |  |

## `C:\laragon\www\tom\wp-content\plugins\ab-split-test-lite\includes\statistics.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 11 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bt_bb_ab_split_test_analyzer&quot;. |  |
| 24 | 42 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;ab_min_visits_for_winner&quot;. |  |
| 39 | 38 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;ab_complete_confidence&quot;. |  |
| 117 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bt_bb_ab_revenue_analyzer&quot;. |  |
| 170 | 42 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;ab_min_visits_for_winner&quot;. |  |
| 310 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;random_normal&quot;. |  |
| 328 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bt_bb_ab_analyze_device_sizes&quot;. |  |
| 387 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;normal_cdf&quot;. |  |

## `C:\laragon\www\tom\wp-content\plugins\ab-split-test-lite\modules\conversion\includes\frontend.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 14 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$eid&quot;. |  |
| 16 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$selector&quot;. |  |
| 19 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$experiment&quot;. |  |
| 24 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$eid&quot;. |  |
| 26 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$selector&quot;. |  |
| 59 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$bt_conversion_vars&quot;. |  |

## `C:\laragon\www\tom\wp-content\plugins\ab-split-test-lite\modules\page-redirect\includes\frontend.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 5 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$eid&quot;. |  |
| 8 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$experiment&quot;. |  |
| 9 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$redirect&quot;. |  |
| 15 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$eid&quot;. |  |

## `C:\laragon\www\tom\wp-content\plugins\ab-split-test-lite\modules\page-redirect\page-redirect.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 5 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound | Classes declared by a theme/plugin should start with the theme/plugin prefix. Found: &quot;BT_BB_AB_PageRedirect&quot;. |  |
| 65 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$BT_BB_AB_PageRedirect&quot;. |  |
| 70 | 5 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound | Classes declared by a theme/plugin should start with the theme/plugin prefix. Found: &quot;BT_BB_AB_PageRedirectModule&quot;. |  |
| 77 | 51 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bt_bb_ab_page_redirect_category&quot;. |  |
| 78 | 51 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bt_bb_ab_page_redirect_group&quot;. |  |

## `C:\laragon\www\tom\wp-content\plugins\ab-split-test-lite\modules\support\bricks\bricks.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 138 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$bt_bb_ab_bricks&quot;. |  |

## `C:\laragon\www\tom\wp-content\plugins\ab-split-test-lite\modules\support\bricks\elements\conversion.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 4 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound | Classes declared by a theme/plugin should start with the theme/plugin prefix. Found: &quot;Element_AB_Conversion&quot;. |  |

## `C:\laragon\www\tom\wp-content\plugins\ab-split-test-lite\modules\support\cache.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 7 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;ab_exclude_js&quot;. |  |
| 60 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bt_ab_add_cfasync_to_script&quot;. |  |

## `C:\laragon\www\tom\wp-content\plugins\ab-split-test-lite\modules\support\elementor.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 77 | 37 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bt_experiments_get_items&quot;. |  |
| 116 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$bt_bb_ab_elementor&quot;. |  |
| 125 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound | Classes declared by a theme/plugin should start with the theme/plugin prefix. Found: &quot;BT_Elementor_Conversion&quot;. |  |
| 160 | 37 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bt_experiments_get_items&quot;. |  |
| 252 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound | Functions declared in the global namespace by a theme/plugin should start with the theme/plugin prefix. Found: &quot;ab_add_attributes_to_element&quot;. |  |
| 279 | 3 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound | Classes declared by a theme/plugin should start with the theme/plugin prefix. Found: &quot;BT_Elementor_AB_Redirect&quot;. |  |
| 314 | 37 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bt_experiments_get_items&quot;. |  |

## `C:\laragon\www\tom\wp-content\plugins\ab-split-test-lite\modules\support\gutenberg.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 100 | 63 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bt_experiments_get_items&quot;. |  |
| 101 | 51 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bt_experiments_conversion_html&quot;. |  |
| 130 | 63 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bt_experiments_get_items&quot;. |  |
| 131 | 51 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound | Hook names invoked by a theme/plugin should start with the theme/plugin prefix. Found: &quot;bt_experiments_ab_page_redirect_html&quot;. |  |
| 148 | 1 | WARNING | WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound | Global variables defined by a theme/plugin should start with the theme/plugin prefix. Found: &quot;$bt_bb_ab_gutenberg&quot;. |  |
