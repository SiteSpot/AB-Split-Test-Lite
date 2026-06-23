=== AB Split Test Lite – Self-Hosted A/B Testing, Heatmaps & AI Agent (MCP) Support ===
Contributors: tomcarless
Donate link: https://absplittest.com
Tags: a/b testing, split testing, conversion optimization, heatmap, woocommerce
Requires at least: 6.9
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Self-hosted A/B & split testing for WordPress. Unlimited traffic, every page builder, AI-agent (MCP) + REST control, heatmaps & replays. No SaaS.

== Description ==

**A/B testing that runs entirely on your own WordPress site. No cloud account. No traffic meter. No data leaving your server.**

[AB Split Test](https://absplittest.com) is a fully **self-hosted** A/B and split testing plugin for WordPress. It's new to the WordPress.org directory — but it isn't new software. It's been refined across 170+ releases since 2019, runs on thousands of websites, and has helped surface millions of dollars in revenue those sites would otherwise have left on the table. Every test, every visitor, and every result is stored in your own database — not on someone else's servers. That single design choice changes everything: there is **no monthly "tested page view" cap**, no per-visitor pricing, and no privacy trade-off. Run as many visitors through your tests as your site can serve.

Most A/B testing plugins are really just front-ends for a paid SaaS. They route your traffic through their cloud, meter your page views, and lock real volume behind expensive tiers. AB Split Test does the opposite. The engine lives inside WordPress, so your tests scale with your hosting — not with someone's invoice.

= ⚡ Why AB Split Test is different =

* **Self-hosted, always.** Tests, tracking, and reporting all run on your server. Your visitor data never leaves your site.
* **Unlimited traffic.** No "tested page view" limit and no per-visitor billing. Test a landing page that gets 1,000 views a month or 10,000,000 — the plugin doesn't care.
* **Built for AI agents.** A native **MCP (Model Context Protocol) server** and a matching **REST API** ship in the free version. Connect Claude, Cursor, or any MCP-compatible agent and it can spin up, launch, and read A/B tests on your site in seconds — for free.
* **Works with every builder.** Test Pages, Gutenberg Blocks, Elementor, Bricks, Beaver Builder, Oxygen, Breakdance, WP Bakery, and more — *inside* the builder, not around it.
* **Privacy-first by design.** No fingerprinting, no personal data collection, no third-party cloud. GDPR- and CCPA-friendly out of the box.
* **Ruthlessly cache-friendly.** First-class compatibility with WP Rocket, NitroPack, LiteSpeed, SiteGround Optimizer, and other caching/optimization plugins.

> "AB Split Test is invaluable, and more so that it is not a SaaS! 100% worth it!" — *Admin, ragallo.com*

= 🤖 The first A/B testing plugin your AI agents can actually drive =

This is what no SaaS competitor gives you for free: **programmatic, self-hosted test control**.

AB Split Test registers a set of WordPress Abilities that are automatically exposed as **MCP tools** the moment you install the official WordPress MCP Adapter. The exact same actions are also available over a clean **REST API**. Both are included in the free version.

Your AI agent can:

* `create-test` — create a new A/B test or a lightweight test idea
* `list-tests` — list existing tests and their status
* `get-test-details` — read a test's full configuration
* `get-test-results` — pull conversion data and statistical significance
* `update-test-status` — start, pause, or complete a test
* `update-test-settings` — change goals, targeting, and variations

That means an agent can read a page, propose a hypothesis, build a point-and-click variation, launch the test, and report back on the winner — without you touching the dashboard. On your own infrastructure, with your own data, at no cost.

= 🎯 Two ways to build a test =

**Point-and-click mode.** Open any page in the visual **Magic Bar**, click the headline, button, image, or section you want to test, type your variations, and go. No selectors to hunt for, no code to write.

> "Makes A/B testing as simple as can be — it's truly point-and-click easy with the WordPress plugin." — *Verified User, Legal Services*

**Full-page mode.** Prefer to test two completely different designs? Use full-page (split-URL) tests to send traffic between entirely separate pages and measure which one converts.

You also get **code/CSS tests** for design tweaks and **on-page variation tests** built right inside your editor.

= 📊 What you can test =

* Headlines, sub-headlines, and body copy
* Buttons and calls-to-action
* Images and hero sections
* Pricing and product copy
* Entire page layouts (full-page / split-URL)
* Custom CSS and design changes
* Anything you can point at with the Magic Bar

= 🎯 Conversion goals that match your business =

Track exactly what matters: element/button clicks, link clicks, page visits, destination URLs, time on page, scroll depth, text appearance, and **form submissions** from the form plugin you already use — Contact Form 7, WPForms, Gravity Forms, Fluent Forms, Ninja Forms, Formidable, Forminator, SureForms, Jet Form Builder, MailPoet, and the native forms in Elementor, Bricks, Breakdance, and Beaver Builder.

= 🔥 Heatmaps, session replays & visitor journeys =

See *why* a variation wins. AB Split Test includes heatmap tracking and **session replays** that reconstruct real visitor journeys — cursor movement, clicks, rage-clicks, and scroll behaviour — straight from your own server. (In the free version, heatmap tracking covers one page with a 3-day retention window; upgrade for more pages and longer history.)

= 📈 Trustworthy results =

Results are scored with proper statistical-significance analysis so you know when a winner is really a winner — not just noise. When a test reaches a confident result, the dashboard tells you, and you can apply the winner with confidence (and a little confetti).

= 🧩 Plays nicely with your stack =

* **Page builders:** Pages, Blocks (Gutenberg), Elementor, Bricks, Beaver Builder, Oxygen, Breakdance, WP Bakery, and more.
* **Caching & optimization:** WP Rocket, NitroPack, LiteSpeed Cache, SiteGround Optimizer, and others — variation scripts are automatically excluded from optimization so tests don't flicker or break.
* **Forms:** all the major form plugins listed above.
* **WooCommerce:** track order-based conversions (revenue-weighted optimization is a Pro feature).

= 🔒 Privacy & performance =

Because nothing is offloaded to a third-party cloud, there's no personal data shipped off your site, no device fingerprinting, and no persistent cross-site tracking. AB Split Test is designed to sit comfortably inside GDPR and CCPA workflows. The front-end footprint is intentionally small and cache-aware, so testing doesn't slow your site down.

= ⭐ Loved by WordPress professionals =

This plugin is new to WordPress.org, but its users aren't:

> "We started with VWO and then Optimizely but we needed something simpler for our team and something more native to WordPress. This plus the support checked all the boxes! It's stuff like that that we wouldn't be able to do in the crazy expensive tools!" — *Aaron Stanley, Bryant Stratton College*

> "Dead simple to set up. I've paid thousands more annually on more complex tools to do the same things. Do yourself a favor, you won't regret it!" — *Ryan Waterbury, onedog.solutions*

> "Smooth, super powerful and easy to integrate. It runs fast, has separate tables, the db is not clogged, and it runs smoothly even on cheap hosting. If you're a beginner: buy it. If you're a pro: buy it." — *digitalfastmind.com*

> "As a WordPress developer, this plugin immediately stood out. It runs fast without bloating the site and integrates cleanly into a standard WordPress workflow. A solid product built by people who clearly understand WordPress." — *Stacey W., CodeInk*

> "The only platform you need to run A/B tests! We run hundreds (potentially thousands) of A/B tests, and Split helps us easily determine which changes lead to the biggest gains in conversion." — *Verified User, Health, Wellness & Fitness*

> "Works well and makes me more money." — *Christian, german-stories.com*

= 🆓 Free vs Pro =

The free version (AB Split Test Lite) is genuinely useful on its own — and unlike the SaaS competition, it never meters your traffic.

**Included free, forever:**

* 1 active A/B test (A vs B)
* Point-and-click (Magic Bar), full-page, on-page, and CSS test types
* **Unlimited traffic / unlimited tested views**
* **MCP server + REST API** for AI-agent and programmatic control
* All page builders and form integrations
* Conversion goals (clicks, URLs, pages, time, scroll, text, forms)
* Heatmaps & session replays (1 page, 3-day retention)
* Statistical significance analysis
* Cache-plugin compatibility
* Self-hosted, privacy-first architecture

**Pro unlocks:**

* Unlimited active tests and unlimited variations
* **AI copilot** — AI-generated test ideas (grounded in behavioural-psychology principles), headline/copy rewriting, and full-page conversion analysis
* Multi-armed bandit auto-optimization (Thompson sampling)
* Revenue / order-value tracking for WooCommerce & EDD
* Sub-goals and automatic winner deployment
* Webhooks and scheduled email reports
* Public, shareable result reports
* Agency Hub for managing tests across many client sites
* WP-CLI commands for scripted test management
* Extended heatmap pages and retention

[See all Pro features →](https://absplittest.com)

= 🆚 AB Split Test vs. cloud-based A/B tools =

Cloud (SaaS) A/B testing plugins route your traffic through their servers, meter your page views, and charge more as your traffic grows. AB Split Test is built the opposite way:

* **Hosting:** runs entirely on your server vs. routed through a third-party cloud.
* **Free traffic limit:** unlimited vs. a few hundred to a few thousand metered views.
* **Your visitor data:** never leaves your site vs. sent to their cloud.
* **AI-agent (MCP) control:** included free vs. rarely offered, or paid.
* **REST API:** included free vs. limited or paid.
* **Heatmaps & session replays:** built in vs. often a paid add-on.
* **Ongoing cost:** none vs. a bill that scales with your traffic.

== Installation ==

1. In your WordPress admin, go to **Plugins → Add New**.
2. Search for **AB Split Test**.
3. Click **Install Now**, then **Activate**.
4. Open **Split Test** in the admin menu and create your first test.
5. To run a test from the page itself, open the target page and use the **Magic Bar** point-and-click editor.

**Manual install:**

1. Download the plugin ZIP.
2. Go to **Plugins → Add New → Upload Plugin** and choose the ZIP.
3. Click **Install Now**, then **Activate**.

**Enable AI-agent (MCP) control (optional):**

1. Install and activate the official **WordPress MCP Adapter** plugin (AB Split Test can prompt you to install it from its Settings → MCP tab).
2. Connect your MCP-compatible client (e.g. Claude) to your site.
3. The `absplittest/*` tools become available immediately. The same actions are also reachable via the REST API under `/wp-json/bt-bb-ab/v1/`.

== Frequently Asked Questions ==

= Is AB Split Test really self-hosted? =

Yes. The testing engine, visitor tracking, and reporting all run inside your WordPress install and store data in your own database. There is no required cloud account and no SaaS backend in the loop. Your visitor data stays on your server.

= Is there a limit on traffic or "tested page views"? =

No. Because the plugin is self-hosted, there is no page-view meter and no per-visitor billing. Your tests scale with your hosting. This is the single biggest difference from cloud-based A/B testing tools, which cap free usage at a few hundred or a few thousand views.

= How do AI agents control my tests for free? =

AB Split Test registers WordPress Abilities that the official WordPress MCP Adapter exposes as MCP tools, and it ships a matching REST API. Both are in the free version. A connected agent can create tests, list them, read results, and start/pause/complete them — all on your own infrastructure, at no cost.

= Does it work with my page builder? =

Almost certainly. AB Split Test supports Pages, Gutenberg Blocks, Elementor, Bricks, Beaver Builder, Oxygen, Breakdance, WP Bakery, and more — and it works *inside* those builders, not just around them.

= Will it slow down my site or fight my caching plugin? =

No. The front-end code is lightweight, and the plugin automatically marks its variation scripts as excluded from optimization for major caching/optimization plugins (WP Rocket, NitroPack, LiteSpeed, SiteGround Optimizer, and others) so your tests render cleanly without flicker.

= Is it GDPR / CCPA friendly? =

It's designed to be. Nothing is offloaded to a third-party cloud, there's no device fingerprinting, and no personal data is collected by default, which makes it straightforward to use within GDPR and CCPA requirements.

= How many tests can I run on the free version? =

The free version runs one active test (A vs B) at a time with unlimited traffic. Upgrade to Pro for unlimited active tests and unlimited variations.

= Do I need to know how to code? =

No. The Magic Bar lets you build tests by clicking elements on the page. Developers who *want* code-level control get a REST API, MCP tools, and (in Pro) WP-CLI.

= Can I track form submissions or WooCommerce conversions? =

Yes. You can set conversion goals on submissions from all major form plugins, and track WooCommerce order-based conversions. Revenue-weighted (order-value) optimization is a Pro feature.

= Where can I get help or report a bug? =

Visit [absplittest.com](https://absplittest.com) for documentation, or use the [WordPress.org support forum](https://wordpress.org/support/plugin/ab-split-test-lite/).

== Screenshots ==

1. The dashboard — all your tests and their status at a glance.
2. Point-and-click Magic Bar — select any element on the page and create variations instantly.
3. Test results with conversion rates and statistical significance.
4. Heatmaps and session replays reconstructed from your own visitor data.
5. MCP / Settings tab — connect AI agents to control tests programmatically.
6. Creating a test: choose point-and-click, full-page, on-page, or CSS test types.

== Changelog ==

= 1.0.0 =
* First WordPress.org release of AB Split Test Lite (the 171st update to AB Split Test since 2019 — full history at https://absplittest.com/changelog).
* Self-hosted A/B, full-page (split-URL), on-page, and CSS test types.
* Point-and-click Magic Bar visual editor.
* Native MCP server (via the WordPress Abilities API) and REST API for AI-agent and programmatic test control.
* Conversion goals: element clicks, links, page visits, URLs, time on page, scroll depth, text, and form submissions across all major form plugins.
* Heatmaps and session replays.
* Statistical significance analysis.
* Page builder support: Pages, Gutenberg, Elementor, Bricks, Beaver Builder, Oxygen, Breakdance, WP Bakery, and more.
* Cache/optimization compatibility (WP Rocket, NitroPack, LiteSpeed, SiteGround Optimizer, and others).
* Privacy-first, self-hosted architecture with no third-party cloud.

== Upgrade Notice ==

= 1.0.0 =
First public release of AB Split Test Lite: self-hosted A/B testing with unlimited traffic, AI-agent (MCP) control, point-and-click editing, heatmaps, and session replays.