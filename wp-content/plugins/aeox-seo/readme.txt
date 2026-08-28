=== AEOX SEO ===
Contributors: aeoxteam
Tags: seo, aeo, geo, ai search, entity seo, schema, structured data, artificial intelligence, optimization, search engine, meta tags, sitemap
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI Search Optimization Platform for WordPress - SEO + AEO + GEO + Entity Optimization for AI-powered search engines.

== Description ==

**AEOX SEO** is the first WordPress plugin designed specifically for the era of AI-powered search. While traditional SEO plugins focus on keyword optimization, AEOX goes further to optimize your content for:

* **Google AI Overviews** (formerly SGE)
* **Bing Copilot** and AI search
* **ChatGPT** and large language models
* **Perplexity** and answer engines
* **Traditional search engines** (Google, Bing, DuckDuckGo)

= Why AEOX? =

The search landscape has fundamentally changed. AI systems now summarize content and cite sources directly in search results. AEOX helps you optimize for this new reality with:

1. **SEO Engine** - Traditional search optimization (titles, meta descriptions, headings, content structure)
2. **AEO Engine** (Answer Engine Optimization) - Optimize for direct answers and question-based queries
3. **GEO Engine** (Generative Engine Optimization) - Improve citation readiness and AI visibility
4. **Entity Engine** (Coming Soon) - Build knowledge graph connections
5. **Schema Engine** (Coming Soon) - Generate structured data for better AI understanding

= Key Features =

**Technical SEO**
* Title and meta description analysis
* Heading structure validation (H1-H6)
* Content depth analysis
* Internal and external link checking
* Image ALT text optimization
* Mobile-friendly recommendations

**Answer Engine Optimization (AEO)**
* Question detection and analysis
* Search intent classification
* Answer block identification
* Readability scoring
* Direct answer optimization

**Generative Engine Optimization (GEO)**
* Citation readiness scoring
* Factual clarity analysis
* Entity clarity detection
* Source support verification
* Content freshness monitoring
* Trust signal detection

**Content Intelligence**
* Topic coverage analysis
* Content gap identification
* Semantic relationship mapping
* Internal linking recommendations

= Important Notes =

**AEOX Scores are Proprietary**: The SEO, AEO, and GEO scores provided by AEOX are proprietary metrics designed to help you improve your content. They are NOT official scores from Google, Microsoft, OpenAI, or any search engine.

**AI Visibility**: AEOX helps optimize your content for AI systems, but cannot guarantee citations or rankings. AI search results depend on many factors beyond any single plugin's control.

**External Services**: Future versions may integrate with external AI services (OpenAI, Google Gemini, Anthropic Claude). No data is sent to external services without your explicit configuration and consent.

= Getting Started =

1. Install and activate AEOX SEO
2. Navigate to AEOX SEO → Dashboard
3. Review your site's overall AI Search Score
4. Edit any post/page to see real-time analysis in the sidebar
5. Follow recommendations to improve your scores

= Requirements =

* WordPress 6.0 or higher
* PHP 7.4 or higher
* MySQL 5.6 or higher / MariaDB 10.1 or higher

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Navigate to Plugins → Add New
3. Search for "AEOX SEO"
4. Click "Install Now"
5. Click "Activate"

= Manual Installation =

1. Download the AEOX SEO plugin ZIP file
2. Upload it to your `/wp-content/plugins/` directory
3. Unzip the file
4. Navigate to Plugins in WordPress admin
5. Click "Activate" next to AEOX SEO

= After Activation =

1. Go to AEOX SEO → Dashboard to see your site's AI Search Score
2. Visit any post or page editor to see the AEOX meta box
3. Configure settings at AEOX SEO → Settings

== Frequently Asked Questions ==

= Is AEOX free? =

Yes! AEOX SEO is completely free and released under GPL-2.0-or-later license. Future premium features will be offered as optional cloud services, not by locking existing functionality.

= Will this replace Yoast SEO or Rank Math? =

AEOX complements traditional SEO plugins by focusing on AI search optimization. You can use AEOX alongside other SEO plugins, though we're building comprehensive SEO features that may make AEOX your only SEO plugin needed.

= What is GEO? =

GEO (Generative Engine Optimization) refers to practices that improve how AI systems understand, extract, and cite your content. This includes factual clarity, source support, entity relationships, and citation readiness.

= Does AEOX send my content to AI services? =

No. By default, all analysis happens locally on your server. Future optional AI-powered features will require your explicit API key configuration and will clearly disclose what data is processed externally.

= What are AEOX scores based on? =

Our scoring system uses weighted algorithms analyzing multiple factors:
* Technical SEO (20%)
* Content SEO (15%)
* Answer Engine Optimization (20%)
* Generative Engine Optimization (20%)
* Entity Optimization (10%)
* Schema/Structured Data (10%)
* Content Freshness (5%)

These weights are configurable in future versions.

= Is AEOX compatible with my theme/page builder? =

AEOX works with any WordPress theme and is compatible with major page builders including Gutenberg, Elementor, and more. Full integration with specific builders is being developed.

= Do I need an API key? =

No. All core features work without any API keys. Optional AI-powered features in future versions may require your own API keys for services like OpenAI or Google Gemini.

= How do I uninstall AEOX? =

1. Deactivate the plugin in WordPress
2. Delete the plugin
3. Optionally, enable "Remove data on uninstall" in settings before deletion to clean up database tables

= Where can I get support? =

Visit our support forum or documentation at [aeoxseo.com](https://aeoxseo.com) (coming soon).

== Screenshots ==

1. AEOX Dashboard showing AI Search Score breakdown
2. Post editor with AEOX meta box displaying real-time analysis
3. SEO Analysis details with actionable recommendations
4. AEO Analysis showing detected questions and answer quality
5. GEO Analysis with citation readiness metrics

== Changelog ==

= 1.0.0 - 2025-01-XX =
* Initial release
* Core plugin architecture
* Database layer with 9 custom tables
* SEO Engine with content analysis
* AEO Engine with question detection
* GEO Engine with citation readiness scoring
* Scoring system with weighted algorithms
* Admin dashboard
* Meta box for post/page analysis
* Security hardening (nonces, capabilities, sanitization)
* WordPress.org compliance improvements
* Uninstall handler with optional data cleanup

== Upgrade Notice ==

= 1.0.0 =
Initial release of AEOX SEO. No upgrade path needed.

== External Services ==

This plugin currently does NOT require any external services. All analysis runs locally on your server.

Future versions may offer optional integrations with:
* OpenAI API (for AI-powered content suggestions)
* Google Gemini API (for advanced content analysis)
* Anthropic Claude API (for content quality assessment)
* Bing IndexNow API (for faster indexing)

Any external service integration will require your explicit API key configuration and clear disclosure of data transmission.

== Privacy Policy ==

AEOX SEO respects your privacy and your visitors' privacy.

**Data Collection**: This plugin does not collect personal data from your visitors by default.

**Data Transmission**: By default, no data is sent to external servers. All analysis runs locally on your WordPress installation.

**Database Storage**: AEOX creates custom database tables to store analysis results. These tables contain metadata about your content (scores, detected issues, etc.) but not visitor personal data.

**Optional External Services**: If you choose to configure optional AI services in the future, your content may be sent to those services for processing. This only happens with your explicit configuration and API key provision.

**Cookies**: AEOX does not set cookies for visitors. Admin cookies are standard WordPress session cookies.

**GDPR Compliance**: Site owners are responsible for their own GDPR compliance. AEOX provides tools to help document content optimization but does not provide legal compliance guarantees.

For more information, consult with a legal professional about your specific privacy requirements.

== License ==

AEOX SEO is licensed under GPL-2.0-or-later.

Copyright (C) 2025 AEOX Team

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
