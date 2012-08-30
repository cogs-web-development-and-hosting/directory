<?php

/**
* The template for displaying the Add/edit listing page.
* You can override this file in your active theme.
*
* @license GNU General Public License (Version 2 - GPLv2) {@link http://www.gnu.org/licenses/gpl-2.0.html}
*/

global $wp_query, $wp_taxonomies, $post, $CustomPress_Core;
$listing_data   = '';
$selected_cats  = '';
$error = $dr_error; // get_query_var('dr_error');
$post_statuses = get_post_statuses(); // get the wp post status list
$allowed_statuses = $this->get_options('general'); // Get the ones we allow
$allowed_statuses = array_reverse(array_intersect_key($post_statuses, $allowed_statuses['moderation']) ); //return the reduced list

//Are we adding a Listing?
if(! isset($_REQUEST['post_id']) ){
	//Make an auto-draft so we have a post id to connect attachemnts to. Set global $post_id so media editor can hook up.
	$post_id = wp_insert_post( array( 'post_title' => __( 'Auto Draft' ), 'post_type' => $this->post_type, 'post_status' => 'auto-draft' ) );
	$listing_data = get_post($post_id, ARRAY_A );
	$listing_data['post_title'] = ''; //Have to have a title to insert the auto-save but we don't want it as final.
	$editing = false;
}

//Or are we editing a listing?
if( isset($_REQUEST['post_id']) ){
	$listing_data = get_post(  $_REQUEST['post_id'], ARRAY_A );
	$post_id = $listing_data['ID'];
	$editing = true;
}

if ( isset( $_POST['listing_data'] ) ) $listing_data = $_POST['listing_data'];

require_once(ABSPATH . 'wp-admin/includes/template.php');

$editor_settings =   array(
'wpautop' => true, // use wpautop?
'media_buttons' => true, // show insert/upload button(s)
'textarea_name' => 'listing_data[post_content]', // set the textarea name to something different, square brackets [] can be used here
'textarea_rows' => 10, //get_option('default_post_edit_rows', 10), // rows="..."
'tabindex' => '',
'editor_css' => '', // intended for extra styles for both visual and HTML editors buttons, needs to include the <style> tags, can use "scoped".
'editor_class' => '', // add extra class(es) to the editor textarea
'teeny' => false, // output the minimal editor config used in Press This
'dfw' => false, // replace the default fullscreen with DFW (needs specific css)
'tinymce' => true, // load TinyMCE, can be used to pass settings directly to TinyMCE using an array()
'quicktags' => true // load Quicktags, can be used to pass settings directly to Quicktags using an array()
);

$listing_content = (empty( $listing_data['post_content'] ) ) ? '' : $listing_data['post_content'];

?>
<script type="text/javascript" src="<?php echo $this->plugin_url . 'ui-front/js/jquery.tagsinput.min.js'; ?>" ></script>
<script type="text/javascript" src="<?php echo $this->plugin_url . 'ui-front/js/media-post.js'; ?>" ></script>
<?php if ( !empty( $error ) ): ?>
<br /><div class="error"><?php echo $error . '<br />'; ?></div>
<?php endif; ?>

<div class="dr_update_form">

	<form class="standard-form base" method="post" action="#" enctype="multipart/form-data" id="dr_update_form" >
		<input type="hidden" id="post_id" name="listing_data[ID]" value="<?php echo ( isset( $listing_data['ID'] ) ) ? $listing_data['ID'] : ''; ?>" />
		<input type="hidden" name="post_id" value="<?php echo ( isset( $listing_data['ID'] ) ) ? $listing_data['ID'] : ''; ?>" />

		<?php if(post_type_supports('directory_listing','editor') ): ?>
		<div class="editfield">
			<label for="title"><?php _e( 'Title', $this->text_domain ); ?></label><br />
			<input class="required" type="text" id="title" name="listing_data[post_title]" value="<?php echo ( isset( $listing_data['post_title'] ) ) ? $listing_data['post_title'] : ''; ?>" />
			<p class="description"><?php _e( 'Enter title here.', $this->text_domain ); ?></p>
		</div>
		<?php endif; ?>

		<div class="editfield"><?php echo $this->get_post_image_link($post_id); ?></div>

		<?php if(post_type_supports('directory_listing','editor') ): ?>
		<div>
			<label for="listingcontent"><?php _e( 'Content', $this->text_domain ); ?></label><br />

			<?php if(version_compare(get_bloginfo('version'), 3.3, '>=') ): ?>

			<?php wp_editor( $listing_content, 'listingcontent', $editor_settings); ?>

			<?php else: ?>

			<textarea id="listingcontent" name="listing_data[post_content]" cols="40" rows="5"><?php echo esc_textarea($listing_content); ?></textarea>

			<?php endif; ?>

			<p class="description"><?php _e( 'The content of your listing.', $this->text_domain ); ?></p>
		</div>
		<?php endif; ?>

		<?php if(post_type_supports('directory_listing','excerpt') ): ?>
		<div class="editfield alt">
			<label for="excerpt"><?php _e( 'Excerpt', $this->text_domain ); ?></label><br />
			<textarea id="excerpt" name="listing_data[post_excerpt]" rows="2" ><?php echo (isset( $listing_data['post_excerpt'] ) ) ? esc_textarea($listing_data['post_excerpt']) : ''; ?></textarea>
			<p class="description"><?php _e( 'A short excerpt of your listing.', $this->text_domain ); ?></p>
		</div>
		<?php endif; ?>

		<?php
		//get related hierarchical taxonomies
		$taxonomies = get_object_taxonomies('directory_listing', 'objects');
		$taxonomies = empty($taxonomies) ? array() : $taxonomies;

		//Loop through the taxonomies that apply
		foreach($taxonomies as $taxonomy):
		if( ! $taxonomy->hierarchical) continue;
		$tax_name = $taxonomy->name;
		$labels = $taxonomy->labels;
		//Get this Taxonomies terms
		$selected_cats = array_values( wp_get_post_terms($listing_data['ID'], $tax_name, array('fields' => 'ids') ) );


		?>

		<div id="taxonomy-<?php echo $tax_name; ?>" class="taxonomydiv">
			<label><?php echo $labels->all_items; ?></label>

			<div id="<?php echo $tax_name; ?>_all" class="tax_panel">
				<?php
				$name = ( $tax_name == 'category' ) ? 'post_category' : 'tax_input[' . $tax_name . ']';
				echo "<input type='hidden' name='{$name}[]' value='0' />"; 		// Allows for an empty term set to be sent. 0 is an invalid Term ID and will be ignored by empty() checks.
				?>
				<ul id="<?php echo $tax_name; ?>_checklist" class="list:<?php echo $labels->name; ?> categorychecklist form-no-clear">
					<?php wp_terms_checklist( 0, array( 'taxonomy' => $tax_name, 'selected_cats' => $selected_cats, 'checked_ontop' => false ) ) ?>
				</ul>
			</div>
			<br />
		</div>
		<?php endforeach; ?>

		<div class="clear"></div>

		<?php
		//get related non-hierarchical taxonomies

		//Loop through the taxonomies that apply
		foreach($taxonomies as $tag):
		if( $tag->hierarchical) continue;

		$tag_name = $tag->name;
		$labels = $tag->labels;

		//Get this Taxonomies terms
		$tag_list = strip_tags(get_the_term_list( $listing_data['ID'], $tag_name, '', ',', '' ));

		?>

		<div class="editfield">
			<div id="<?php echo $tag_name; ?>-checklist" class="tagchecklist">
				<label><?php echo $labels->name . ': ' . $labels->add_or_remove_items; ?>
					<input id="tag_<?php echo $tag_name; ?>" name="tag_input[<?php echo $tag_name; ?>]" type="text" value="<?php echo $tag_list?>" />
				</label>
			</div>
			<br />
		</div>
		<script type="text/javascript" > jQuery('#tag_<?php echo $tag_name; ?>').tagsInput({width:'auto'}); </script>
		<?php endforeach; ?>


		<div class="editfield" >
			<label for="title"><?php _e( 'Status', $this->text_domain ); ?></label>
			<div id="status-box">
				<select name="listing_data[post_status]" id="listing_data[post_status]">
					<?php
					foreach($allowed_statuses as $key => $value): ?>

					<option value="<?php echo $key; ?>" <?php selected( ! empty($listing_data['post_status'] ) && $key == $listing_data['post_status'] ); ?> ><?php echo $value; ?></option>

					<?php endforeach; ?>
				</select>
			</div>
			<p class="description"><?php _e( 'Select a status for your Listing.', $this->text_domain ); ?></p>
		</div>

		<?php if( isset( $CustomPress_Core) ) : ?>
		<div class="editfield">
			<?php
			$post->post_type    = 'directory_listing';
			$post->ID           = $listing_data['ID'];
			$CustomPress_Core->display_custom_fields();
			?>
		</div>
		<?php endif; ?>
		<?php if ( !empty( $error ) ): ?>
		<br /><div class="error"><?php echo $error . '<br />'; ?></div>
		<?php endif; ?>

		<div class="submit">
			<?php wp_nonce_field( 'verify' ); ?>
			<input type="submit" value="<?php _e( 'Save Changes', $this->text_domain ); ?>" name="update_listing">

			<input type="button" value="<?php _e( 'Cancel', $this->text_domain ); ?>" onclick="location.href='<?php echo get_permalink($this->my_listings_page_id); ?>'">
		</div>
	</form>

	<script type="text/javascript">jQuery('#dr_update_form').validate();</script>
</div>