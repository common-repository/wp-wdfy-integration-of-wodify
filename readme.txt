=== WP WDFY Integration of Wodify ===
Contributors: osti47
Tags: wodify, crossfit
Requires at least: 4.6
Tested up to: 6.6
Requires PHP: 5.6
Stable tag: trunk
License: GPL3
License URI: http://www.gnu.org/licenses/gpl-3.0
Donate link: https://www.paypal.me/amrap42/10

Display Wodify information directly within your Wordpress blog.  

== Description ==
**WP-WDFY** integrates information from the online performance tracking software **Wodify** into your WordPress based web site.

**Important update July 2024**
Please make sure to update a.s.a.p. - Wodify is updating the APIs that this plugin uses, any version <4.0 of this plugin will stop working soon.

DISCLAIMER: The plugin and its author are in no way associated with or endorsed by WODIFY. The plugin relies on the API provided by Wodify.

I would still like to thank Wodify for acknowledging the plugin and supporting me to keep it working.

**Features**

* **Automatic WOD posting**: Automatically create posts from Wodify WODs including images.
* **Upcoming Classes widget**: Displays the upcoming classes from Wodify for a selected number of days. 
* **WOD Widget**: Displays the WOD of a selected day, program, and location.
* **WOD shortcode**:  Insert a selected WOD into a post, page, ...
* **WOD block**: Insert a selected WOD into a page or post using the block editor
* **Wodify link shortcode**: Inserts responsive text or logo links to the Wodify web app into posts, pages, ...
* **Upcoming classes block**: Insert a list of upcoming classes into a page or post using the block editor
* **REST-API for speech output via Alexa/Siri**: Use the REST-API in combination with your own Alexa Skill.


== Installation ==
**Installation**:

* Install and Activate the Plugin via the WordPress Plugin menu
* Under Settings / Wodify integration enter the API Key for your Wodify tenant. To find you API Key look here: [Finding your Wodify API Key](https://wodify.zendesk.com/hc/en-us/articles/208736908-How-do-I-set-up-WOD-integration-without-WordPress-)
* After saving the API key, optionally set the default Location and Default Program parameters.

**Configure Features**:

Please check the plugins settings page for details. Remember you can use different widgets, blocks and shortcodes. Feel free to style the output with your CSS.

== Screenshots ==

1. Display your upcoming classes from Wodify in a widget
2. Plugin configuration screen with definition of class colors for widget
3. With this plugin you can use a shortcode in your posts and pages to display the WOD from Wodify for a specific day in your page.
4. When the page is rendered the plugin will pull the WOD from Wodify (or from its cache if available).
5. Use the WOD block in the new block editor to integrate a WOD in your posts or pages.

== Changelog ==
= 4.04 (2024/10/30) =
* fixed messup from 4.02 (error when avtivating/updating plugin and in some other unrelated areas)
* added new debug option
= 4.02 (2024/10/28) =
* fixed problem with classes for multiple locations and removed debug output
= 4.01 (2024/10/24) =
* add some debug output
= 4.0 (2024/07/16) =
* Update to new Wodify API
* cancelled classes are now marked in the calendar widget
= 3.19 (2024/02/14) =
* most HTML formatting that comes from Wodify's WOD editor is now stripped, so you can format using your websites CSS. Copy and paste into the WOD editor often includes unwanted formatting which is now removed.
= 3.18 (2023/11/11) =
* minor PHP compatibility fixes, comaptibel up to WP6.4
= 3.17 (2023/04/27) =
* fixes to make blocks compatible with Wordpress 6.2
= 3.16 (2023/04/26) =
* improved new WOD editor compatibility / fixed HTML entity issues
= 3.15 (2022/12/11) =
* WordPress 6.1 compatibility
* minor changes in speech API
* minor changes in WOD formating
= 3.14 (2022/10/22) =
* new options (nummonths, startperiod) for [wdfyevents] shortcode
= 3.13 (2022/10/21) =
* some more adaptions and fixes for WOD formating
* new column "name" column for [wdfyevents] shortcode
= 3.12 (2022/10/16) =
* formatting WODs adapted for legacy (non-HTML) WODs
= 3.11 (2022/10/05) =
* hotfix for Wodify now HTML formating WODs, will maybe add handling for legacy WOds later
= 3.10 (2022/01/28) =
* fix some more PHP warnings
* tested up to WP5.9  
= 3.09 (2021/09/26) =
* fix PHP warnings for empty WODs
= 3.08 (2021/07/30) =
* minor Wordpress 4.8 adaptions und fix PHP warnings.
= 3.07 (2021/02/07) =
* FIXED minor speech optimizations.
= 3.06 (2021/02/06) =
* FIXED minor speech optimizations.
= 3.05 (2021/01/31) =
* FIXED minor speech issues.
= 3.04 (2021/01/31) =
* IMPROVED Alexa sample code
= 3.02 (2021/01/31) =
* FIXED some German labels
= 3.0 (2021/01/31) =
* ADDED new REST API for speech output via Alexa or Siri
= 2.6 (2021/10/26) =
* CHANGED Lookup caches for location, coaches and programs now updated whenever settings page is accessed
= 2.5 (2020/02/13) =
* ADDED More configuration options for schema.org event attributes.
= 2.4.2 (2019/11/18) =
* ADDED Configuration option for schema.org event image.
= 2.4.1 (2019/11/17) =
* FIXED schema.org data in shortcode now validates without error. Some warnings still left. Update with configuration options to follow.
= 2.4 (2019/11/17) =
* ADDED new shortcode [wdfyevents] to display a list of upcoming classes incl. schema.org markup
* ADDED new block to display a list of upcoming classes incl. schema.org markup
= 2.3 (2019/07/29) =
* IMPROVED Limit caching frequency selection to specific sensible values
= 2.2 (2019/03/12) =
* ADDED customize post type of automatically created WODs
= 2.1 (2019/01/29) =
* FIXED a bug where debug output would show in automatically created WOD posts
= 2.0 (2019/01/11) =
* ADDED new WOD block for WordPress 5 block editor
= 1.17 (2019/01/08) =
* Added some diagnostic functions
* Tested on WordPress 5.0
= 1.16 (2018/12/09) =
* Minor code clean-up. 
* Tested on WordPress 5.0
= 1.15 (2018/03/08) =
* FIXED error with a specific WordPress timezone setting
* IMPROVED internal change to use WordPress http API for Wodify access
= 1.14 (2018/02/28) =
* FIXED Timezone issue for WOD shortcodes and widgets (today's WOD might not always have been today's depending on your timezone) 
* FIXED Timezone issue for upcoming classes widget 
* FIXED minor translation issue
* FIXED minor security issues
= 1.13 (2018/02/11) =
* INFO tested up to current WordPress version
* ADDED Diagnostic information download button for support purposes.
* FIX small layout fix when there are no announcements
* FIX Announcement now work with Wodify - Wodify fixed their API - you need to activate "publish externally" on announcement you would like to publish through the plugin
= 1.12.1 (2017/11/17) =
* FIXED All external links with target=_blank now using rel="noopener" for security reasons
* INFO tested up to WordPress 4.9
= 1.12 (2017/10/22) =
* FIXED (Important!) Again... Wodify changed parts of the API without any notification (WODs could display warnings)
= 1.11 (2017/09/12) =
* FIXED (Important!) Wodify changed parts of their API without telling anyone which broke at least the plugin's backend (programs list)
* ADDED plugin now handels announcements with WODs. New attribute "Announcements for shortcode and widget include/exclude components"
= 1.10.5 (2017/08/21) =
* FIXED adapt to inconsistency in Wodify's picture handling in WODs
= 1.10.4 (2017/08/21) =
* FIXED more formatting error in WOD comments
* FIXED problem if all components are images
= 1.10.3 (2017/08/21) =
* FIXED Formatting error in WOD comments
= 1.10.2 (2017/07/27) =
* CHANGED WOD Caching to improve cache behaviour for recently changed WODS
= 1.10.1 (2017/06/10) =
* ADDED/FIXED WOD Component Comments will now be displayed
= 1.9.2 (2017/05/03) =
* ADDED WOD shortcode parameter to display WOD for a specific (upcoming) weekday 
* FIXED minor readme spelling changes
= 1.9.1 (2017/04/01) =
* FIXED problem with multiple coaches in classes widget
= 1.9 (2017/02/05) =
* ADDED show WOD images (must be activated in settings, widgets or shortcodes)
* ADDED donation link and plugin info in settings
* FIXED disabling individual auto WOD post setting did not work correctly
* FIXED Wodify API returns last edited date time for WODs in time zone EST instead of the setup time zone, plugin now accounts for that (WOD caching)
= 1.8.2 (2017/02/01) =
* FIXED WOD not displaying when it contained only one component
* IMPROVED Added CSS style for "WOD not available" message
= 1.8.1 (2017/01/14) =
* CHANGED the plugin now respects the date and time formats from WordPress settings
= 1.8 (2017/01/08) =
* ADDED new WOD name parameter for post title of automated WOD posts
= 1.7.8 (2016/12/18) =
* FIX hopefully fixing PHP 5.4 compatibility
= 1.7.7 (2016/12/16) =
* FIX wrong settings link in plugin manager
* IMPROVED correctly add current plugin version to css/js cache buster
= 1.7.6 (2016/12/13) =
* FIX bug fixing for settings page and some minor other fixes
= 1.7.5 (2016/12/03) =
* FIX publish offset in automatic WOD posts now working
= 1.7.4 (2016/12/02) =
* FIXED some more caching issues with classes and wods
* IMPROVED prepared plugin for integration to translate.wordpress.com
= 1.7.2 (2016/12/01) =
* FIXED problem with classes widget not updating (when there is only a single location in Wodify)- mostly introduced with 1.7.1 sorry for that
* IMPROVED WOD recaching through URL parameter
* IMPROVED class cache configuration
= 1.7.1 (2016/11/30) =
* IMPROVED to avoid losing settings when saving with broken Wodify connection, basic data (locations, programs, coaches) is now returned also from outdated cache if Wodify call fails.
* FIXED Potential problems when only one program, coach, or location was returned from Wodify
* FIXED Shortcode wdfywod documentation in readme and on plugin settings page
= 1.7 (2016/11/29) =
* ADDED automatic post creation for WODs
* ADDED filter classes for a specific coach
* IMPROVED visual reservation info for "open" classes now shown like an empty class (grey instead green)
= 1.6.1 (2016/11/23) =
* FIX removed "from cache" text from WOD output
= 1.6 (2016/11/20) =
* ADDED configure URLs for each coach to be used as link target when coach names are displayed, e.g. in classes widget 
* ADDED parameter for switching auto-scrolling on/off on classes widget
* ADDED URL parameter to explicitely trigger cache refresh for WODs displayed in the current page (see WOD tab in settings)
* IMPROVED Cleaned up settings panel and added some documentation and description texts
* FIXED initialization of new classes widget parameters to avoid PHP Notices
= 1.5 (2016/11/19) = 
* ADDED caching for classes via WordPress cron
* ADDED Visual reservation status for classes widget
* ADDED setting to chose what WOD publish date from Wodify the plugin will use
* IMPROVED help texts on settings page
* IMPROVED optimized Wodify logos
* IMPROVED styling of links in classes widget
* FIXED default for cache settings for WODs
* FIXED PHP warning and classes not showing, when there is only one class on a specific day
= 1.4.1 (2016/11/12) = 
* FIX styles for Wodify logo caused layout problem with page
= 1.4 (2016/11/11) = 
* CHANGED/ADDED link shortcode now uses new Wodify logos, size can additionally be styled through css
= 1.3 (2016/11/04) = 
* ADD configure URL for each program to be used as link target in classes widget (e.g. class descriptions)
* FIX load script and style resources with version number to avoid browser caching problems
* FIX Program color setup if there is only one program in Wodify
* FIX set initial program color correctly on program page
* FIX some translations
= 1.2 (2016/10/31) = 
* FIX small layout glitch of classes widget on mobile devices
= 1.1.1 (2016/10/30) = 
* FIX fix issues in 1.0 release
= 1.0 (2016/10/30) =
* ADDED Upcoming classes widget 
* ADDED setting for Wodify timezone to correctly calculate times from Wodify, fixes potential issues with Blog publish date
* ADDED caching locations and programs from wodify for one day improving backend performance
* FIXED caching issue with WODs ("no WOD" for next day cached forever)
* FIXED Filter out URL from hero WODs
* FIXED incomplete translations
= 0.5 =
* ADDED "includecomponents" attricbute for [wdfywod] shortcode to show only selected components
* ADDED "excludecomponents" attricbute for [wdfywod] shortcode to hide selected components
* ADDED caching for all WOD pulling from Wodify. Only WODs +/- 7 days around today are cached 
* ADDED "cache" attribute for [wdfywod] shortcode to disable caching (set to "false" to disable caching)
* ADDED div Tags around WOD sections
* ADDED WOD Widget setting for WOD day selection, cache settings and inlcuding and excluding WOD components from display
= 0.4.6 =
* ADDED Settings link on plugins page
* FIXED PHP notices from shortcodes
* FIXED PHP includes
= 0.4.5 =
* ADDED "date" attribute of [wdfywod] shortcode now accepts relative dates, e.g. "+1" or "-1"
= 0.4.4 =
* bug fix in widget
= 0.4.3 =
* ADDED: widget option to ignore Wodify WOD publish settings
* CHANGED: Added timeout (2s) for Wodify WOD API calls
* CHANGED: Error logging for wodify API removed
* FIXED: minor internal error handling
= 0.4.2 =
* name change to WP WDFY
= 0.4.1 =
* bug fix in wdfylink shortcode
= 0.4 =
* ADDED WOD caching in widget to reduce API calls and speed up page loads
* ADDED WOD display in widget respects BlogPublish Date set in Wodify
* ADDED WOD display via shortcode by default respects BlogPublish Date set in Wodify, can be overriden using new "ignorepublishdate" attribute
= 0.3.4 =
* ADDED "logo" attribute for "wdfylink" shortcode
= 0.3.4 =
* bug fixes
= 0.3.3 =
* more bug fixes with WOD display no working in all environments
= 0.3.2 =
* bug fixes
= 0.3.1 = 
* some fixes in readme.txt
= 0.3 = 
* first public release, added shortcodes "wdfylink" and "wdfywod"
= 0.2 =
* Renamed to WordPress requirements
= 0.1 =
* first version comes with a lightweight widget to display the current day's WOD on your webpage

