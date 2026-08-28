# Multi-Select Taxonomy for ACF Relationship

A free, open-source add-on for [Advanced Custom Fields](https://www.advancedcustomfields.com/) (Free or Pro) by WPengine that enables **multi-select taxonomy filtering** on ACF's Relationship field.

By default, ACF's Relationship field lets an editor filter related posts by only **one** taxonomy term at a time. This add-on turns that filter into a multi-select, so editors can combine terms — matching the flexibility already available in Field Group settings.

## Why

* Selecting multiple terms from the **same** taxonomy (e.g. Month = February, March) matches posts in *either* month.
* Selecting terms from **different** taxonomies (e.g. Year = 2007 and Month = February) matches posts that satisfy *both* conditions at once.

In other words: **OR** within a taxonomy, **AND** across taxonomies — the faceted filtering behavior editors expect.

## Requirements

* WordPress 5.0+
* PHP 7.4+
* [Advanced Custom Fields](https://wordpress.org/plugins/advanced-custom-fields/) — Free or Pro (the Relationship field ships in the free core plugin, so this add-on does not require or unlock any Pro-only functionality)

## Installation

### From a release zip

1. Download the latest `multi-select-taxonomy-for-acf-relationship.zip` from the [Releases](../../releases) page.
2. In your WordPress admin, go to **Plugins → Add New → Upload Plugin** and upload the zip.
3. Activate **Multi-Select Taxonomy for ACF Relationship**.

### From source

1. Clone or download this repository into `wp-content/plugins/`:
   ```
   git clone https://github.com/divstudio-ru/multi-select-taxonomy-for-acf-relationship.git
   ```
2. Activate the plugin from **Plugins** in your WordPress admin.

No configuration is needed — once activated, any Relationship field with taxonomy filtering enabled will automatically become a multi-select.

## How it works

* The taxonomy filter dropdown on Relationship fields becomes a native multi-select, enhanced with [Select2](https://select2.org/) for a searchable, tag-style UI (matching ACF's own look and feel) and a "Select taxonomies..." placeholder when empty.
* On the server side, selected `taxonomy:term_slug` values are grouped by taxonomy and combined into a `tax_query`: terms within the same taxonomy use `OR`, and separate taxonomies are joined with `AND`.
* Works with taxonomy filters added via the Field Group UI, and with fields added dynamically (repeaters, flexible content, etc.).

## Translations

Bundled translations (`.po`/`.mo`) are included for German, French, Spanish, Italian, Portuguese, Dutch, Polish, and Russian. A `.pot` template is available in `/languages` for contributing additional languages.

## Support / Issues

Found a bug or have a feature request? Please [open an issue](https://github.com/divstudio-ru/multi-select-taxonomy-for-acf-relationship/issues).

## Changelog

See [readme.txt](readme.txt) for the full version history.

## License

GPL v2.1. See [LICENSE](https://www.tldrlegal.com/license/gnu-lesser-general-public-license-v2-1-lgpl-2-1).

## Credits

Developed and maintained by [div STUDIO](https://div.studio).
