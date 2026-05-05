# Plugin Check Report

**Plugin:** AB Split Test Lite
**Generated at:** 2026-05-04 17:14:38


## `C:\laragon\www\tom\wp-content\plugins\ab-split-test-lite\bt-bb-ab.php`

| Line | Column | Type | Code | Message | Docs |
| --- | --- | --- | --- | --- | --- |
| 11143 | 34 | ERROR | WordPress.WP.EnqueuedResources.NonEnqueuedScript | Scripts must be registered/enqueued via wp_enqueue_script() |  |
| 11203 | 55 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '"';\r\n'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 12287 | 40 | ERROR | WordPress.WP.I18n.MissingTranslatorsComment | A function call to _n_noop() with texts containing placeholders was found, but was not accompanied by a "translators:" comment on the line above to clarify the meaning of the placeholders. | [Docs](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#descriptions) |
| 12309 | 40 | ERROR | WordPress.WP.I18n.MissingTranslatorsComment | A function call to _n_noop() with texts containing placeholders was found, but was not accompanied by a "translators:" comment on the line above to clarify the meaning of the placeholders. | [Docs](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#descriptions) |
| 13039 | 40 | ERROR | PluginCheck.CodeAnalysis.EnqueuedResourceOffloading.OffloadedContent | Found call to wp_enqueue_style() with external resource. Offloading styles to your servers or any remote service is disallowed. |  |
| 13041 | 40 | ERROR | PluginCheck.CodeAnalysis.EnqueuedResourceOffloading.OffloadedContent | Found call to wp_enqueue_script() with external resource. Offloading scripts to your servers or any remote service is disallowed. |  |
| 19962 | 84 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$message'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 21330 | 71 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$idea'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 21332 | 75 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$complete'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 21334 | 19 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found '$label'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 21342 | 112 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'BT_AB_TEST_WL_ABTEST'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
| 22158 | 15 | ERROR | WordPress.Security.EscapeOutput.OutputNotEscaped | All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'BT_AB_TEST_WL_ABTEST'. | [Docs](https://developer.wordpress.org/apis/security/escaping/#escaping-functions) |
