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
 * Handles registration and processing of shortcodes for displaying staff members
 * in a responsive grid layout via the [ptcb_staff] shortcode.
 */
class PTCB_Staff_Shortcodes {
	/**
	 * Constructor
	 *
	 * Registers the shortcode and initializes logging
	 */
	public function __construct() {
		// Register shortcodes
		add_shortcode('ptcb_staff', array($this, 'staff_shortcode'));
		// Log shortcode registration
		ptcb_staff()->log('PTCB_Staff_Shortcodes initialized and shortcodes registered', 'info');
	}

	/**
	 * Main staff shortcode function
	 *
	 * Processes the [ptcb_staff] shortcode which displays staff members in a grid layout.
	 * Accepts parameters: columns, limit, orderby, order
	 *
	 * @param array $atts Shortcode attributes
	 * @return string Shortcode HTML output
	 */
	public function staff_shortcode($atts) {
		ptcb_staff()->log('Processing [ptcb_staff] shortcode with attributes: ' . print_r($atts, true), 'info');

		// Default attributes
		$atts = shortcode_atts(array(
			'columns' => 3,
			'limit' => -1,
			'orderby' => 'menu_order',
			'order' => 'ASC',
		), $atts, 'ptcb_staff');

		ptcb_staff()->log('Shortcode attributes after defaults applied: ' . print_r($atts, true), 'debug');

		// Sanitize attributes
		$columns = absint($atts['columns']);
		$columns = ($columns < 1 || $columns > 6) ? 3 : $columns; // Constrain to 1-6 columns
		ptcb_staff()->log('Using ' . $columns . ' columns for display', 'debug');

		$limit = intval($atts['limit']);
		ptcb_staff()->log('Posts limit set to: ' . ($limit == -1 ? 'unlimited' : $limit), 'debug');

		$valid_orderby = array('menu_order', 'title', 'date', 'rand');
		$orderby = in_array($atts['orderby'], $valid_orderby) ? $atts['orderby'] : 'menu_order';
		ptcb_staff()->log('Ordering by: ' . $orderby . ' (Valid options: ' . implode(', ', $valid_orderby) . ')', 'debug');

		$order = in_array(strtoupper($atts['order']), array('ASC', 'DESC')) ? strtoupper($atts['order']) : 'ASC';
		ptcb_staff()->log('Order direction: ' . $order, 'debug');

		// Query for staff members
		$args = array(
			'post_type' => 'staff',
			'posts_per_page' => $limit,
			'orderby' => $orderby,
			'order' => $order,
			'post_status' => 'publish',
		);

		ptcb_staff()->log('Staff query arguments: ' . print_r($args, true), 'info');
		$staff_query = new WP_Query($args);
		ptcb_staff()->log('Query found ' . $staff_query->post_count . ' staff members', 'info');

		// Start output buffering for shortcode content
		ob_start();

		if ($staff_query->have_posts()) {
			// Main container with number of columns as a class
			echo '<div class="ptcb-staff-grid ptcb-staff-columns-' . esc_attr($columns) . '">';
			$count = 0;

			while ($staff_query->have_posts()) {
				$staff_query->the_post();
				$post_id = get_the_ID();
				ptcb_staff()->log('Processing staff member: ID=' . $post_id . ', Title=' . get_the_title(), 'debug');

				// Start a new row if needed
				if ($count % $columns === 0) {
					ptcb_staff()->log('Starting new row at position ' . $count, 'debug');
					echo '<div class="ptcb-staff-row">';
				}

				// Get company title using the helper
				$company_title = PTCB_Staff_Helpers::get_company_title($post_id);
				ptcb_staff()->log('Retrieved company title for ID ' . $post_id . ': ' . ($company_title ?: 'empty'), 'debug');

				// Get staff image using helper
				$staff_image = PTCB_Staff_Helpers::get_staff_image($post_id, 'medium', array('class' => 'ptcb-staff-thumbnail'));
				ptcb_staff()->log('Image for staff ID ' . $post_id . ': ' . (empty($staff_image) ? 'not found' : 'found'), 'debug');

				// Individual staff card
				?>
                <div class="ptcb-staff-column ptcb-staff-column-<?php echo esc_attr($count % $columns + 1); ?>">
                    <!-- Staff Member Card - Container for entire staff member display -->
                    <div class="ptcb-staff-card">
                        <a href="<?php the_permalink(); ?>" class="ptcb-staff-card-link">

							<?php if (!empty($staff_image)): ?>
                                <!-- STAFF IMAGE SECTION
									 This section contains the staff member's featured image
									 Class 'ptcb-staff-card-image' can be used for styling the image container -->
                                <div class="ptcb-staff-card-image">
									<?php echo $staff_image; // Displays the featured image with proper attributes ?>
                                </div>
							<?php endif; ?>

                            <!-- STAFF CONTENT SECTION
                                 This section contains all text content for the staff member
                                 Class 'ptcb-staff-card-content' can be used for styling the entire content area -->
                            <div class="ptcb-staff-card-content">
                                <!-- Post Title - Staff Member's Name
                                     Class 'ptcb-staff-card-title' can be used for styling the name/title -->
                                <h3 class="ptcb-staff-card-title"><?php the_title(); ?></h3>

                                <!-- Separator between name and company title
                                     Class 'ptcb-staff-title-separator' can be used for styling this divider -->
                                <hr class="ptcb-staff-title-separator">

								<?php if (!empty($company_title)): ?>
                                    <!-- Company Title - Staff Member's Position/Role
										 Class 'ptcb-staff-card-company-title' can be used for styling the position text -->
                                    <div class="ptcb-staff-card-company-title">
										<?php echo esc_html($company_title); // Displays the ACF company_title field ?>
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
						ptcb_staff()->log('Adding ' . $empty_columns . ' empty columns to complete the last row', 'debug');
						for ($i = 0; $i < $empty_columns; $i++) {
							echo '<div class="ptcb-staff-column ptcb-staff-column-empty"></div>';
						}
					}
					ptcb_staff()->log('Closing row at position ' . $count, 'debug');
					echo '</div><!-- .ptcb-staff-row -->';
				}
			}

			echo '</div><!-- .ptcb-staff-grid -->';
			ptcb_staff()->log('Completed rendering staff grid with ' . $count . ' members', 'info');

		} else {
			// No staff members found - display a friendly message to site visitors
			ptcb_staff()->log('No staff members found to display', 'warning');
			echo '<div class="ptcb-staff-not-found">
                    <p>No staff to show at this time. Please check back later.</p>
                  </div>';
		}

		// Restore original post data
		wp_reset_postdata();

		// Get the buffered content
		$output = ob_get_clean();
		ptcb_staff()->log('Shortcode processing complete, returning ' . strlen($output) . ' characters of HTML', 'info');

		// Return the shortcode output
		return $output;
	}
}