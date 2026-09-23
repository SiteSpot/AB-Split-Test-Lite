=== AB Split Test – A/B Testing, Heatmaps, Session Replay & MCP for AI Agents ===
Contributors: tomsitespot
Donate link: https://absplittest.com
Tags: a/b testing, split testing, conversion optimization, heatmap, mcp
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A/B testing and split testing for WordPress, self-hosted. Unmetered traffic, heatmaps on every page, page-builder controls, MCP + REST.

== Description ==

**A/B testing that runs entirely on your own WordPress site. No cloud account. No traffic meter. No visitor data leaving your server.**

[AB Split Test](https://absplittest.com) is a fully **self-hosted** A/B and split testing plugin for WordPress. It is the first A/B testing plugin built on the WordPress **Abilities API**, so AI agents such as Claude Code, Cursor, and Windsurf can create and run tests over **MCP (Model Context Protocol)** in the free version. It is new to the WordPress.org directory, but it is not new software. It has been refined across 170+ releases since 2019 and runs on thousands of websites.

Every test, every visit, and every result is stored in your own database. That one design choice changes everything. There is **no monthly "tested page view" cap**, no per-visitor pricing, and no privacy trade-off. Run as many visitors through your test as your site can serve.

Most A/B testing plugins are front-ends for a paid SaaS. They route your tracking data through their cloud, meter your page views, and lock real volume behind expensive tiers. AB Split Test does the opposite. The engine lives inside WordPress, so your tests scale with your hosting, not with someone else's invoice.

= Why AB Split Test is different =

* **Self-hosted, always.** Tests, tracking, and reporting all run on your server. Every visit, click, and conversion lives in your own database.
* **Unmetered traffic.** No "tested page view" limit and no per-visitor billing. A page with 1,000 views a month and a page with 10,000,000 cost the same: nothing.
* **Heatmaps and session replays on every page, free.** No page picker and no quota. The free version records across your whole site and keeps a rolling 3-day window.
* **Built for AI agents.** A native **MCP (Model Context Protocol)** integration and a matching **REST API** ship in the free version. Connect Claude, Cursor, or any MCP client and it can create, launch, and read tests on your site.
* **Real page-builder integrations.** Elementor, Bricks, Beaver Builder, Oxygen, Breakdance, WP Bakery, and Gutenberg all get native test controls inside the builder itself.
* **Privacy-first by design.** No device fingerprinting and no third-party cloud. Visitors are assigned to variations with first-party cookies on your own domain.
* **Cache-friendly.** Test scripts are marked for exclusion in WP Rocket, NitroPack, LiteSpeed Cache, SiteGround Optimizer, and other optimization plugins, so tests don't flicker or break.

> "AB Split Test is invaluable, and more so that it is not a SaaS! 100% worth it!" — *Admin, ragallo.com*

= The A/B testing plugin your AI agent can drive =

AB Split Test registers WordPress Abilities. Install the official WordPress MCP Adapter and they appear as **MCP tools** straight away. The same actions are available over the **REST API**. Both are included free.

Your AI agent can use:

* `create-test` — create an A/B test or save a test idea
* `list-tests` — list tests and their status
* `get-test-details` — read a test's full configuration
* `get-test-results` — pull visits, conversions, and statistical confidence
* `update-test-status` — start, pause, or complete a test
* `update-test-settings` — change the goal, targeting, and variations
* `get-heatmap-data` — read aggregated click and scroll data for a page
* `list-heatmap-pages` — list the pages that have heatmap data

An agent can read a page, propose a hypothesis, build a point-and-click variation, launch the test, and report back on the result, without you opening the dashboard. It all happens on your own infrastructure, with your own data.

= Four ways to build a test =

**Point-and-click (Magic Bar).** Open any page, click the headline, button, image, or section you want to test, type your variation, and go. No selectors to hunt for and no code to write.

> "Makes A/B testing as simple as can be — it's truly point-and-click easy with the WordPress plugin." — *Verified User, Legal Services*

**Full-page (split URL).** Send traffic between two completely different pages and measure which one converts.

**On-page.** Tag sections or blocks as variations inside your page builder or the block editor.

**CSS.** Test design changes by applying a stylesheet class to the variation.

= What you can test =

* Headlines, sub-headlines, and body copy
* Buttons and calls to action
* Images and hero sections
* Pricing and product copy
* Entire page layouts
* Design and CSS changes
* Anything you can click on with the Magic Bar

= Page-builder integrations that live inside the builder =

Many testing tools say they are "compatible" with page builders, which usually means they tolerate them. AB Split Test adds **native controls inside each builder**, so you build variations in the interface you already design in:

* **Elementor** — tag any element as a test variation in the Elementor editor.
* **Bricks** — in-editor variation tagging plus a native Bricks conversion element.
* **Breakdance** — a "Split Test" settings section on every element, plus a conversion element.
* **Beaver Builder** — split-test settings in row and module settings.
* **Oxygen** — split-test options in component options.
* **WP Bakery** — test and variation controls on rows and sections.
* **Gutenberg** — split-test attributes on every block, managed from the block editor.

No shadow editor and no fragile selector mapping to an external tool. Your builder is the test editor.

= Conversion goals =

Pick the action that counts as a win:

* Page or post visit (for example, your thank-you page)
* URL visited, with `*` wildcards
* Element clicked
* Link clicked
* Text visible on the page
* Scroll depth
* Time active on the page
* Conversion element visible (a block, module, or element you place)
* Custom JavaScript event
* WooCommerce "order received" page, when WooCommerce is active

Native form-plugin submission goals and purchase tracking with order values are part of Pro.

= Heatmaps, session replays, and visitor journeys =

See *why* a variation wins. Heatmaps show where visitors click and how far they scroll. Session replays reconstruct real visits: cursor movement, clicks, and scrolling. Everything is recorded and stored on your own server, on **every page of your site**. The free version keeps a rolling 3-day window. Pro keeps longer history.

= Trustworthy results =

Results use Bayesian statistical analysis, so you know when a winner is really a winner and not just noise. Filter results by device to see how each variation performs. When a test reaches a confident result, the dashboard tells you.

= Loved by WordPress professionals =

This plugin is new to WordPress.org, but its users aren't:

> "We started with VWO and then Optimizely but we needed something simpler for our team and something more native to WordPress. This plus the support checked all the boxes! It's stuff like that that we wouldn't be able to do in the crazy expensive tools!" — *Aaron Stanley, Bryant Stratton College*

> "Dead simple to set up. I've paid thousands more annually on more complex tools to do the same things. Do yourself a favor, you won't regret it!" — *Ryan Waterbury, onedog.solutions*

> "Smooth, super powerful and easy to integrate. It runs fast, has separate tables, the db is not clogged, and it runs smoothly even on cheap hosting. If you're a beginner: buy it. If you're a pro: buy it." — *digitalfastmind.com*

> "As a WordPress developer, this plugin immediately stood out. It runs fast without bloating the site and integrates cleanly into a standard WordPress workflow. A solid product built by people who clearly understand WordPress." — *Stacey W., CodeInk*

> "Works well and makes me more money." — *Christian, german-stories.com*

= Free vs Pro =

The free version is useful on its own, and it never meters your traffic.

**Included free:**

* 1 active A/B test at a time (control + 1 variation)
* Point-and-click, full-page, on-page, and CSS test types
* **Unmetered traffic**
* **MCP tools and REST API** for AI agents and scripts
* Native controls in every supported page builder
* Click, link, page, URL, scroll, time, text, conversion-element, and JavaScript goals
* Heatmaps and session replays on every page (3-day retention)
* Bayesian statistical analysis with device breakdown
* Cache-plugin compatibility
* Self-hosted, privacy-first architecture

**Pro adds:**

* Unlimited active tests and unlimited variations
* Form submission goals for Contact Form 7, Gravity Forms, WPForms, Fluent Forms, and more
* Purchase and revenue tracking for WooCommerce, Easy Digital Downloads, and FluentCart
* Sub-goals for multi-step funnels
* Automatic winner selection and multi-armed bandit optimization
* Audiences with UTM, referrer, and location targeting, plus results segmented by audience
* Page analytics: visits, active time, and scroll depth per page
* AI test ideas and an optimization hub
* Webhooks, scheduled email reports, and shareable result reports
* WP-CLI commands
* Longer heatmap and session replay retention

= Try the full version free for 7 days =

Want unlimited tests, AI test ideas, and everything else in the Pro list above? Get the free full version from absplittest.com. It includes a **7-day trial of every paid feature, plus AI credits**. No credit card needed.

**Upgrading keeps everything.** Your tests, results, and settings carry over. Install the full version and activate it. Lite steps aside on its own, and you pick up exactly where you left off.

[Get the free full version and 7-day trial](https://absplittest.com/repo-up/?utm_source=wporg-lite&utm_medium=readme&utm_campaign=free-vs-pro)

= AB Split Test vs cloud-based A/B tools =

Cloud A/B testing plugins send your tracking data to their servers, meter your page views, and charge more as your traffic grows. AB Split Test is built the other way:

* **Hosting:** runs on your server, not a third-party cloud.
* **Free traffic limit:** none, instead of a few hundred to a few thousand metered views.
* **Visitor data:** stays in your database.
* **AI-agent (MCP) control:** included free.
* **REST API:** included free.
* **Heatmaps and session replays:** built in.
* **Ongoing cost:** none for the free version, and no bill that grows with traffic.

= AB Split Test vs Nelio A/B Testing =

Nelio is the best-known A/B testing plugin in the directory, so it's the comparison people ask about most. The difference is architectural:

* **Where your data lives.** Nelio's documentation says tests and variants are stored in WordPress, while tracking data (views, conversions, and heatmaps) is sent to and processed in Nelio's cloud on AWS. With AB Split Test, the engine and the data both run on your server.
* **Account requirement.** Nelio needs a Nelio account and cloud plan. AB Split Test needs no external account.
* **Metered traffic.** Nelio meters "tested page views" on every plan, and its free plan includes 500 per month. AB Split Test has no meter on any plan.
* **Heatmaps.** AB Split Test's free version records heatmaps and session replays on every page, stored locally, with a 3-day window.
* **What's the same?** Both free plans run one active test at a time. The difference is what happens when your traffic grows.

= Help translate =

AB Split Test is translation-ready. Contribute a translation for your language at [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/ab-split-test-lite/).

== Installation ==

1. In your WordPress admin, go to **Plugins → Add New Plugin**.
2. Search for **AB Split Test Lite**.
3. Click **Install Now**, then **Activate**.
4. Open **Split Test** in the admin menu and create your first test.
5. To test an element on a page, open the page on the front end and launch the **Magic Bar** editor from the admin bar.

**Enable AI-agent (MCP) control (optional):**

1. Install and activate the official **WordPress MCP Adapter** plugin. The plugin's settings **Developer** tab walks you through it.
2. Create an application password for your user under **Users → Profile**.
3. Copy the ready-made client configuration from the Developer tab into Claude, Cursor, Windsurf, or another MCP client.
4. The `absplittest/*` tools are now available. The same actions are also on the REST API under `/wp-json/bt-bb-ab/v1/`.

== Frequently Asked Questions ==

= Is AB Split Test a self-hosted A/B testing plugin? =

Yes. The testing engine, visitor tracking, heatmaps, and reporting all run inside your WordPress install and store data on your own server. There is no cloud account and no SaaS backend.

= Is there a free WordPress A/B testing plugin with no page-view limit? =

Yes. AB Split Test has no page-view meter and no per-visitor billing on any plan. Your tests scale with your hosting.

= What are the limits of the free version? =

One active test at a time, with a control and one variation. Heatmaps and session replays keep a rolling 3-day window. Traffic is never limited.

= Can an AI agent like Claude run A/B tests on WordPress? =

AB Split Test registers WordPress Abilities, which the official WordPress MCP Adapter exposes as MCP tools. The same actions are on the REST API. An agent authenticated as a user with the right permissions can create tests, list them, read results, read heatmaps, and start, pause, or complete tests.

= How do I A/B test an Elementor, Bricks, or Beaver Builder page? =

AB Split Test adds native controls to Elementor, Bricks, Beaver Builder, Oxygen, Breakdance, WP Bakery, and Gutenberg. The point-and-click Magic Bar and full-page tests work with any theme or builder, because they operate on the rendered page.

= Is there a self-hosted alternative to Nelio A/B Testing? =

Yes. AB Split Test runs the testing engine and stores all tracking data on your own server, needs no external account, and never meters tested page views. Nelio stores and processes tracking data in its cloud and meters page views on every plan. See the comparison above.

= Are heatmaps limited in the free version? =

Heatmaps and session replays are recorded on every page, with no page limit. The free version keeps the last 3 days of data. Pro keeps longer history.

= Can I track form submissions or WooCommerce purchases? =

In the free version you can count a visit to your form's thank-you page, a URL, or the WooCommerce "order received" page as the conversion. Native form-plugin submission goals and purchase and revenue tracking are part of Pro.

= Will it slow down my site or fight my caching plugin? =

The front-end script is small and makes no requests to third-party servers. It is automatically marked for exclusion in major caching and optimization plugins so tests render cleanly.

= Does it use cookies? Is it GDPR friendly? =

AB Split Test uses first-party cookies and browser storage on your own domain to remember which variation a visitor saw. It does not fingerprint devices and sends nothing to a third party. As with any analytics tool, check your own consent requirements.

= What happens to my data if I delete the plugin? =

Your tests and results are kept by default, so nothing is lost by accident. Developers who want a full clean-up can set the `abst_delete_data_on_uninstall` option to `1` before deleting the plugin.

= Do I need to know how to code? =

No. The Magic Bar lets you build tests by clicking elements on the page. Developers who want more control get the REST API and MCP tools.

= Where can I get help or report a bug? =

Use the [WordPress.org support forum](https://wordpress.org/support/plugin/ab-split-test-lite/) or visit [absplittest.com](https://absplittest.com) for documentation.

== Screenshots ==

1. The dashboard with all your tests and their status.
2. The point-and-click Magic Bar: select any element and create a variation.
3. Test results with conversion rates and statistical confidence.
4. Heatmaps and session replays built from your own visitor data.
5. The Developer tab with MCP and REST API setup.
6. Creating a test: choose point-and-click, full-page, on-page, or CSS.

== Changelog ==

= 1.0.0 =
* First WordPress.org release of AB Split Test Lite, built on the AB Split Test engine used since 2019.
* Self-hosted point-and-click, full-page, on-page, and CSS tests.
* MCP tools (via the WordPress Abilities API) and REST API for AI-agent and programmatic control.
* Heatmaps and session replays on every page, with a 3-day retention window.
* Native page-builder controls for Elementor, Bricks, Beaver Builder, Oxygen, Breakdance, WP Bakery, and Gutenberg.
* Bayesian statistical analysis with device breakdown.

== Upgrade Notice ==

= 1.0.0 =
First public release of AB Split Test Lite: self-hosted A/B testing with unmetered traffic, AI-agent (MCP) control, point-and-click editing, heatmaps, and session replays.
