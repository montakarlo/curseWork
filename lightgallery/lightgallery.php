<?php
/*
	Plugin Name: LightGallery
	Plugin URI:  https://dzine.io/products/lightgallery-wp-plugin/?WPPluginFree
	Description: A customizable, modular, responsive, lightbox gallery plugin for jQuery
	Version:     1.0.2
	Author:      LightGallery Team
	Author URI:  http://sachinchoolur.github.io/lightGallery/
*/


	if ( ! class_exists( 'LightGallery' ) )
	{
		class LightGallery
		{
			private static $Instance;


			public static function Instance()
			{
				if ( ! self::$Instance )
				{
					self::$Instance = new self();
				}

				return self::$Instance;
			}


			public function __construct()
			{
				$this->Version = '1.0.2';
				$this->LibraryVersion = '1.6.6';
				$this->PluginFile = __FILE__;
				$this->PluginName = 'LightGallery';
				$this->PluginPath = trailingslashit( dirname( $this->PluginFile ) );
				$this->PluginURL = trailingslashit( get_bloginfo( 'wpurl' ) . '/wp-content/plugins/' . dirname( plugin_basename( $this->PluginFile ) ) );


				if ( self::$Instance )
				{
					wp_die( sprintf( '<strong>%s:</strong> Please use the <code>%s::Instance()</code> method for initialization.', $this->PluginName, __CLASS__ ) );
				}


				$this->SettingsName = 'LightGallery';
				$this->Settings = get_option( $this->SettingsName );
				$this->SettingsDefaults = array
				(
					'Version' => $this->Version,
					'Options' => array
					(
						'mode' => 'lg-slide',
						'cssEasing' => 'ease',
						'easing' => 'linear',
						'speed' => 600,
						'height' => '100%',
						'width' => '100%',
						'backdropDuration' => 150,
						'hideBarsDelay' => 6000,
						'useLeft' => 0,
						'closable' => 1,
						'loop' => 1,
						'escKey' => 1,
						'keyPress' => 1,
						'controls' => 1,
						'slideEndAnimatoin' => 1,
						'hideControlOnEnd' => 0,
						'mousewheel' => 1,
						'getCaptionFromTitleOrAlt' => 1,
						'preload' => 1,
						'showAfterLoad' => 1,
						'index' => 0,
						'iframeMaxWidth' => '100%',
						'download' => 1,
						'counter' => 1,
						'enableDrag' => 1,
						'enableTouch' => 1,
					),
				);


				register_activation_hook( $this->PluginFile, array( $this, 'HookActivation' ) );


				add_filter( 'plugin_action_links_' . plugin_basename( $this->PluginFile ), array( $this, 'HookPluginActionLinks' ) );
				add_action( 'admin_init', array( $this, 'HookAdminInit' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'HookAdminEnqueueScripts' ), 10, 1 );
				add_action( 'admin_menu', array( $this, 'HookAdminMenu' ) );
				add_action( 'add_meta_boxes', array( $this, 'HookMetaBoxes' ), 10, 2 );

				add_action( 'wp_enqueue_scripts', array( $this, 'HookWPEnqueueScripts' ) );
				add_action( 'wp_head', array( $this, 'HookWPHead' ) );
				add_filter( 'content_save_pre', array( $this, 'HookContentSavePre' ), 10, 1 );
				add_shortcode( 'lightgallery', array( $this, 'HookShortCode' ) );
			}


			public function HookActivation()
			{
				if ( ! current_user_can( 'activate_plugins' ) )
				{
					return;
				}

				$SettingsCurrent = get_option( $this->SettingsName );

				if ( is_array( $SettingsCurrent ) )
				{
					$Settings = array_replace_recursive( $this->SettingsDefaults, $SettingsCurrent );
					$Settings = array_intersect_key( $Settings, $this->SettingsDefaults );

					$Settings['Version'] = $this->Version;

					update_option( $this->SettingsName, $Settings );
				}
				else
				{
					add_option( $this->SettingsName, $this->SettingsDefaults );
				}

				$this->Settings = get_option( $this->SettingsName );
			}


			public function HookPluginActionLinks( $Links )
			{
				$Link = "<a href=\"options-general.php?page=$this->SettingsName\">Settings</a>";

				array_push( $Links, $Link );

				return $Links;
			}


			public function HookAdminInit()
			{
				if ( version_compare( $this->Settings['Version'], $this->Version, '<' ) )
				{
					$this->HookActivation();

					add_action( 'admin_notices', create_function( '', "print '<div class=\'notice notice-success is-dismissible\'> <p><strong>$this->PluginName:</strong> Plugin settings has been successfully updated.</p> </div>';" ) );
				}
			}


			public function HookAdminEnqueueScripts( $Hook )
			{
				if ( $Hook == 'post.php' || $Hook == 'post-new.php' )
				{
					if ( function_exists( 'wp_enqueue_media' ) )
					{
						wp_enqueue_media();
					}

					wp_enqueue_script( "$this->SettingsName-Script-Admin", $this->PluginURL . 'includes/script.js', array( 'jquery' ), $this->Version );
				}
			}


			public function HookAdminMenu()
			{
				$PageHook = add_submenu_page( 'options-general.php', $this->PluginName, $this->PluginName, 'manage_options', $this->PluginName, array( $this, 'HookAdminMenuCallback' ) );

				add_action( "load-$PageHook", array( $this, 'HookAdminMenuLoad' ) );
			}


			public function HookAdminMenuLoad()
			{
				$Screen = get_current_screen();

				$Screen->add_help_tab( array
				(
					'id' => $this->SettingsName,
					'title' => 'Overview',
					'content' => '<p>These settings are provided by the plugin LightGallery and determines the Media Lightbox behaviour controlled by LightGallery.</p>',
				));

				$Screen->set_help_sidebar
				(
					"<p><strong>For more information:</strong></p> <p><a href=\"https://wordpress.org/support/plugin/lightgallery\">Support Forum</a></p> <p><a href=\"https://dzine.io/products/lightgallery-wp-plugin/?WPPluginFree\">Purchase Premium License</a></p>"
				);
			}


			public function HookAdminMenuCallback()
			{
				if ( ! current_user_can( 'manage_options' ) )
				{
					wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
				}

				if ( isset( $_POST['action'] ) && ! wp_verify_nonce( $_POST['nonce'], $this->SettingsName ) )
				{
					wp_die( __( 'Security check failed! Settings not saved.' ) );
				}


				global $wp_version;

				if ( version_compare( $wp_version, '3.5', '<' ) )
				{
					print '<div class="notice notice-warning"> <p>WordPress 3.5 is required for this plugin to work properly.</p> </div>';
				}


				if ( isset( $_POST['submit'] ) )
				{
					foreach ( $_POST as $Key => $Value )
					{
						if ( array_key_exists( $Key, $this->SettingsDefaults ) )
						{
							if ( is_array( $Value ) )
							{
								array_walk_recursive( $Value, array( $this, 'TrimByReference' ) );
							}
							else
							{
								$Value = trim( $Value );
							}

							$this->Settings[$Key] = $Value;
						}
					}

					if ( update_option( $this->SettingsName, $this->Settings ) )
					{
						print '<div class="notice notice-success is-dismissible"> <p><strong>Settings saved.</strong></p> </div>';
					}
				}

				?>

					<style>
						.nav-tab-content { display: none; }
						.nav-tab-content-active { display: block; }
					</style>


					<div class="wrap">

						<h2><?php print $this->PluginName; ?> Settings</h2>


						<form method="post" action="">


							<h2 class="nav-tab-wrapper">
								<a href="#Gallery" class="nav-tab nav-tab-active" data-tab="Gallery">Gallery</a>
								<a href="#Help" class="nav-tab" data-tab="Help">Help</a>
							</h2>


							<div class="nav-tab-content nav-tab-content-active" data-tab="Gallery">

								<table class="form-table">

									<tr valign="top">
										<th scope="row">
											Transition Mode
										</th>
										<td>
											<select name="Options[mode]">
												<option value="lg-slide" <?php selected( $this->Settings['Options']['mode'], 'lg-slide' ); ?>>Slide</option>
												<option value="lg-fade" <?php selected( $this->Settings['Options']['mode'], 'lg-fade' ); ?>>Fade</option>
												<option value="lg-zoom-in" <?php selected( $this->Settings['Options']['mode'], 'lg-zoom-in' ); ?>>Zoom In</option>
												<option value="lg-zoom-in-big" <?php selected( $this->Settings['Options']['mode'], 'lg-zoom-in-big' ); ?>>Zoom In Big</option>
												<option value="lg-zoom-out" <?php selected( $this->Settings['Options']['mode'], 'lg-zoom-out' ); ?>>Zoom Out</option>
												<option value="lg-zoom-out-big" <?php selected( $this->Settings['Options']['mode'], 'lg-zoom-out-big' ); ?>>Zoom Out Big</option>
												<option value="lg-zoom-out-in" <?php selected( $this->Settings['Options']['mode'], 'lg-zoom-out-in' ); ?>>Zoom Out In</option>
												<option value="lg-zoom-in-out" <?php selected( $this->Settings['Options']['mode'], 'lg-zoom-in-out' ); ?>>Zoom In Out</option>
												<option value="lg-soft-zoom" <?php selected( $this->Settings['Options']['mode'], 'lg-soft-zoom' ); ?>>Soft Zoom</option>
												<option value="lg-scale-up" <?php selected( $this->Settings['Options']['mode'], 'lg-scale-up' ); ?>>Scale Up</option>
												<option value="lg-slide-circular" <?php selected( $this->Settings['Options']['mode'], 'lg-slide-circular' ); ?>>Slide Circular</option>
												<option value="lg-slide-circular-vertical" <?php selected( $this->Settings['Options']['mode'], 'lg-slide-circular-vertical' ); ?>>Slide Circular Vertical</option>
												<option value="lg-slide-vertical" <?php selected( $this->Settings['Options']['mode'], 'lg-slide-vertical' ); ?>>Slide Vertical</option>
												<option value="lg-slide-vertical-growth" <?php selected( $this->Settings['Options']['mode'], 'lg-slide-vertical-growth' ); ?>>Slide Vertical Growth</option>
												<option value="lg-slide-skew-only" <?php selected( $this->Settings['Options']['mode'], 'lg-slide-skew-only' ); ?>>Slide Skew Only</option>
												<option value="lg-slide-skew-only-rev" <?php selected( $this->Settings['Options']['mode'], 'lg-slide-skew-only-rev' ); ?>>Slide Skew Only Reverse</option>
												<option value="lg-slide-skew-only-y" <?php selected( $this->Settings['Options']['mode'], 'lg-slide-skew-only-y' ); ?>>Slide Skew Only Y</option>
												<option value="lg-slide-skew-only-y-rev" <?php selected( $this->Settings['Options']['mode'], 'lg-slide-skew-only-y-rev' ); ?>>Slide Skew Only Y Reverse</option>
												<option value="lg-slide-skew" <?php selected( $this->Settings['Options']['mode'], 'lg-slide-skew' ); ?>>Slide Skew</option>
												<option value="lg-slide-skew-rev" <?php selected( $this->Settings['Options']['mode'], 'lg-slide-skew-rev' ); ?>>Slide Skew Reverse</option>
												<option value="lg-slide-skew-cross" <?php selected( $this->Settings['Options']['mode'], 'lg-slide-skew-cross' ); ?>>Slide Skew Cross</option>
												<option value="lg-slide-skew-cross-rev" <?php selected( $this->Settings['Options']['mode'], 'lg-slide-skew-cross-rev' ); ?>>Slide Skew Cross Reverse</option>
												<option value="lg-slide-skew-ver" <?php selected( $this->Settings['Options']['mode'], 'lg-slide-skew-ver' ); ?>>Slide Skew Vertical</option>
												<option value="lg-slide-skew-ver-rev" <?php selected( $this->Settings['Options']['mode'], 'lg-slide-skew-ver-rev' ); ?>>Slide Skew Vertical Reverse</option>
												<option value="lg-slide-skew-ver-cross" <?php selected( $this->Settings['Options']['mode'], 'lg-slide-skew-ver-cross' ); ?>>Slide Skew Vertical Cross</option>
												<option value="lg-slide-skew-ver-cross-rev" <?php selected( $this->Settings['Options']['mode'], 'lg-slide-skew-ver-cross-rev' ); ?>>Slide Skew Vertical Cross Reverse</option>
												<option value="lg-lollipop" <?php selected( $this->Settings['Options']['mode'], 'lg-lollipop' ); ?>>Lollipop</option>
												<option value="lg-lollipop-rev" <?php selected( $this->Settings['Options']['mode'], 'lg-lollipop-rev' ); ?>>Lollipop Reverse</option>
												<option value="lg-rotate" <?php selected( $this->Settings['Options']['mode'], 'lg-rotate' ); ?>>Rotate</option>
												<option value="lg-rotate-rev" <?php selected( $this->Settings['Options']['mode'], 'lg-rotate-rev' ); ?>>Rotate Reverse</option>
												<option value="lg-tube" <?php selected( $this->Settings['Options']['mode'], 'lg-tube' ); ?>>Tube</option>
											</select>
											<p class="description">Type of transition between elements.</p>
										</td>
									</tr>

									<tr valign="top">
										<th scope="row">
											Transition Duration
										</th>
										<td>
											<input name="Options[speed]" type="number" value="<?php print $this->Settings['Options']['speed']; ?>" class="small-text"> milliseconds
											<p class="description">Transition duration.</p>
										</td>
									</tr>

									<tr valign="top">
										<th scope="row">
											CSS Transition
										</th>
										<td>
											<select name="Options[useLeft]">
												<option value="1" <?php selected( $this->Settings['Options']['useLeft'], 1 ); ?>>Enabled</option>
												<option value="0" <?php selected( $this->Settings['Options']['useLeft'], 0 ); ?>>Disabled</option>
											</select>
											<p class="description">Force to use CSS left property instead of transform.</p>
										</td>
									</tr>

									<tr valign="top">
										<th scope="row">
											CSS Animation
										</th>
										<td>
											<select name="Options[cssEasing]">
												<option value="ease" <?php selected( $this->Settings['Options']['cssEasing'], 'ease' ); ?>>Ease</option>
												<option value="linear" <?php selected( $this->Settings['Options']['cssEasing'], 'linear' ); ?>>Linear</option>
												<option value="ease-in" <?php selected( $this->Settings['Options']['cssEasing'], 'ease-in' ); ?>>Ease In</option>
												<option value="ease-out" <?php selected( $this->Settings['Options']['cssEasing'], 'ease-out' ); ?>>Ease Out</option>
												<option value="ease-in-out" <?php selected( $this->Settings['Options']['cssEasing'], 'ease-in-out' ); ?>>Ease In Out</option>
												<option value="step-start" <?php selected( $this->Settings['Options']['cssEasing'], 'step-start' ); ?>>Step Start</option>
												<option value="step-end" <?php selected( $this->Settings['Options']['cssEasing'], 'step-end' ); ?>>Step End</option>
											</select>
											<p class="description">Type of easing for CSS animations.</p>
										</td>
									</tr>

									<tr valign="top">
										<th scope="row">
											jQuery Animation
										</th>
										<td>
											<select name="Options[easing]">
												<option value="ease" <?php selected( $this->Settings['Options']['easing'], 'ease' ); ?>>Ease</option>
												<option value="linear" <?php selected( $this->Settings['Options']['easing'], 'linear' ); ?>>Linear</option>
												<option value="ease-in" <?php selected( $this->Settings['Options']['easing'], 'ease-in' ); ?>>Ease In</option>
												<option value="ease-out" <?php selected( $this->Settings['Options']['easing'], 'ease-out' ); ?>>Ease Out</option>
												<option value="ease-in-out" <?php selected( $this->Settings['Options']['easing'], 'ease-in-out' ); ?>>Ease In Out</option>
												<option value="step-start" <?php selected( $this->Settings['Options']['easing'], 'step-start' ); ?>>Step Start</option>
												<option value="step-end" <?php selected( $this->Settings['Options']['easing'], 'step-end' ); ?>>Step End</option>
											</select>
											<p class="description">Type of easing for jQuery animations.</p>
										</td>
									</tr>

									<tr valign="top">
										<th scope="row">
											Backdrop Duration
										</th>
										<td>
											<input name="Options[backdropDuration]" type="number" value="<?php print $this->Settings['Options']['backdropDuration']; ?>" class="small-text">
											<p class="description">Backdrop transtion duration.</p>
										</td>
									</tr>

									<tr valign="top">
										<th scope="row">
											Gallery Height
										</th>
										<td>
											<input name="Options[height]" type="text" value="<?php print $this->Settings['Options']['height']; ?>" class="small-text">
											<p class="description">Maximum height of the gallery. e.g. <code>100%</code> or <code>300px</code></p>
										</td>
									</tr>

									<tr valign="top">
										<th scope="row">
											Gallery Width
										</th>
										<td>
											<input name="Options[width]" type="text" value="<?php print $this->Settings['Options']['width']; ?>" class="small-text">
											<p class="description">Maximum width of the gallery. e.g. <code>100%</code> or <code>300px</code></p>
										</td>
									</tr>

									<tr valign="top">
										<th scope="row">
											Inline Frame Width
										</th>
										<td>
											<input name="Options[iframeMaxWidth]" type="text" value="<?php print $this->Settings['Options']['iframeMaxWidth']; ?>" class="small-text">
											<p class="description">Maximum width of the iframe tag. e.g. <code>100%</code> or <code>300px</code></p>
										</td>
									</tr>

									<tr valign="top">
										<th scope="row">
											Hide Gallery Controls
										</th>
										<td>
											<input name="Options[hideBarsDelay]" type="number" value="<?php print $this->Settings['Options']['hideBarsDelay']; ?>" class="small-text"> milliseconds
											<p class="description">Delay for hiding gallery controls.</p>
										</td>
									</tr>

									<tr valign="top">
										<th scope="row">
											Navigation Controls
										</th>
										<td>
											<select name="Options[controls]">
												<option value="1" <?php selected( $this->Settings['Options']['controls'], 1 ); ?>>Enabled</option>
												<option value="0" <?php selected( $this->Settings['Options']['controls'], 0 ); ?>>Disabled</option>
											</select>
											<p class="description">Display previous/next buttons.</p>
										</td>
									</tr>

									<tr valign="top">
										<th scope="row">
											Hide Controls On Start/End
										</th>
										<td>
											<select name="Options[hideControlOnEnd]">
												<option value="1" <?php selected( $this->Settings['Options']['hideControlOnEnd'], 1 ); ?>>Enabled</option>
												<option value="0" <?php selected( $this->Settings['Options']['hideControlOnEnd'], 0 ); ?>>Disabled</option>
											</select>
											<p class="description">Hide navigation buttons on first/last element.</p>
										</td>
									</tr>

									<tr valign="top">
										<th scope="row">
											Keyboard Navigation
										</th>
										<td>
											<select name="Options[keyPress]">
												<option value="1" <?php selected( $this->Settings['Options']['keyPress'], 1 ); ?>>Enabled</option>
												<option value="0" <?php selected( $this->Settings['Options']['keyPress'], 0 ); ?>>Disabled</option>
											</select>
											<p class="description">Control navigation with keyboard arrow keys.</p>
										</td>
									</tr>

									<tr valign="top">
										<th scope="row">
											Mousewheel
										</th>
										<td>
											<select name="Options[mousewheel]">
												<option value="1" <?php selected( $this->Settings['Options']['mousewheel'], 1 ); ?>>Enabled</option>
												<option value="0" <?php selected( $this->Settings['Options']['mousewheel'], 0 ); ?>>Disabled</option>
											</select>
											<p class="description">Control navigation with mousewheel.</p>
										</td>
									</tr>

									<tr valign="top">
										<th scope="row">
											Close On Click
										</th>
										<td>
											<select name="Options[closable]">
												<option value="1" <?php selected( $this->Settings['Options']['closable'], 1 ); ?>>Enabled</option>
												<option value="0" <?php selected( $this->Settings['Options']['closable'], 0 ); ?>>Disabled</option>
											</select>
											<p class="description">Clicking on dimmer will close gallery.</p>
										</td>
									</tr>

									<tr valign="top">
										<th scope="row">
											Close On Esc
										</th>
										<td>
											<select name="Options[escKey]">
												<option value="1" <?php selected( $this->Settings['Options']['escKey'], 1 ); ?>>Enabled</option>
												<option value="0" <?php selected( $this->Settings['Options']['escKey'], 0 ); ?>>Disabled</option>
											</select>
											<p class="description">Close gallery by pressing the <code>Esc</code> key.</p>
										</td>
									</tr>

									<tr valign="top">
										<th scope="row">
											Item Counter
										</th>
										<td>
											<select name="Options[counter]">
												<option value="1" <?php selected( $this->Settings['Options']['counter'], 1 ); ?>>Enabled</option>
												<option value="0" <?php selected( $this->Settings['Options']['counter'], 0 ); ?>>Disabled</option>
											</select>
											<p class="description">Show total and index number of currently displayed element.</p>
										</td>
									</tr>

									<tr valign="top">
										<th scope="row">
											Item Loop
										</th>
										<td>
											<select name="Options[loop]">
												<option value="1" <?php selected( $this->Settings['Options']['loop'], 1 ); ?>>Enabled</option>
												<option value="0" <?php selected( $this->Settings['Options']['loop'], 0 ); ?>>Disabled</option>
											</select>
											<p class="description">Loop back to the beginning of the gallery when on the last element.</p>
										</td>
									</tr>

									<tr valign="top">
										<th scope="row">
											Animate At End
										</th>
										<td>
											<select name="Options[slideEndAnimatoin]">
												<option value="1" <?php selected( $this->Settings['Options']['slideEndAnimatoin'], 1 ); ?>>Enabled</option>
												<option value="0" <?php selected( $this->Settings['Options']['slideEndAnimatoin'], 0 ); ?>>Disabled</option>
											</select>
											<p class="description">Animate after the last elements is reached.</p>
										</td>
									</tr>

									<tr valign="top">
										<th scope="row">
											Item Index
										</th>
										<td>
											<input name="Options[index]" type="number" value="<?php print $this->Settings['Options']['index']; ?>" class="small-text">
											<p class="description">Which element to show initially.</p>
										</td>
									</tr>

									<tr valign="top">
										<th scope="row">
											Preload Items
										</th>
										<td>
											<input name="Options[preload]" type="number" value="<?php print $this->Settings['Options']['preload']; ?>" class="small-text">
											<p class="description">Number of sibling elements to preload. If preload is 1 then 3rd and 5th element will be pre-loaded in background after the 4th element is fully loaded.</p>
										</td>
									</tr>

									<tr valign="top">
										<th scope="row">
											Show On Load
										</th>
										<td>
											<select name="Options[showAfterLoad]">
												<option value="1" <?php selected( $this->Settings['Options']['showAfterLoad'], 1 ); ?>>Enabled</option>
												<option value="0" <?php selected( $this->Settings['Options']['showAfterLoad'], 0 ); ?>>Disabled</option>
											</select>
											<p class="description">Show the element once it is fully loaded.</p>
										</td>
									</tr>

									<tr valign="top">
										<th scope="row">
											Download
										</th>
										<td>
											<select name="Options[download]">
												<option value="1" <?php selected( $this->Settings['Options']['download'], 1 ); ?>>Enabled</option>
												<option value="0" <?php selected( $this->Settings['Options']['download'], 0 ); ?>>Disabled</option>
											</select>
											<p class="description">Show download button for modern browsers.</p>
										</td>
									</tr>

									<tr valign="top">
										<th scope="row">
											Auto Captions
										</th>
										<td>
											<select name="Options[getCaptionFromTitleOrAlt]">
												<option value="1" <?php selected( $this->Settings['Options']['getCaptionFromTitleOrAlt'], 1 ); ?>>Enabled</option>
												<option value="0" <?php selected( $this->Settings['Options']['getCaptionFromTitleOrAlt'], 0 ); ?>>Disabled</option>
											</select>
											<p class="description">Generate captions from <code>alt</code> or <code>title</code> tags.</p>
										</td>
									</tr>

									<tr valign="top">
										<th scope="row">
											Mouse Drag
										</th>
										<td>
											<select name="Options[enableDrag]">
												<option value="1" <?php selected( $this->Settings['Options']['enableDrag'], 1 ); ?>>Enabled</option>
												<option value="0" <?php selected( $this->Settings['Options']['enableDrag'], 0 ); ?>>Disabled</option>
											</select>
											<p class="description">Desktop mouse drag support.</p>
										</td>
									</tr>

									<tr valign="top">
										<th scope="row">
											Touch Screen
										</th>
										<td>
											<select name="Options[enableTouch]">
												<option value="1" <?php selected( $this->Settings['Options']['enableTouch'], 1 ); ?>>Enabled</option>
												<option value="0" <?php selected( $this->Settings['Options']['enableTouch'], 0 ); ?>>Disabled</option>
											</select>
											<p class="description">Touch screen event support.</p>
										</td>
									</tr>

								</table>

							</div>


							<div class="nav-tab-content" data-tab="Help">

								<h3>Settings</h3>
								<p>The plugin comes loaded with default settings prefilled for <a href="http://sachinchoolur.github.io/lightGallery/?WPPluginFree" target="_blank">lightGallery</a> and all of its additional plugins. Most of the settings are self explanatory and have useful description where necessary. There are some advanced options available where input must be in valid format e.g. YouTube Options. Such settings if used incorrectly can break the whole plugin functionality on frontend side.</p>

								<h3>Usage</h3>
								<p>The plugin provides easy to use interface on all Posts and Pages for integrating images to your site content. It uses a special <a href="https://codex.wordpress.org/Shortcode" target="_blank">ShortCode</a> which handles all the complex integration with lightGallery and output a well formatted HTML code in content area and loads all necessary CSS and JavaScript files in footer.</p>
								<p>You can get started in just few simple steps:</p>
								<ol>
									<li>Go to Post > Edit > LightGallery Meta Box</li>
									<li>Select type of media you want to insert in your content</li>
									<li>Required ShortCode will be generated automatically</li>
									<li>Copy/Paste or use Insert button to add it to your content</li>
								</ol>

								<h3>Premium Version</h3>
								<p>We offer paid versions of this plugin with additional features. You can know more about features and pricing on our <a href="https://dzine.io/products/lightgallery-wp-plugin/?WPPluginFree" target="_blank">website</a>.</p>

								<h3>Support</h3>
								<p>If you have any questions or comments please post them on <a href="https://wordpress.org/support/plugin/lightgallery" target="_blank">support forum</a>.</p>

							</div>


							<input name="nonce" type="hidden" value="<?php print wp_create_nonce( $this->SettingsName ); ?>">

							<?php submit_button(); ?>


						</form>

					</div>


					<script>

						jQuery( document ).ready( function( $ )
						{
							$( '.nav-tab-wrapper a' ).click( function( e )
							{
								Elm = $( this );

								Elm.blur();

								Elm.parent().find( 'a.nav-tab-active' ).removeClass( 'nav-tab-active' );
								Elm.addClass( 'nav-tab-active' );

								Elm.parent().siblings( '.nav-tab-content-active' ).fadeOut( 'fast', function()
								{
									$( this ).removeClass( 'nav-tab-content-active' );
									Elm.parent().siblings( '.nav-tab-content[data-tab="' + Elm.attr( 'data-tab' ) + '"]' ).fadeIn( 'fast' ).addClass( 'nav-tab-content-active' );
								});
							});


							function NavTabHash()
							{
								if ( document.location.hash )
								{
									var Hash = document.location.hash.substring( 1 );

									if ( Hash.search( '-' ) )
									{
										var Hash1 = Hash.slice( 0, Hash.indexOf( '-' ) );
										$( 'a.nav-tab[data-tab="' + Hash1 + '"]' ).click();
									}

									$( 'a.nav-tab[data-tab="' + Hash + '"]' ).click();
								}
							}

							NavTabHash();

							$( window ).on( 'hashchange', function()
							{
								NavTabHash();
							});
						});

					</script>

				<?php

			}


			public function HookMetaBoxes( $PostType, $Post )
			{
				add_meta_box( "$this->SettingsName-MetaBox", $this->PluginName, array( $this, 'HookMetaBoxCallback' ), 'page', 'side' );
				add_meta_box( "$this->SettingsName-MetaBox", $this->PluginName, array( $this, 'HookMetaBoxCallback' ), 'post', 'side' );
			}


			public function HookMetaBoxCallback( $Post )
			{

				?>

					<input id="<?php print $this->SettingsName; ?>-Images" type="hidden">

					<div>
						<p><strong>Select Media</strong></p>
						<button class="button button-small media-gallery">Images</button>
						<div id="<?php print $this->SettingsName; ?>-Preview" style="margin-top: 5px;"></div>
					</div>

					<div>
						<p><strong>ShortCode</strong></p>
						<textarea id="<?php print $this->SettingsName; ?>-ShortCode" class="large-text" rows="3"></textarea>
						<button class="button button-small editor-insert">Insert into content</button> &nbsp; <button class="button button-small media-clear">Clear</button>
					</div>

				<?php
			}


			public function HookWPEnqueueScripts()
			{
				global $wp_version;

				wp_register_style( "$this->SettingsName-Style", $this->PluginURL . 'library/css/lightgallery.css', null, $this->LibraryVersion );
				wp_register_script( "$this->SettingsName-Script", $this->PluginURL . 'library/js/lightgallery.js', array( 'jquery' ), $this->LibraryVersion, false );


				$DefaultOptions = array_diff_assoc( $this->Settings['Options'], $this->SettingsDefaults['Options'] );

				if ( count( $DefaultOptions ) )
				{
					array_walk( $DefaultOptions, array( $this, 'CastDefaultOptions' ) );

					wp_localize_script( "$this->SettingsName-Script", $this->SettingsName, array( 'Options' => $DefaultOptions ) );
				}


				wp_enqueue_style( "$this->SettingsName-Style" );
				wp_enqueue_script( "$this->SettingsName-Script" );
			}


			public function HookWPHead()
			{
				$DefaultOptions = array_diff_assoc( $this->Settings['Options'], $this->SettingsDefaults['Options'] );

				printf( "<script> jQuery( document ).ready( function( $ ) { $( '.lightgallery-default' ).lightGallery( %s ); }); </script>", ( count( $DefaultOptions ) ? 'LightGallery.Options' : '' ) );
			}


			public function HookContentSavePre( $Content )
			{
				$Content = preg_replace_callback( '/\[lightgallery\s+(.+?)]/i', array( $this, 'SanitizeShortCodeCallback' ), $Content );

				return $Content;
			}


			public function HookShortCode( $Attributes, $Content = null, $Code = '' )
			{
				static $Instance = 0;

				$Instance++;
				$Attributes = array_map( array( $this, 'SanitizeShortCodeAttrCallback' ), $Attributes );
				$Images = $Attributes['images'];
				$DefaultOptions = array_diff_assoc( $this->Settings['Options'], $this->SettingsDefaults['Options'] );
				$Data = '';

				if ( ! empty( $Images ) )
				{
					$Ids = array();

					if ( strpos( $Images, ',' ) !== false )
					{
						$Ids = explode( ',', $Images );
					}
					else
					{
						$Ids[] = $Images;
					}


					$Data .= "<p id=\"lightgallery-$Instance\" class=\"lightgallery-default\">\n";

					foreach ( $Ids as $Id )
					{
						$Attachment = get_post( $Id );

						if ( $Attachment )
						{
							$ImageLarge = wp_get_attachment_image_src( $Id, 'large' );
							$ImageThumbnail = wp_get_attachment_image_src( $Id, 'thumbnail' );

							$Caption = $Attachment->post_excerpt;

							$Data .= sprintf( '<a href="%s"><img src="%s" alt="%s"></a>' . "\n", reset( $ImageLarge ), reset( $ImageThumbnail ), $Caption );
						}
					}

					$Data .= '</p>';

					return $Data;
				}
				else
				{
					return "<p>$this->PluginName: Required parameter is missing.</p>";
				}
			}


			public function SanitizeShortCodeCallback( $Matches )
			{
				$Content = $Matches[0];

				$Content = preg_replace( array( '~\xE2\x80\x9C~', '~\xE2\x80\x9D~', '~\xE2\x80\x9F~', '~\xE2\x80\xB3~' ), '"', $Content );

				$Content = preg_replace( array( '~\xc2\xa0~', '/\s\s+/' ), ' ', $Content );

				return $Content;
			}


			public function SanitizeShortCodeAttrCallback( $Content )
			{
				$Content = preg_replace( array( '~\xE2\x80\x9C~', '~\xE2\x80\x9D~', '~\xE2\x80\x9F~', '~\xE2\x80\xB3~' ), '', $Content );

				return $Content;
			}


			public function CastDefaultOptions( &$Value, $Key )
			{
				if ( in_array( $Key, array( 'useLeft', 'closable', 'loop', 'escKey', 'keyPress', 'controls', 'slideEndAnimatoin', 'hideControlOnEnd', 'mousewheel', 'getCaptionFromTitleOrAlt', 'showAfterLoad', 'download', 'counter', 'enableDrag', 'enableTouch' ) ) )
				{
					settype( $Value, 'boolean' );
				}

				if ( in_array( $Key, array( 'speed', 'backdropDuration', 'hideBarsDelay', 'preload', 'index' ) ) )
				{
					settype( $Value, 'integer' );
				}
			}


			public function TrimByReference( &$String )
			{
				$String = trim( $String );
			}
		}


		LightGallery::Instance();
	}

?>