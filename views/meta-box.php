<?php
use WPKB\Options;

/** @var Options $opts */
defined( 'ABSPATH' ) or exit;

$hidden_from_archive = get_post_meta( $post->ID, 'hidden_from_archive', true );
?>

<table class="form-table">
	<tr valign="top">
		<th><?php _e( 'Visibility', 'wp-knowledge-base' ); ?></th>
		<td>
			<label>
				<input type="checkbox" name="hidden_from_archive" value="1" <?php checked( $hidden_from_archive ); ?> /> &nbsp;
				<?php _e( 'Hidden from archive pages?', 'wp-knowledge-base' ); ?>
			</label>
		</td>
	</tr>
</table>