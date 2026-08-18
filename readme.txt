=== LoopSync Dynamic Fields for Elementor ===
Contributors: devnexus
Tags: elementor, acf, repeater, loop grid, dynamic tags
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 0.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Render ACF Pro Repeater fields inside Elementor Pro's Loop Grid widget using dedicated dynamic tags — no code required.

== Description ==

LoopSync Dynamic Fields for Elementor bridges the gap between **ACF Pro Repeater fields** and **Elementor Pro's Loop Grid widget**.

Elementor Pro's Loop Grid natively loops over WordPress posts, terms, or products. This plugin enables the Loop Grid to loop directly over rows of an ACF Pro Repeater field without creating mock posts, duplicate entries, or negative IDs.

Each repeater row seamlessly becomes an item in your Loop Grid, and dedicated Elementor Dynamic Tags let you display sub-field values directly inside your Loop Item Template.

= Key Features =

* **Native Loop Grid Integration**: Adds repeater query controls directly into the Loop Grid's Query section.
* **Six Dedicated Dynamic Tags**:
    * **Repeater: Text / Textarea**: For text, textarea, email, and password sub-fields.
    * **Repeater: WYSIWYG**: For rich text (WYSIWYG) and oEmbed sub-fields.
    * **Repeater: Image**: For image sub-fields (supports Array, URL, and ID return formats).
    * **Repeater: URL / Link**: For URL, link (array), page link, and file sub-fields.
    * **Repeater: Number**: For number and range sub-fields.
    * **Repeater: Date / Time**: For date picker, datetime picker, and time picker sub-fields.
* **Filter-Driven Architecture**: Built on a modular Field Type Provider system for clean extensibility.
* **Safe & Clean**: Leaves zero database clutter upon uninstallation.

= Requirements =

* **WordPress**: 6.5 or higher
* **PHP**: 7.4 or higher
* **Elementor**: 3.21.0 or higher
* **Elementor Pro**: 3.21.0 or higher
* **Advanced Custom Fields Pro**: 6.0.0 or higher

== Installation ==

1. Upload the `loopsync-dynamic-fields-for-elementor` folder to the `/wp-content/plugins/` directory.
2. If installing from source, run `composer install --no-dev --optimize-autoloader` in the plugin directory to generate the autoloader.
3. Activate the plugin through the **Plugins** menu in WordPress.
4. Ensure **Elementor**, **Elementor Pro**, and **ACF Pro** are active.

== How to Use ==

= 1. Create a Loop Item Template =
1. Go to **Templates → Theme Builder → Loop Item** in WordPress admin and create a new template.
2. Design your card layout using standard Elementor widgets.
3. For any widget field supporting dynamic data, click the **Dynamic Tags** icon (⚡).
4. Under the **ACF** section, select one of the **Repeater:** dynamic tags.
5. In the tag settings, select the parent **Repeater Field** and the target **Sub-Field**.
6. Save and publish the Loop Item Template.

= 2. Configure the Loop Grid Widget =
1. Edit your page in Elementor and add a **Loop Grid** widget.
2. In the widget panel, open the **Query** section.
3. Turn on the **Use ACF Repeater** toggle.
4. Select the ACF Repeater Field from the dropdown.
5. Optionally enable **Current Post Only** to restrict rows to the current post context.
6. Select your Loop Item Template.

== Frequently Asked Questions ==

= Does this plugin work with the free version of ACF? =
No. ACF Repeater fields are an ACF Pro feature, so Advanced Custom Fields Pro (6.0.0+) is required.

= Does this work with Elementor Free? =
The Loop Grid widget and Dynamic Tags are Elementor Pro features, so Elementor Pro (3.21.0+) is required.

= How are dates formatted? =
Sub-field values use the return format configured directly in your ACF field settings.

= Can I add support for other custom field types? =
Yes. The plugin uses an extensible `Field_Type_Contract` provider interface with the `loop_dynamic_fields/register_providers` hook.

== Screenshots ==

1. Loop Grid Query panel with ACF Repeater controls enabled.
2. Selecting an ACF Repeater Dynamic Tag inside the Loop Item Template editor.
3. Front-end Loop Grid rendering ACF Repeater rows.

== Changelog ==


== Upgrade Notice ==

= 0.0.1 =
Update plugin name, Slug & Prefix.
Update Author & Plugin URI.

= 0.0.0 =
Initial development release.
