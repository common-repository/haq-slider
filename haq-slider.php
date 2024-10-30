<?php
/*
Plugin Name: HAQ Slider
Plugin URI: https://husain25.wordpress.com/plugins/haq-slider
Description: This plugin creates an image slideshow from the images you upload using the jQuery HAQ Slider plugin.
Version: 2.0.1
Author: Husain Ahmed
Author URI: https://husain25.wordpress.com/
*/
	/**
	 * HAQSLIDER_PATH directory
	 */
	define( 'HAQSLIDER_PATH', WP_PLUGIN_DIR . '/' . basename( dirname( __FILE__ ) ) );

	/**
	 * Load plugin
	 */
	require_once HAQSLIDER_PATH . '/function.php';

	add_action('admin_menu', 'haqSliderRegisterMenu');
	function haqSliderRegisterMenu() {
		add_menu_page('HAQ Slider', 'HAQ Slider', 'manage_options', 'haq-slider', 'haqSliderAdminPage' );
		add_submenu_page('haq-slider', 'Settings', 'Settings', 'manage_options','haq_slider_settings', 'haqSliderAdminSettings' );
	}

	//	handle the uploaded images,
	function haqSliderHandleUpload() {
		global $haq_settings, $haqSliderImage;
		
		//	upload the image
		$sliderfile = $_FILES['haq_slider'];
		$upload = wp_handle_upload($sliderfile, 0);
		extract($upload);
		$uploadDirPath = str_replace(basename($file), '', $url);
		list($imageWidth, $imageHeight) = getimagesize($file);
		
		if(strpos($type, 'image') === FALSE) {
			unlink($file); 
			echo '<div class="error" id="message"><p>Sorry, Uploaded file does not seem to be a valid image. Please try again.</p></div>';
			return;
		}
		
		//	validate image width/height
		if($imageWidth < $haq_settings['img_width'] || $imageHeight < $haq_settings['img_height']) {
			unlink($file); // delete the image
			echo '<div class="error" id="message"><p>Sorry, This image does not meet the minimum height/width. Please upload another image</p></div>';
			return;
		}
		
		//	if the image is larger than the width/height of requirements.
		if($imageWidth > $haq_settings['img_width'] || $imageHeight > $haq_settings['img_height']) {
		
			$resizedImage = image_resize($file, $haq_settings['img_width'], $haq_settings['img_height'], true, 'resized');
			$resized_url = $uploadDirPath . basename($resizedImage);
			//	delete the original
			unlink($file);
			$file 	= $resizedImage;
			$url 	= $resized_url;
		}
		
		//	create thumbnail image
		$thumb_height = round((100 * $haq_settings['img_height']) / $haq_settings['img_width']);
		if(isset($upload['file'])) {
			$thumbnailImage = image_resize($file, 100, $thumb_height, true, 'thumb');
			$thumbnailImage_url = $uploadDirPath . basename($thumbnailImage);
		}
		
		$time = date('YmdHis');
		$haqSliderImage[$time] = array(
			'id' => $time,
			'file' => $file,
			'file_url' => $url,
			'thumbnail' => $thumbnailImage,
			'thumbnail_url' => $thumbnailImage_url,
			'image_links_to' => ''
		);
		
		$haqSliderImage['update'] = 'Added';
		update_option('haq_slider_images', $haqSliderImage);
	}



	function haqSliderFunction($args = array(), $content = null) {
		global $haq_settings, $haqSliderImage;
		$args = wp_parse_args($args, $haq_settings);
		$newline = "\n";
		echo '<div id="'.$haq_settings['div'].'">'.$newline;	
		foreach((array)$haqSliderImage as $image => $data) {
			if($data['image_links_to'])
			echo '<a href="'.$data['image_links_to'].'">';			
			echo '<img src="'.$data['file_url'].'" width="'.$haq_settings['img_width'].'" height="'.$haq_settings['img_height'].'" class="'.$data['id'].'" alt="" />';			
			if($data['image_links_to'])
			echo '</a>';			
			echo $newline;
		}		
		echo '</div>'.$newline;
	}

	add_shortcode('haq_slider', 'haqSliderShortcodeFunction');
	function haqSliderShortcodeFunction($atts) {
		ob_start();
		haqSliderFunction();
		$output = ob_get_clean();
		return $output;
		
	}

	add_action('wp_print_scripts', 'haqSliderScriptFunction');
	function haqSliderScriptFunction() {
		if(!is_admin())
		wp_enqueue_script( 'haq_slider', plugin_dir_url( __FILE__ ) . 'media/js/haqslider.all.min.js' );
	}

	add_action('wp_footer', 'haqSliderArgeFunction', 15);
	function haqSliderArgeFunction() {
		global $haq_settings; ?>

		<?php if($haq_settings['rotate']) : ?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			$("#<?php echo $haq_settings['div']; ?>").cycle({ 
				fx: '<?php echo $haq_settings['effect']; ?>',
				timeout: <?php echo ($haq_settings['delay'] * 1000); ?>,
				speed: <?php echo ($haq_settings['duration'] * 1000); ?>,
				pause: 1,
				fit: 1
			});
		});
		</script>
		<?php endif; ?>

	<?php }

	add_action( 'wp_head', 'haqSliderStyle' );
	function haqSliderStyle() { 
		global $haq_settings;
		?>
			
		<style type="text/css" media="screen">
			#<?php echo $haq_settings['div']; ?> {
				position: relative;
				width: <?php echo $haq_settings['img_width']; ?>px;
				height: <?php echo $haq_settings['img_height']?>px;
				height: <?php echo $haq_settings['img_height']?>px;
				margin: 0; padding: 0;
				overflow: hidden;
			}
		</style>
		
	<?php }
	
	
	function haqSliderAdminImage() {
		echo '<div class="wrap">';
		?>
		<?php global $haqSliderImage; ?>
		<?php haqSliderCheckUpdate(); ?>
		<h2><?php _e('HAQ Slider Images', 'haq_slider'); ?></h2>
		
		<table class="form-table">
			<tr valign="top" style="background:#fff;"><th scope="row"> &nbsp; Upload New Slide</th>
				<td>
				<form enctype="multipart/form-data" method="post" action="?page=haq-slider">
					<input type="hidden" name="post_id" id="post_id" value="0" />
					<input type="hidden" name="action" id="action" value="wp_handle_upload" />
					
					<label for="haq_slider">Select a File: </label>
					<input type="file" name="haq_slider" id="haq_slider" />
					<input type="submit" class="button-primary" name="html-upload" value="Upload" />
					<a class="button-primary" style="float:right;" href="?page=haq_slider_settings"> Settings </a>
				</form>
				</td>
			</tr>
		</table><br />
		
		<?php if(!empty($haqSliderImage)) : ?>
		<table class="widefat fixed" cellspacing="0">
			<thead>
				<tr>
					<th scope="col" class="column-slug">Slide Image</th>
					<th scope="col">Image Links To</th>
					<th scope="col" class="column-slug">Actions</th>
				</tr>
			</thead>
			
			<tfoot>
				<tr>
					<th scope="col" class="column-slug">Slide Image</th>
					<th scope="col">Image Links To</th>
					<th scope="col" class="column-slug">Actions</th>
				</tr>
			</tfoot>
			
			<tbody>
			
			<form method="post" action="options.php">
			<?php settings_fields('haq_slider_images'); ?>
			<?php foreach((array)$haqSliderImage as $image => $data) : ?>
				<tr>
					<input type="hidden" name="haq_slider_images[<?php echo $image; ?>][id]" value="<?php echo $data['id']; ?>" />
					<input type="hidden" name="haq_slider_images[<?php echo $image; ?>][file]" value="<?php echo $data['file']; ?>" />
					<input type="hidden" name="haq_slider_images[<?php echo $image; ?>][file_url]" value="<?php echo $data['file_url']; ?>" />
					<input type="hidden" name="haq_slider_images[<?php echo $image; ?>][thumbnail]" value="<?php echo $data['thumbnail']; ?>" />
					<input type="hidden" name="haq_slider_images[<?php echo $image; ?>][thumbnail_url]" value="<?php echo $data['thumbnail_url']; ?>" />
					<th scope="row" class="column-slug"><img src="<?php echo $data['thumbnail_url']; ?>" /></th>
					<td><input type="text" name="haq_slider_images[<?php echo $image; ?>][image_links_to]" value="<?php echo $data['image_links_to']; ?>" size="35" /></td>
					<td class="column-slug"><input type="submit" class="button-primary" value="Update" /> <a href="?page=haq-slider&amp;delete-slide=<?php echo $image; ?>" class="button">Delete</a></td>
				</tr>
			<?php endforeach; ?>
			<input type="hidden" name="haq_slider_images[update]" value="Updated" />
			</form>
			
			</tbody>
		</table>
		<?php endif; ?>

	<?php
	echo '</div>';
	}


	function haqSliderAdminSettings() { ?>

		<?php haqSliderUpdateCheckSettings(); ?>
		<h2><?php _e('HAQ Slider Settings', 'haq-slider'); ?></h2>
		<form method="post" action="options.php">
		<?php settings_fields('haq_slider_settings'); ?>
		<?php global $haq_settings; $options = $haq_settings; ?>
		<table class="form-table">

			<tr valign="top">
				<th scope="row">Transition Status</th>
				<td><input name="haq_slider_settings[rotate]" type="checkbox" value="1" <?php checked('1', $options['rotate']); ?> /> 
					<label for="haq_slider_settings[rotate]">Check this box if you want to enable the transition effects
				</td>
			</tr>
			
			<tr>
				<th scope="row">Transition Delay time</th>
				<td>Length of time (in seconds) you would like each image to be visible:<br />
					<input type="text" name="haq_slider_settings[delay]" value="<?php echo $options['delay'] ?>" size="4" />
					<label for="haq_slider_settings[delay]">second(s)</label>
				</td>
			</tr>
			
			<tr>
				<th scope="row">Transition effect Type</th>
				<td>Please choose effect you would like to use when your slider rotate:<br />
					<select name="haq_slider_settings[effect]">
						<option value="fade" <?php selected('fade', $options['effect']); ?>>fade</option>
						<option value="wipe" <?php selected('wipe', $options['effect']); ?>>wipe</option>
						<option value="scrollUp" <?php selected('scrollUp', $options['effect']); ?>>scroll Up</option>
						<option value="scrollDown" <?php selected('scrollDown', $options['effect']); ?>>scroll Down</option>
						<option value="scrollLeft" <?php selected('scrollLeft', $options['effect']); ?>>scroll Left</option>
						<option value="scrollRight" <?php selected('scrollRight', $options['effect']); ?>>scroll Right</option>
						<option value="cover" <?php selected('cover', $options['effect']); ?>>cover</option>
						<option value="shuffle" <?php selected('shuffle', $options['effect']); ?>>shuffle</option>
					</select>
				</td>
			</tr>
			
			<tr><th scope="row">Transition Length</th>
			<td>Length of time (in seconds) you would like the transition length to be:<br />
				<input type="text" name="haq_slider_settings[duration]" value="<?php echo $options['duration'] ?>" size="4" />
				<label for="haq_slider_settings[duration]">second(s)</label>
			</td></tr>

			<tr><th scope="row">Image Dimensions</th>
			<td>Please input the width of the image rotator:<br />
				<input type="text" name="haq_slider_settings[img_width]" value="<?php echo $options['img_width'] ?>" size="4" />
				<label for="haq_slider_settings[img_width]">px</label>
				<br /><br />
				Please input the height of the image rotator:<br />
				<input type="text" name="haq_slider_settings[img_height]" value="<?php echo $options['img_height'] ?>" size="4" />
				<label for="haq_slider_settings[img_height]">px</label>
			</td></tr>
			
			<tr><th scope="row">Rotator DIV ID</th>
			<td>Please indicate what you would like the rotator DIV ID to be:<br />
				<input type="text" name="haq_slider_settings[div]" value="<?php echo $options['div'] ?>" />
			</td></tr>
			
			<input type="hidden" name="haq_slider_settings[update]" value="UPDATED" />
		
		</table>
		<p class="submit">
		<input type="submit" class="button-primary" value="<?php _e('Save Settings') ?>" />
		</form>
		
		<!-- The Reset Option -->
		<form method="post" action="options.php">
		<?php settings_fields('haq_slider_settings'); ?>
		<?php global $haq_slider_defaults; // use the defaults ?>
		<?php foreach((array)$haq_slider_defaults as $key => $value) : ?>
		<input type="hidden" name="haq_slider_settings[<?php echo $key; ?>]" value="<?php echo $value; ?>" />
		<?php endforeach; ?>
		<input type="hidden" name="haq_slider_settings[update]" value="RESET" />
		<input type="submit" class="button" value="<?php _e('Reset Settings') ?>" />
		</form>
		<!-- End Reset Option -->
		</p>

	<?php
	}



