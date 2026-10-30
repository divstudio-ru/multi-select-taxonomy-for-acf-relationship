=== Multi-Select Taxonomy for ACF Relationship ===
Contributors: div STUDIO
Tags: acf, advanced custom fields, relationship field, multiselect, taxonomy filter
Requires at least: 5.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.17
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Author URI: https://div.studio
Requires Plugins: advanced-custom-fields

== Description ==

This is a **free add-on for Advanced Custom Fields** that enhances the Relationship field by enabling multi-select functionality for taxonomy filters in the post editor.

The Relationship field ships in ACF's free core plugin, so this add-on works with either the free version or ACF Pro - it does not require or unlock any Pro-only functionality.

By default, ACF's Relationship field lets an editor filter related posts by only one taxonomy term at a time. This add-on turns that filter into a multi-select, so editors can combine terms:

* Selecting multiple terms from the **same** taxonomy (e.g. Month = February, March) matches posts in *either* month.
* Selecting terms from **different** taxonomies (e.g. Year = 2007 and Month = February) matches posts that satisfy *both* conditions at once.

== Changelog ==

= 1.0.17 =
* Fixed: "Add Row" did nothing in a Repeater (or Flexible Content) whose
  rows contain a Relationship field with the taxonomy filter. The new row
  is cloned from ACF's hidden template row, whose filter was already
  enhanced; the copy kept Select2's class but not its instance, and
  calling select2('destroy') on it threw an error that aborted ACF's
  "append" action. The script now checks for a real Select2 instance and
  only strips leftover markup from such copies.
* The exposed ACFRelMultiselectEnhancer.version now matches the plugin version.

= 1.0.16 =
* Updated the Plugin URI (and the translation files' bug-report link) to
  match the renamed GitHub repository:
  github.com/divstudio-ru/multi-select-taxonomy-for-acf-relationship.

= 1.0.15 =
* Renamed the plugin to "Multi-Select Taxonomy for ACF Relationship" (from
  "ACF Relationship Multi-Select Filter") and updated the slug and text
  domain to match, ahead of WordPress.org submission - the Plugin
  Directory's naming guidelines don't allow a plugin name to lead with
  another product's trademark ("ACF") unless you're the trademark holder.
* Bumped "Tested up to" to WordPress 7.1.

= 1.0.14 =
* Updated the Plugin URI to the correct GitHub location
  (github.com/divstudio-ru/acf-relationship-multiselect).

= 1.0.13 =
* Added bundled translations (.po/.mo) for German, French, Spanish,
  Italian, Portuguese, Dutch, Polish, and Russian.

= 1.0.12 =
* Made the "Select taxonomies..." placeholder text translatable. It's a
  string in the plugin's own JS file, not something ACF provides, and JS
  strings aren't picked up by load_plugin_textdomain() on their own - it's
  now passed from PHP to JS via wp_localize_script() so it goes through the
  normal __() translation pipeline like the rest of the plugin's text.
* Added a languages/ directory with a starter .pot template.

= 1.0.11 =
* Fixed the placeholder still not appearing on a fresh page reload. ACF
  field groups are commonly loaded as "classic" metaboxes that WordPress
  injects via AJAX after the main editor UI renders, and the metabox panel
  can still be mid-animation (effectively zero width) at the exact moment
  our script ran - Select2 miscalculates its layout, and the placeholder
  ends up invisible, if initialized while its container isn't actually
  visible yet. Select2 initialization now waits until the field is visible
  before running.

= 1.0.10 =
* Fixed the placeholder not appearing on a fresh page load/reload (it did
  appear correctly when clearing the field mid-session). On initial load
  ACF's own default Select2 is typically already attached to the select
  (single-select mode) before our script runs; we now destroy that instance
  first, before making any DOM changes, and explicitly force Select2 to
  re-sync its display afterward, so stale internal Select2 state can no
  longer mask the empty/placeholder state.

= 1.0.9 =
* Set the empty-state placeholder text to "Select taxonomies..." - it was
  already configured before, but couldn't actually be seen until 1.0.8
  fixed the bug that kept a phantom term stuck selected, since the field
  was never truly empty before that.

= 1.0.8 =
* Fixed a bug where the filter could not be cleared back to "no filter" -
  deselecting terms kept the first taxonomy term stuck as selected. Caused
  by removing the placeholder option while the select was still a native
  single-select: browsers require a single-select to always keep exactly
  one option selected, so removing the selected placeholder made the
  browser auto-select the next option (the first real term), and that
  stuck around even after the field became a multi-select a moment later.
  Now the multiple attribute is applied first, then the placeholder is
  removed, then the selection is explicitly cleared.

= 1.0.7 =
* Clarified that this add-on works with Advanced Custom Fields free or Pro
  (the Relationship field it enhances ships in the free core plugin) -
  updated the requirement wording, admin notice, and activation guard
  accordingly. No detection logic changed - it always accepted either
  edition.

= 1.0.6 =
* Fixed filter logic: terms selected within the same taxonomy now combine
  with OR (e.g. February or March), while terms selected across different
  taxonomies now combine with AND (e.g. Year 2007 and Month February).
  Previously all selected terms were OR'd together, so picking a second
  taxonomy widened the results instead of narrowing them.

= 1.0.5 =
* Fixed actual post filtering: the taxonomy filter values ACF sends are term
  slugs, not numeric term IDs - the tax_query now matches on slug
* Fixed a fatal error that could occur when multiple taxonomies were
  selected, by intercepting the raw request before ACF's core code runs
  explode() on it
* Removed the unnecessary fetch API interceptor - ACF's own AJAX request
  already transmits multiple selected values correctly once the filter is a
  native multi-select
* Removed debug console logging and temporary visual test styling

= 1.0.4 =
* Implemented fetch API interceptor for proper multi-select value transmission
* Fixed relationship field filtering to work with multiple taxonomy selections
* Improved JavaScript event handling

= 1.0.0 =
* Initial release

== License ==

This plugin is licensed under the GPL v2 or later.
