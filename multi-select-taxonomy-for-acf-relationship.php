<?php
/**
 * Plugin Name: Multi-Select Taxonomy for ACF Relationship
 * Plugin URI: https://github.com/divstudio-ru/multi-select-taxonomy-for-acf-relationship
 * Description: Add-on for Advanced Custom Fields (Free or Pro) - Enables multi-select taxonomy filtering in Relationship fields. Allows editors to select multiple taxonomy terms simultaneously when filtering related posts, matching the capabilities available in Field Group settings.
 * Version: 1.0.16
 * Author: div STUDIO
 * Author URI: https://div.studio
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: multi-select-taxonomy-for-acf-relationship
 * Domain Path: /languages
 * Requires: Advanced Custom Fields 5.8+ (Free or Pro)
 * Requires PHP: 7.4
 *
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * This is an ADD-ON for Advanced Custom Fields.
 * The Relationship field itself ships in ACF's free core plugin, so this
 * add-on works with either the free version or ACF Pro - it does not
 * require or unlock any Pro-only functionality, and does not modify ACF
 * itself or violate any license terms.
 *
 * License: GPL v2 or later
 *
 * ─────────────────────────────────────────────────────────────────────────────
 */

// Prevent direct access to this file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define plugin constants
 */
define( 'ACF_REL_MULTISELECT_VERSION', '1.0.16' );
define( 'ACF_REL_MULTISELECT_PATH', plugin_dir_path( __FILE__ ) );
define( 'ACF_REL_MULTISELECT_URL', plugin_dir_url( __FILE__ ) );
define( 'ACF_REL_MULTISELECT_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Check if Advanced Custom Fields (Free or Pro) is installed and activated
 *
 * The 'ACF' class is defined by both the free and Pro editions of Advanced
 * Custom Fields, since Pro is built on the same core - so this check
 * accepts either edition. The Relationship field this add-on enhances
 * ships in the free core plugin, so Pro is not required.
 *
 * @return bool
 */
function acf_rel_multiselect_is_acf_active() {
	return class_exists( 'ACF' );
}

/**
 * Show admin notice if ACF is not installed/activated
 * This notice ONLY shows when ACF is not active
 */
function acf_rel_multiselect_admin_notice() {
	// Only show to users who can install plugins
	if ( ! current_user_can( 'install_plugins' ) ) {
		return;
	}

	// Double-check ACF is really not active (in case it's loading late)
	if ( acf_rel_multiselect_is_acf_active() ) {
		return;
	}

	// Get plugin data
	$plugin_name = 'ACF Relationship Multi-Select Filter';

	?>
	<div class="notice notice-error is-dismissible">
		<p>
			<strong><?php echo esc_html( $plugin_name ); ?>:</strong>
			<?php esc_html_e( 'Requires Advanced Custom Fields (free or Pro) to be installed and activated.', 'multi-select-taxonomy-for-acf-relationship' ); ?>
		</p>
		<p>
			<?php
			printf(
				wp_kses_post(
					__( 'Please <a href="%s">install and activate Advanced Custom Fields</a> first.', 'multi-select-taxonomy-for-acf-relationship' )
				),
				esc_url( admin_url( 'plugins.php' ) )
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Initialize the plugin - Use admin_init for better timing
 * This hook fires after WordPress core has finished setting up
 */
function acf_rel_multiselect_init() {
	// Check if ACF Pro is active
	if ( ! acf_rel_multiselect_is_acf_active() ) {
		// Show admin notice ONLY when ACF is not installed
		add_action( 'admin_notices', 'acf_rel_multiselect_admin_notice' );
		return;
	}

	// ACF is active - load the plugin functionality
	acf_rel_multiselect_load_plugin();
}

/**
 * Load plugin functionality (only called when ACF Pro is active)
 */
function acf_rel_multiselect_load_plugin() {
	// Enqueue admin scripts on ALL admin pages (let the script detect where it's needed)
	add_action( 'admin_enqueue_scripts', 'acf_rel_multiselect_enqueue_admin_scripts', 20 );

	// Run BEFORE ACF's own AJAX handler for this action, so we can stash the
	// multi-select values and neutralize $_POST['taxonomy'] before ACF's core
	// relationship field code runs explode() on it (which fatals on PHP 8 if
	// given an array instead of a string).
	add_action( 'wp_ajax_acf/fields/relationship/query', 'acf_rel_multiselect_preprocess_ajax', 1 );
	add_action( 'wp_ajax_nopriv_acf/fields/relationship/query', 'acf_rel_multiselect_preprocess_ajax', 1 );

	// Hook into ACF's relationship query to rebuild the tax_query from the
	// stashed multi-select values.
	add_filter( 'acf/fields/relationship/query', 'acf_rel_multiselect_handle_multiple_taxonomies', 10, 3 );

	// Add plugin action links
	add_filter( 'plugin_action_links_' . ACF_REL_MULTISELECT_BASENAME, 'acf_rel_multiselect_action_links' );
}

/**
 * Enqueue scripts for admin area
 * This adds the JavaScript that converts the taxonomy filter to multi-select
 *
 * UPDATED: Enqueue on all admin pages and let JS detect where it's needed
 */
function acf_rel_multiselect_enqueue_admin_scripts() {
	// Only enqueue in the admin area
	if ( ! is_admin() ) {
		return;
	}

	// Register the enhancement script
	wp_register_script(
		'acf-rel-multiselect-admin',
		ACF_REL_MULTISELECT_URL . 'assets/js/admin.js',
		array( 'jquery' ),
		ACF_REL_MULTISELECT_VERSION,
		true
	);

	// Enqueue the script on all admin pages (the script will self-detect if it's needed)
	wp_enqueue_script( 'acf-rel-multiselect-admin' );

	// Pass translatable strings through to the JS file. Strings hardcoded
	// directly in a .js file are NOT picked up by load_plugin_textdomain()
	// or any .mo/.po file - only PHP strings wrapped in __()/_e() are. This
	// is the standard way to make a plain (non-build-step) admin script's
	// UI text translatable: translate it here in PHP, then hand it to JS.
	wp_localize_script(
		'acf-rel-multiselect-admin',
		'acfRelMultiselectI18n',
		array(
			'placeholder' => __( 'Select taxonomies...', 'multi-select-taxonomy-for-acf-relationship' ),
		)
	);

	// Add inline styles
	wp_add_inline_style( 'wp-admin', acf_rel_multiselect_get_inline_styles() );
}

/**
 * Inline CSS styles for better multi-select appearance
 *
 * @return string
 */
function acf_rel_multiselect_get_inline_styles() {
	return <<<'CSS'
/* ACF Relationship Multi-Select Filter Styles */
.acf-relationship .filter.-taxonomy select[multiple] {
	height: auto;
	min-height: 36px;
}

.acf-relationship .filter.-taxonomy .select2-container--default .select2-selection--multiple {
	min-height: 36px;
	padding: 2px 5px;
}

.acf-relationship .filter.-taxonomy .select2-container--default.select2-container--focus .select2-selection--multiple {
	border-color: #2e7d32;
	box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1);
}

.acf-relationship .filter.-taxonomy .select2-container--default .select2-selection--multiple .select2-selection__choice {
	background-color: #f0f0f0;
	border: 1px solid #ccc;
	border-radius: 3px;
	padding: 3px 6px;
	margin: 2px 2px 2px 0;
}

.acf-relationship .filter.-taxonomy .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
	color: #999;
	margin-right: 4px;
}

.acf-relationship .filter.-taxonomy .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
	color: #333;
}
CSS;
}

/**
 * Fires very early on ACF's own "acf/fields/relationship/query" AJAX action,
 * before ACF's core relationship field class reads $_POST['taxonomy'].
 *
 * When our JS turns the taxonomy filter into a real multi-select, the browser
 * (via jQuery's default array serialization) sends MULTIPLE values for the
 * same field as taxonomy[]=a&taxonomy[]=b, which PHP collects into
 * $_POST['taxonomy'] as an array. ACF core, however, expects a single string
 * and calls explode(':', $_POST['taxonomy']) - on PHP 8 that is a fatal
 * TypeError when given an array. So we stash the real selections somewhere
 * safe and rewrite $_POST['taxonomy'] to a single harmless value ('all')
 * before ACF's own handler ever runs.
 */
function acf_rel_multiselect_preprocess_ajax() {
	if ( isset( $_POST['taxonomy'] ) && is_array( $_POST['taxonomy'] ) ) {
		$GLOBALS['acf_rel_multiselect_selected'] = array_map( 'sanitize_text_field', wp_unslash( $_POST['taxonomy'] ) );

		// Prevent ACF core from fataling on an array where it expects a string.
		$_POST['taxonomy'] = 'all';
	}
}

/**
 * Handle multiple taxonomy selections in relationship field queries
 *
 * This builds a "faceted filter" tax_query:
 * - Multiple terms selected WITHIN the same taxonomy are combined with OR
 *   (e.g. Month = February OR March - show posts in either month).
 * - Terms selected ACROSS different taxonomies are combined with AND
 *   (e.g. Year = 2007 AND Month = February - show posts that match both).
 *
 * Without this grouping, selecting a term from a second taxonomy would
 * widen the match (OR) instead of narrowing it (AND), which looks like
 * "the filter stops working" as soon as a second taxonomy is touched.
 *
 * Each selected value is in the format "taxonomy:term_slug" - this matches
 * the option values ACF itself renders in the taxonomy filter dropdown,
 * which use the term SLUG, not the numeric term ID.
 *
 * @param array $args     The WP_Query arguments
 * @param array $field    The ACF field array
 * @param int   $post_id  The post ID
 * @return array Modified query arguments
 */
function acf_rel_multiselect_handle_multiple_taxonomies( $args, $field, $post_id ) {
	// Only process if this is a relationship field
	if ( 'relationship' !== $field['type'] ) {
		return $args;
	}

	// Check if the field has taxonomy filters configured
	if ( empty( $field['filters'] ) || ! in_array( 'taxonomy', $field['filters'], true ) ) {
		return $args;
	}

	// Only act when our preprocess step actually stashed multiple values.
	if ( empty( $GLOBALS['acf_rel_multiselect_selected'] ) || ! is_array( $GLOBALS['acf_rel_multiselect_selected'] ) ) {
		return $args;
	}

	$selected_taxonomies = array_filter( $GLOBALS['acf_rel_multiselect_selected'] );

	if ( empty( $selected_taxonomies ) ) {
		// Empty multi-select - return all posts (no filter)
		return $args;
	}

	// Group selected terms by taxonomy. Each selected value is in the
	// format "taxonomy:term_slug".
	$grouped = array();

	foreach ( $selected_taxonomies as $tax_value ) {
		if ( strpos( $tax_value, ':' ) === false ) {
			continue;
		}

		list( $taxonomy, $term_slug ) = explode( ':', $tax_value, 2 );
		$taxonomy  = sanitize_key( trim( $taxonomy ) );
		$term_slug = sanitize_title( trim( $term_slug ) );

		if ( $taxonomy && $term_slug && taxonomy_exists( $taxonomy ) ) {
			if ( ! isset( $grouped[ $taxonomy ] ) ) {
				$grouped[ $taxonomy ] = array();
			}
			$grouped[ $taxonomy ][] = $term_slug;
		}
	}

	if ( empty( $grouped ) ) {
		return $args;
	}

	// Build one tax_query clause per taxonomy. Multiple terms in the same
	// taxonomy use 'IN' (OR within that taxonomy).
	$tax_queries = array();

	foreach ( $grouped as $taxonomy => $term_slugs ) {
		$tax_queries[] = array(
			'taxonomy' => $taxonomy,
			'field'    => 'slug',
			'terms'    => array_values( array_unique( $term_slugs ) ),
			'operator' => 'IN',
		);
	}

	// Combine the per-taxonomy clauses with AND (a post must match every
	// selected taxonomy), unless only one taxonomy was involved.
	if ( count( $tax_queries ) === 1 ) {
		$args['tax_query'] = array( $tax_queries[0] );
	} else {
		$args['tax_query'] = array_merge( array( 'relation' => 'AND' ), $tax_queries );
	}

	return $args;
}

/**
 * Add plugin action links in Plugins page
 *
 * @param array $links
 * @return array
 */
function acf_rel_multiselect_action_links( $links ) {
	// Add settings link if ACF is active
	if ( acf_rel_multiselect_is_acf_active() ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'edit.php?post_type=acf-field-group' ) ),
			esc_html__( 'ACF Field Groups', 'multi-select-taxonomy-for-acf-relationship' )
		);
		array_unshift( $links, $settings_link );
	}

	return $links;
}

/**
 * Plugin activation hook
 */
register_activation_hook( __FILE__, 'acf_rel_multiselect_activate' );

function acf_rel_multiselect_activate() {
	// Check if ACF (free or Pro) is installed
	if ( ! acf_rel_multiselect_is_acf_active() ) {
		// Deactivate the plugin
		deactivate_plugins( ACF_REL_MULTISELECT_BASENAME );

		// Show error message
		wp_die(
			wp_kses_post(
				'<p><strong>ACF Relationship Multi-Select Filter:</strong></p>' .
				'<p>This add-on requires Advanced Custom Fields (free or Pro) to be installed and activated.</p>' .
				'<p>Please <a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">install and activate Advanced Custom Fields</a> first.</p>'
			),
			'Advanced Custom Fields Required',
			array( 'back_link' => true )
		);
	}
}

/**
 * Initialize plugin on admin_init hook (better timing than plugins_loaded)
 * This ensures all plugins have fully loaded before checking for ACF
 */
add_action( 'admin_init', 'acf_rel_multiselect_init', 5 );

/**
 * Load plugin translations
 */
add_action( 'plugins_loaded', function() {
	load_plugin_textdomain(
		'multi-select-taxonomy-for-acf-relationship',
		false,
		dirname( ACF_REL_MULTISELECT_BASENAME ) . '/languages/'
	);
}, 5 );
