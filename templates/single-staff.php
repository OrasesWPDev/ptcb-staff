<?php
/**
 * Template for displaying single staff posts
 *
 * @package PTCB_Staff
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

get_header();
?>
    <main id="main" class="<?php echo esc_attr(flatsome_main_classes()); ?>">
        <!-- Header Block (full width) -->
        <div class="ptcb-staff-section-wrapper ptcb-staff-header">
			<?php echo do_shortcode('[block id="single-staff-header"]'); ?>
        </div>

        <!-- Start the Loop -->
		<?php while (have_posts()) : the_post(); ?>

            <!-- Staff Bio Section with Featured Image -->
            <div class="ptcb-staff-bio-section">
                <div class="container"> <!-- Added container class here -->
                    <div class="row">
                        <!-- Featured Image Column (Left) -->
                        <div class="large-4 medium-4 small-12 col ptcb-staff-featured-image-column">
							<?php if (has_post_thumbnail()) : ?>
                                <div class="ptcb-staff-featured-image">
									<?php the_post_thumbnail('large', array('class' => 'ptcb-staff-profile-image')); ?>
                                </div>
							<?php endif; ?>
                        </div>
                        <!-- Bio Content Column (Right) -->
                        <div class="large-8 medium-8 small-12 col ptcb-staff-bio-column">
                            <div class="ptcb-staff-bio-content">
								<?php
								// Display company title if it exists
								$company_title = PTCB_Staff_Helpers::get_company_title();
								if (!empty($company_title)) {
									echo '<div class="ptcb-staff-single-company-title">' . esc_html($company_title) . '</div>';
								}

								// Check if there's content in the editor
								$content = get_the_content();
								if (!empty($content)) {
									the_content();
								} else {
									// Display fallback message if no content
									echo '<h2>' . get_the_title() . '</h2>';
									if (!empty($company_title)) {
										echo '<div class="ptcb-staff-single-company-title">' . esc_html($company_title) . '</div>';
									}
									echo '<p class="ptcb-staff-no-bio">No bio information at this time. Check back later.</p>';
								}
								?>
                            </div>
                        </div>
                    </div>
                </div> <!-- Closing container div -->
            </div>

		<?php endwhile; // End of the loop. ?>
    </main>
<?php get_footer(); ?>