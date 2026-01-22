<?php

namespace Pantheon\ContentPublisher;

if (!defined('ABSPATH')) {
	exit;
}

use WP_Post;

/**
 * PCC Posts class to override WP_Posts_List_Table.
 */
class PccPostsListTable extends \WP_Posts_List_Table
{
	/**
	 * Outputs the post title with a custom logo if the post has a PCC ID meta.
	 *
	 * @param WP_Post $post The current post object.
	 */
	public function columnTitle($post)
	{
		// Check if the post is linked
		if ($this->isLinkedPost($post)) {
			// Output the PCC logo HTML
			$allowed_tags = [
				'svg' => [
					'class'    => true,
					'width'    => true,
					'height'   => true,
					'viewBox'  => true,
					'fill'     => true,
					'xmlns'    => true,
				],
				'path' => [
					'd'    => true,
					'fill' => true,
				],
			];
			echo wp_kses($this->pccLogo(), $allowed_tags);
			echo '<div class="pcc-post-title-container">';
		}

		// Call the parent method to output the post title
		parent::column_title($post);

		// Close the div container if it was opened
		if ($this->isLinkedPost($post)) {
			echo '</div>';
		}
	}


	/**
	 * Checks if the post has the 'cpub_id' meta-key.
	 *
	 * This method determines if the custom condition is met based on the existence
	 * of a specific post-meta key.
	 *
	 * @param   WP_Post  $post  The current WP_Post object.
	 *
	 * @return bool Whether the 'cpub_id' meta-key exists.
	 */
	protected function isLinkedPost($post)
	{
		return (bool) get_post_meta($post->ID, CPUB_CONTENT_META_KEY, true);
	}

	/**
	 * Generates the HTML for the PCC post logo.
	 *
	 * This method returns the HTML string for the SVG logo to be displayed
	 * when the post has the 'cpub_id' meta-key.
	 *
	 * @return string The HTML for the SVG logo.
	 */
	private function pccLogo()
	{
		// phpcs:ignore Generic.Files.LineLength.TooLong
		return '<svg class="pcc-icon" width="6" height="12" viewBox="0 0 6 12" fill="none" xmlns="http://www.w3.org/2000/svg"> <path d="M0.571429 0L1.71429 2.59063H0.25974L0.727273 3.71487H3.68831L0.571429 0Z" fill="#FFDC28"/> <path d="M4.44156 9.40937L3.94806 8.28513H3.27273L1.87013 5.0835H1.27273L2.67533 8.28513H0.961043L4.12988 12L2.98702 9.40937H4.44156Z" fill="#FFDC28"/> <path d="M4.83117 7.13645H2.98701L3.37663 8.01629H4.83117C4.85715 8.01629 4.96104 7.96741 4.96104 7.57637C4.93507 7.18533 4.85715 7.13645 4.83117 7.13645Z" fill="#23232D"/> <path d="M5.01299 6.08554H2.54545L2.93506 6.96538H5.01299C5.03896 6.96538 5.14286 6.9165 5.14286 6.52546C5.11688 6.13442 5.03896 6.08554 5.01299 6.08554Z" fill="#23232D"/> <path d="M4.83117 4.86354C4.85714 4.86354 4.96104 4.81466 4.96104 4.42362C4.96104 4.03258 4.88312 3.9837 4.83117 3.9837H2.80519L3.1948 4.86354H4.83117Z" fill="#23232D"/> <path d="M3.63637 5.91446H4.98702C5.01299 5.91446 5.11689 5.86558 5.11689 5.47454C5.11689 5.0835 5.03896 5.03462 4.98702 5.03462H3.24676L3.63637 5.91446Z" fill="#23232D"/> <path d="M4.83117 7.13645H2.98701L3.37663 8.01629H4.83117C4.85715 8.01629 4.96104 7.96741 4.96104 7.57637C4.93507 7.18533 4.85715 7.13645 4.83117 7.13645Z" fill="#23232D"/> <path d="M5.01299 6.08554H2.54545L2.93506 6.96538H5.01299C5.03896 6.96538 5.14286 6.9165 5.14286 6.52546C5.11688 6.13442 5.03896 6.08554 5.01299 6.08554Z" fill="#23232D"/> <path d="M1.4026 5.91446L0.961039 4.86354H1.97403L2.44156 5.91446H3.4026L2.54546 3.9837H0.441559C0.285714 3.9837 0.181818 3.9837 0.103896 4.20366C0.0259739 4.4725 0 4.98574 0 5.98778C0 6.98982 -1.00631e-07 7.50305 0.103896 7.77189C0.181818 7.99185 0.25974 7.99185 0.441559 7.99185H2.28572L1.4026 5.91446Z" fill="#23232D"/> </svg>';
	}
}
