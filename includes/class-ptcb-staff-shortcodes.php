<?php
/**
 * Shortcodes for Staff post type
 *
 * @package PTCB_Staff
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * PTCB Staff Shortcodes Class
 *
 * Handles registration and processing of shortcodes
 */
class PTCB_Staff_Shortcodes {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Register shortcodes
		add_shortcode('ptcb_staff', array($this, 'staff_shortcode'));

		// Log shortcode registration
		ptcb_staff()->log('Staff shortcodes registered', 'info');
	}

	/**
	 * Main staff shortcode function
	 *
	 * @param array $atts Shortcode attributes
	 * @return string Shortcode output
	 */
	public function staff_shortcode($atts) {
		// Default attributes
		$atts = shortcode_atts(array(
			'columns' => 3,
			'limit' => -1,
			'orderby' => 'menu_order',
			'order' => 'ASC',
		), $atts, 'ptcb_staff');

		// Sanitize attributes
		$columns = absint($atts['columns']);
		$columns = ($columns < 1 || $columns > 6) ? 3 : $columns; // Constrain to 1-6 columns

		$limit = intval($atts['limit']);

		$orderby = in_array($atts['orderby'], array('menu_order', 'title', 'date', 'rand'))
			? $atts['orderby']
			: 'menu_order';

		$order = in_array(strtoupper($atts['order']), array('ASC', 'DESC'))
			? strtoupper($atts['order'])
			: 'ASC';

		// Query for staff members
		$args = array(
			'post_type' => 'staff',
			'posts_per_page' => $limit,
			'orderby' => $orderby,
			'order' => $order,
			'post_status' => 'publish',
		);

		$staff_query = new WP_Query($args);

		// Log the query if in debug mode
		ptcb_staff()->log('Staff shortcode query: ' . print_r($args, true), 'info');

		// Start output buffering for shortcode content
		ob_start();

		if ($staff_query->have_posts()) {
			// Main container with number of columns as a class
			echo '<div class="ptcb-staff-grid ptcb-staff-columns-' . esc_attr($columns) . '">';

			$count = 0;

			while ($staff_query->have_posts()) {
				$staff_query->the_post();
				$post_id = get_the_ID();

				// Start a new row if needed
				if ($count % $columns === 0) {
					echo '<div class="ptcb-staff-row">';
				}

				// Get company title using the helper
				$company_title = PTCB_Staff_Helpers::get_company_title($post_id);

				// Get staff image using helper
				$staff_image = PTCB_Staff_Helpers::get_staff_image($post_id, 'medium', array('class' => 'ptcb-staff-thumbnail'));

				// Individual staff card
				?>
                <div class="ptcb-staff-column ptcb-staff-column-<?php echo esc_attr($count % $columns + 1); ?>">
                    <div class="ptcb-staff-card">
                        <a href="<?php the_permalink(); ?>" class="ptcb-staff-card-link">
							<?php if (!empty($staff_image)): ?>
                                <div class="ptcb-staff-card-image">
									<?php echo $staff_image; ?>
                                </div>
							<?php endif; ?>

                            <div class="ptcb-staff-card-content">
                                <h3 class="ptcb-staff-card-title"><?php the_title(); ?></h3>

								<?php if (!empty($company_title)): ?>
                                    <div class="ptcb-staff-card-company-title">
										<?php echo esc_html($company_title); ?>
                                    </div>
								<?php endif; ?>
                            </div>
                        </a>
                    </div>
                </div>
				<?php

				$count++;

				// Close the row if needed
				if ($count % $columns === 0 || $count === $staff_query->post_count) {
					// Add empty columns if needed to fill the last row
					if ($count === $staff_query->post_count && $count % $columns !== 0) {
						$empty_columns = $columns - ($count % $columns);
						for ($i = 0; $i < $empty_columns; $i++) {
							echo '<div class="ptcb-staff-column ptcb-staff-column-empty"></div>';
						}
					}

					echo '</div><!-- .ptcb-staff-row -->';
				}
			}

			echo '</div><!-- .ptcb-staff-grid -->';

		} else {
			echo '<div class="ptcb-staff-not-found">No staff members found.</div>';
		}

		// Restore original post data
		wp_reset_postdata();

		// Get the buffered content
		$output = ob_get_clean();

		// Return the shortcode output
		return $output;
	}
}