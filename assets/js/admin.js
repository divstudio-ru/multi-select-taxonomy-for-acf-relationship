/**
 * Multi-Select Taxonomy for ACF Relationship
 * JavaScript enhancement to convert the taxonomy filter dropdown on ACF
 * Relationship fields into a multi-select.
 *
 * ACF's relationship field JS reads the taxonomy filter's value with
 * $select.val() and hands it to jQuery's AJAX data object. When the select
 * is a native multi-select, $select.val() returns an array, and jQuery's
 * default (non-traditional) serialization automatically sends it as
 * repeated taxonomy[] values. No custom AJAX/fetch interception is needed
 * on the client side - the server side (see the main plugin file) just
 * needs to know how to read multiple taxonomy[] values.
 *
 * @package acf-rel-multiselect
 * @version 1.0.16
 */

(function($) {
	'use strict';

	var isInitialized = false;

	// Translated via wp_localize_script() in the main plugin file (see
	// acf_rel_multiselect_enqueue_admin_scripts()). The fallback covers the
	// unlikely case this script loads before that localization runs.
	var PLACEHOLDER_TEXT = (typeof acfRelMultiselectI18n !== 'undefined' && acfRelMultiselectI18n.placeholder)
		? acfRelMultiselectI18n.placeholder
		: 'Select taxonomies...';

	/**
	 * Initialize multi-select enhancement for relationship fields
	 */
	function initializeMultiSelectFilter() {
		var $selects = $('.acf-relationship .filter.-taxonomy select');

		if ($selects.length === 0) {
			return false;
		}

		$selects.each(function() {
			enhanceTaxonomyFilter($(this));
		});

		return true;
	}

	/**
	 * Enhance a single taxonomy filter to support multi-select
	 *
	 * @param {jQuery} $select The select element to enhance
	 */
	function enhanceTaxonomyFilter($select) {
		// Skip if already enhanced
		if ($select.data('acf-rel-multiselect-enhanced')) {
			return;
		}

		// On a fresh page load, ACF (or the theme) typically already runs its
		// own default Select2 init on this select (single-select mode, with
		// the placeholder option) before our script gets to it. Destroy that
		// FIRST, before we touch attributes/options/value below - otherwise
		// our DOM edits happen while an old Select2 instance is still
		// "watching" the element and caching stale internal state, which can
		// leave the placeholder not showing even though the value is empty.
		if ($select.hasClass('select2-hidden-accessible')) {
			$select.select2('destroy');
		}

		// Add the multiple attribute FIRST, before touching the options.
		// A native single-select always keeps exactly one option selected -
		// if we remove the currently-selected placeholder option while the
		// element is still a single-select, the browser auto-selects the
		// next remaining option (the first real term) to satisfy that rule,
		// and that phantom selection sticks around afterwards ("can't clear
		// the filter, the first term keeps coming back"). A multi-select has
		// no such requirement, so doing this first avoids the quirk.
		$select.attr('multiple', 'multiple');

		// Now it's safe to remove the placeholder ("Select taxonomy") option -
		// it has an empty value and shouldn't be a selectable choice.
		$select.find('option[value=""]').remove();

		// Explicitly clear any selection so the field starts empty (no
		// filter) rather than inheriting whatever the browser considered
		// "selected" before it became a multi-select.
		$select.val(null);

		// Select2 also reads this attribute directly, so set it as a
		// fallback in case anything re-initializes Select2 without passing
		// the placeholder option explicitly.
		$select.attr('data-placeholder', PLACEHOLDER_TEXT);

		$select.data('acf-rel-multiselect-enhanced', true);

		// Initialize Select2 fresh, now that the select is a clean, empty
		// multi-select. ACF field groups are commonly loaded as "classic"
		// metaboxes that WordPress injects via AJAX after the main editor
		// UI renders, and the metabox panel can still be mid-animation
		// (effectively zero width) at that exact moment. Select2 miscalculates
		// its layout - and the placeholder text ends up invisible - if it's
		// initialized while its container isn't actually visible yet. Wait
		// for visibility first rather than assuming the DOM being present
		// means it's ready to measure.
		initSelect2WhenVisible($select);

		// Trigger the relationship field's own change handling so results
		// refresh when the multi-select value changes.
		$select.on('change', function() {
			var $relationship = $select.closest('.acf-relationship');
			if ($relationship.length) {
				$relationship.find('input, select').first().trigger('change');
			}
		});
	}

	/**
	 * Initialize Select2 on a select once it's actually visible (non-zero
	 * dimensions), retrying briefly if it isn't yet. Select2 can miscalculate
	 * width/layout - leaving the placeholder effectively invisible - if
	 * initialized while its container is hidden or still animating into view
	 * (e.g. a WordPress metabox panel that's still loading/expanding).
	 *
	 * @param {jQuery} $select
	 * @param {number} [attemptsLeft]
	 */
	function initSelect2WhenVisible($select, attemptsLeft) {
		if (!$.fn.select2) {
			return;
		}

		attemptsLeft = (typeof attemptsLeft === 'number') ? attemptsLeft : 20;

		if (!$select.is(':visible') && attemptsLeft > 0) {
			setTimeout(function() {
				initSelect2WhenVisible($select, attemptsLeft - 1);
			}, 50);
			return;
		}

		try {
			$select.select2({
				multiple: true,
				placeholder: PLACEHOLDER_TEXT,
				allowClear: true,
				width: '100%',
				minimumResultsForSearch: 0
			});

			// Force Select2 to re-sync its own rendered display with the
			// select's actual (empty) value. ".select2" is a namespaced
			// trigger Select2 itself listens for to refresh its UI - this
			// covers cases where Select2 initialized with leftover internal
			// selection data despite the underlying select already being
			// empty.
			$select.val(null).trigger('change.select2');
		} catch (e) {
			// If Select2 initialization fails for any reason, fall back
			// silently to the native multi-select control.
		}
	}

	/**
	 * Handle dynamically added relationship fields (e.g., in repeaters, flexible content)
	 */
	function setupDynamicFieldObserver() {
		if (typeof acf !== 'undefined' && acf.addAction) {
			acf.addAction('render_field_group', function(group, $group) {
				$group.find('.acf-relationship .filter.-taxonomy select').each(function() {
					enhanceTaxonomyFilter($(this));
				});
			});

			acf.addAction('append', function($el) {
				$el.find('.acf-relationship .filter.-taxonomy select').each(function() {
					enhanceTaxonomyFilter($(this));
				});
			});

			acf.addAction('duplicate_field', function($field) {
				$field.find('.acf-relationship .filter.-taxonomy select').each(function() {
					enhanceTaxonomyFilter($(this));
				});
			});
		}
	}

	$(document).ready(function() {
		if (isInitialized) {
			return;
		}
		isInitialized = true;

		initializeMultiSelectFilter();
		setupDynamicFieldObserver();

		$(document).on('acf/render_fields', function() {
			initializeMultiSelectFilter();
		});
	});

	// Catch any relationship fields added via AJAX-loaded content
	$(document).on('ajaxComplete', function() {
		initializeMultiSelectFilter();
	});

	/**
	 * Expose the enhancer function globally in case manual initialization is needed
	 */
	window.ACFRelMultiselectEnhancer = {
		enhance: enhanceTaxonomyFilter,
		initAll: initializeMultiSelectFilter,
		version: '1.0.14'
	};

})(jQuery);
