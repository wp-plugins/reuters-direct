=== Reuters Direct ===
Contributors: RNAGS
Donate link: http://thomsonreuters.com/en/products-services/reuters-news-agency.html
Tags: news_aggregator,Reuters,News,Reuters_Connect
Requires at least: 3.8
Tested up to: 4.2.2
Stable tag: 2.4.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A full-featured news aggregator, powered by Reuters Connect: Web Services, which ingests Reuters content directly into a WordPress platform.

== Description ==

A full-featured news aggregator, powered by Reuters Connect: Web Services, which ingests Reuters news and picture content directly into a WordPress platform.

Reuters Direct uses our Reuters Web Services‐API to ingest content into WordPress directly. Text wires are ingested as posts in draft status. Pictures are automatically ingested into the Media Gallery with appropriate descriptions and titles. Online Reports are ingested with attached pictures and can be set to publish status or draft depending on if a picture has been made available for it.

Configure ingestion of content via channel as well multiple image resolution and category codes. 

*** In order to use this plugin, you must have a Reuters Connect: API user. This is a paid subscription and details for obtaining these credentials can be found in the FAQ. ***

== Installation ==

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Select Reuters Direct from the Settings menu.
1. Log in using your Reuters Connect: Web Services API username and password
1. Configure required channels, category codes, and media settings. 

== Frequently asked questions ==

= Is this an official Reuters released delivery method? =

Yes, its an official Reuters media delivery method. See Donate Link or https://rmb.reuters.com/agency-api-demo/login.jsp for details.


= I don't have a Reuters Connect: Web Services username and password =

Select Contact Us under the Help section of the plugin and fill out the form to submit a
ticket to Reuters customer service. From there, an appropriate account representative will reach out to assist.

= Should I remain logged into the plugin? =

Yes, absolutely. If you log out of Reuters Wordpress Direct, any polling and ingestion will stop. The Dashboard widget
will also state "Not logged In" as well.

= What content will this ingest into Wordpress? =

Reuters Wordpress direct will ingest news and picture content as subscribed to in the entitlements of your account.
If any channel is missing in the Configuration page, please contact your Account Manager or select the Help drop-down to
open a ticket with our Customer Service team.

= How do I identify content and their post status? =

Reuters WordPress direct will set the Post status to either Publish or Draft status. You can also select which content gets
which status. For example, Text Wire content will ALWAYS be set to Draft status. This is to allow Editors the change to review
the content prior to publish. Text wire content will be tagged with "REUTERS TXT" for easy filtering. Additionally, Online
Reports can be set to Published only if there is an accompanying image. These stories are tagged "Reuters OLR-no image"
for easy filtering. We recommend you remove these tags upon publishing this content.

= I don't see any images with my Online Reports =

Reuters WordPress direct will assign the first image in the package as the Featured Image of the article. It will also inline
any images associated with the article at the bottom of the post. If you do not see the first image, please review your theme
to ensure it uses the Featured Image. You can also look at the Featured Image box at the bottom of the Edit Post screen to 
review the image.

= Will Reuters Wordpress Direct ingest video? =

As of this writing, RWPD will not ingest video into Wordpress. This functionality is currently on our roadmap for inclusion.

= What is this Dashboard widget? =

RWPD will add a widget to your dashboard to communicate the back-end functionality and channel details. It will provide information
on what's being updated, how many stories it's getting, cron jobs kicking off, etc.

= Gimmie the tech details!! =

RWPD uses our the Reuters Connect: Web Services API to make REST queries wrapped in cURL statements using the TLSv1.2 secure protocol. 
Every five minutes, a cron job kicks off and pulls all available content from the API and written directly into the WordPress database. Any updates to stories are over-written in the database
based on the GUID of the story. Story packages are ingested into Wordpress as Posts, and images directly into the Media Gallery. 
Associated metadata is included with the images and category information is also carried into Wordpress at the time of ingestion.

= I have feedback on RWPD. What can I do? =

We are definitely open to any and all feedback. Please use the Help drop-down in the Configuration page to open a ticket with
our Customer Service team and will be happy to look at your request.

= Am I in the danger zone? =

Ask Lana.

== Screenshots ==

1. Screenshot-1: Configuration screen of Reuters Wordpress Direct
2. Screenshot-2: Configuration screen cont. 
3. Screenshot-3: Posts screen in Wordpress show the post tagging for Online Reports wo/ Images and Text-wires
4. Screenshot-4: Media gallery
5. Screenshot-5: Image Detail Screen showing attached metadata

== Changelog ==

= 2.4.2 =
* Fixed duplicate image bug.

= 2.4.1 =
* Release version!

== Upgrade notice ==

= 2.4.2 =
* Performance upgrade & minor bug fixes done. 

