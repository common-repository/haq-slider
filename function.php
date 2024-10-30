<?php

	$haq_slider_default_settings = apply_filters('haq_slider_defaults', array(
			'rotate' => 5,
			'effect' => 'fade',
			'delay' => 3,
			'duration' => 1,
			'img_width' => 600,
			'img_height' => 300,
			'div' => 'rotator'
		));

	$haq_slider_settings 	= get_option('haq_slider_settings');
	$haqSliderImage 		= get_option('haq_slider_images');
	$haq_settings 			= wp_parse_args($haq_slider_settings, $haq_slider_default_settings);

	add_action('admin_init', 'registerHaqSliderSettings');
	function registerHaqSliderSettings() {
		register_setting('haq_slider_images', 'haq_slider_images', 'haqSliderImageValidate');
		register_setting('haq_slider_settings', 'haq_slider_settings', 'haqSliderSettingValidate');
	}

	function haqSliderAdminPage() {
		echo '<div class="wrap">';
			$action 		=  sanitize_text_field($_REQUEST['action']);
			$deleteAction 	=  sanitize_text_field($_REQUEST['delete-slide']);
			if($action == 'wp_handle_upload')
				haqSliderHandleUpload();
			if(isset($deleteAction))
				haqSliderUploadDelete($deleteAction);
			haqSliderAdminImage();
			
		echo '</div>';
	}
	
	
	
	function haqSliderUploadDelete($id) {
		global $haqSliderImage;

		if(!isset($haqSliderImage[$id])) return;
		unlink($haqSliderImage[$id]['file']);
		unlink($haqSliderImage[$id]['thumbnail']);
		$haqSliderImage['update'] = 'Deleted';
		unset($haqSliderImage[$id]);
		update_option('haq_slider_images', $haqSliderImage);
	}


	function haqSliderUpdateCheckSettings() {
		global $haq_settings;
		if(isset($haq_settings['update'])) {
			echo '<div class="updated fade" id="message"><p>HAQ Slider Settings <strong>'.$haq_settings['update'].'</strong></p></div>';
			unset($haq_settings['update']);
			update_option('haq_slider_settings', $haq_settings);
		}
	}
	

	function haqSliderCheckUpdate() {
		global $haqSliderImage;
		if($haqSliderImage['update'] == 'Added' || $haqSliderImage['update'] == 'Deleted' || $haqSliderImage['update'] == 'Updated') {
			echo '<div class="updated fade" id="message"><p>Image(s) '.$haqSliderImage['update'].' Successfully</p></div>';
			unset($haqSliderImage['update']);
			update_option('haq_slider_images', $haqSliderImage);
		}
	}

	
	
	function haqSliderSettingValidate($input) {
		$input['rotate'] = ($input['rotate'] == 1 ? 1 : 0);
		$input['effect'] = wp_filter_nohtml_kses($input['effect']);
		$input['img_width'] = intval($input['img_width']);
		$input['img_height'] = intval($input['img_height']);
		$input['div'] = wp_filter_nohtml_kses($input['div']);
		
		return $input;
	}

	function haqSliderImageValidate($input) {
		foreach((array)$input as $key => $value) {
			if($key != 'update') {
				$input[$key]['file_url'] = clean_url($value['file_url']);
				$input[$key]['thumbnail_url'] = clean_url($value['thumbnail_url']);
				
				if($value['image_links_to'])
				$input[$key]['image_links_to'] = clean_url($value['image_links_to']);
			}
		}
		return $input;
	}
