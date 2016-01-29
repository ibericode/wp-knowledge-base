<?php

use WPKB\Options;

defined( 'ABSPATH' ) or exit;

/** @var Options $opts */
?>
<div class="wrap wpkb-settins">
	<h1><?php _e( 'Knowledge Base Settings', 'wp-knowledge-base' ); ?></h1>

	<form method="post" action="<?php echo admin_url( 'options.php' ); ?>">
		<?php settings_fields( 'wpkb_options' ); ?>

		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<?php _e( 'Archive Page', 'wp-knowledge-base' ); ?>
				</th>
				<td>
					<?php wp_dropdown_pages(
						array(
							'show_option_none' => '-- Default',
							'option_none_value' => 0,
							'name' => 'wpkb[custom_archive_page_id]',
							'selected' => $opts->get('custom_archive_page_id')
						)
					); ?>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>
</div>
