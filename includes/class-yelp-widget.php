<?php

/**
 * Adds Yelp Widget
 *
 * Class Yelp_Widget
 */
class Yelp_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 *
	 * Yelp_Widget constructor.
	 */
	public function __construct() {
		parent::__construct(
			'yelp_widget', // Base ID
			'Yelp Widget', // Name
			array( 'description' => __( 'Display Yelp business ratings and reviews on your Website.', 'yelp-widget' ) ) // Args
		);

		// Hooks
		add_action( 'wp_enqueue_scripts', array( $this, 'public_scripts' ) );
		add_action( 'admin_init', array( $this, 'widget_settings' ) );

	}

	/**
	 * Initiate the Yelp Widget
	 *
	 * @param $file
	 */
	function widget_settings( $file ) {

		// Register the yelp_widget settings as a group
		register_setting( 'yelp_widget_settings', 'yelp_widget_settings', array( 'sanitize_callback' => 'yelp_widget_clean' ) );

	}

	/**
	 * Register + Enqueue Yelp Widget Pro scripts
	 *
	 * Loads CSS + JS on the frontend.
	 */
	function public_scripts() {
	
		wp_register_style( 'yelp-widget', YELP_WIDGET_MS_URL . '/assets/css/public-main.css' );
		wp_enqueue_style( 'yelp-widget' );
		

	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	function widget( $args, $instance ) {

		// Get plugin options.
		$options = get_option( 'yelp_widget_settings' );

		/**
		 * As of v1.5.0, the Yelp API transitioned from v2 to v3. To ensure upgraded plugins continue to function, a backup API key has been included below.
		 * It is still highly recommended that each user set up their own Yelp app and use their own API key.
		 */
		$fusion_api_key = ! empty( $options['yelp_widget_fusion_api'] ) ? $options['yelp_widget_fusion_api'] : 'u6iiKEMVJzF8hpqAaxajY-pf0bWxltr4etYBs6jo6HDpgZHQErXP8JkIGWA2ISKI2HUE9-E-3MBiYK14YXCq3fZmGPKFFjPVouU4HhQONe4AlEIct9MTVf97ZOs5WnYx';

		// Get Widget Options.
		$title          = apply_filters( 'widget_title', $instance['title'] );
		$displayOption  = isset( $instance['display_option'] ) ? $instance['display_option'] : '';
		$term           = isset( $instance['term'] ) ? $instance['term'] : '';
		$id             = isset( $instance['id'] ) ? $instance['id'] : '';
		$location       = isset( $instance['location'] ) ? $instance['location'] : '';
		$address        = isset( $instance['display_address'] ) ? $instance['display_address'] : '';
		$phone          = isset( $instance['display_phone'] ) ? $instance['display_phone'] : '';
		$limit          = isset( $instance['limit'] ) ? $instance['limit'] : '';
		$profileImgSize = isset( $instance['profile_img_size'] ) ? $instance['profile_img_size'] : '';
		$sort           = isset( $instance['sort'] ) ? $instance['sort'] : '';
		$align          = isset( $instance['alignment'] ) ? $instance['alignment'] : '';
		$reviewsOption  = isset( $instance['display_reviews'] ) ? esc_attr( $instance['display_reviews'] ) : '';
		$titleOutput    = isset( $instance['disable_title_output'] ) ? $instance['disable_title_output'] : '';
		$targetBlank    = isset( $instance['target_blank'] ) ? $instance['target_blank'] : '';
		$noFollow       = isset( $instance['no_follow'] ) ? $instance['no_follow'] : '';
		$cache          = isset( $instance['cache'] ) ? $instance['cache'] : '';


		// If cache option is enabled, attempt to get response from transient.
		if ( 'none' !== strtolower( $cache ) ) {

			$transient = $displayOption . $term . $id . $location . $limit . $sort . $profileImgSize;

			// Check for an existing copy of our cached/transient data.
			$response = get_transient( $transient );

			if ( false === $response ) {

				// Get Time to Cache Data
				$expiration = $cache;

				// Assign Time to appropriate Math
				switch ( $expiration ) {
					case '1 Hour':
						$expiration = 3600;
						break;
					case '3 Hours':
						$expiration = 3600 * 3;
						break;
					case '6 Hours':
						$expiration = 3600 * 6;
						break;
					case '12 Hours':
						$expiration = 60 * 60 * 12;
						break;
					case '1 Day':
						$expiration = 60 * 60 * 24;
						break;
					case '2 Days':
						$expiration = 60 * 60 * 48;
						break;
					case '1 Week':
						$expiration = 60 * 60 * 168;
						break;
				}

				// Cache data wasn't there, so regenerate the data and save the transient
				if ( '1' === $displayOption ) {
					$response = yelp_widget_fusion_get_business( $fusion_api_key, $id, $reviewsOption );
				} else {
					$response = yelp_widget_fusion_search( $fusion_api_key, $term, $location, $limit, $sort );
				}

				set_transient( $transient, $response, $expiration );
			}
		} else {

			// No Cache option enabled
			if ( '1' === $displayOption ) {
				// Widget is in Business mode.
				$response = yelp_widget_fusion_get_business( $fusion_api_key, $id, $reviewsOption );
			} else {
				// Widget is in Search mode.
				$response = yelp_widget_fusion_search( $fusion_api_key, $term, $location, $limit, $sort );
			}
		}

		/**
		 * Output Yelp Widget Pro
		 */

		// Widget Output
		echo $args['before_widget'];

		// if the title is set & the user hasn't disabled title output
		if ( $title && $titleOutput != 1 ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		if ( isset( $response->businesses ) ) {
			$businesses = $response->businesses;
		} else {
			$businesses = array( $response );
		}

		// Check Yelp API response for an error
		if ( isset( $response->error ) ) {

			echo $this->handle_yelp_api_error( $response );

		} else {

			// Verify results have been returned
			if ( ! isset( $businesses[0] ) ) {
				echo '<div class="yelp-error">' . __( 'No results found', 'yelp-widget' ) . '</div>';
			} else {

				/**
				 * The response from Yelp is valid - Output Widget:
				 */

				// Open link in new window if set.
				if ( $targetBlank == 1 ) {
					$targetBlank = 'target="_blank" ';
				} else {
					$targetBlank = '';
				}
				// Add nofollow relation if set.
				if ( '1' === $noFollow ) {
					$noFollow = 'rel="nofollow" ';
				} else {
					$noFollow = '';
				}

				// Begin Setting Output Variable by Looping Data from Yelp
				for ( $x = 0; $x < count( $businesses ); $x ++ ) {
					?>

					<div class="yelp yelp-business <?php echo $align;
					// Set profile image size
					switch ( $profileImgSize ) {

						case '40x40':
							echo 'ywp-size-40';
							break;
						case '60x60':
							echo 'ywp-size-60';
							break;
						case '80x80':
							echo 'ywp-size-80';
							break;
						case '100x100':
							echo 'ywp-size-100';
							break;
						default:
							echo 'ywp-size-60';
					}

					?>">
						<div class="yelp-business-img-wrap">
							<img class="yelp-business-img" src="
							<?php
							if ( ! empty( $businesses[ $x ]->image_url ) ) {
								echo esc_attr( $businesses[ $x ]->image_url );
							} else {
								echo YELP_WIDGET_MS_URL . '/assets/images/blank-biz.png';
							};
							?>
							"
								<?php
								// Set profile image size
								switch ( $profileImgSize ) {

									case '40x40':
										echo "width='40' height='40'";
										break;
									case '60x60':
										echo "width='60' height='60'";
										break;
									case '80x80':
										echo "width='80' height='80'";
										break;
									case '100x100':
										echo "width='100' height='100'";
										break;
									default:
										echo "width='60' height='60'";
								}
								?>
							/></div>
						<div class="yelp-info-wrap">
							<a class="yelp-business-name" <?php echo $targetBlank . $noFollow; ?>
							   href="<?php echo esc_attr( $businesses[ $x ]->url ); ?>"
							   title="<?php echo esc_attr( $businesses[ $x ]->name ); ?> Yelp page"><?php echo $businesses[ $x ]->name; ?></a>
							<?php yelp_widget_fusion_stars( $businesses[ $x ]->rating ); ?>
							<span
								class="review-count"><?php echo esc_attr( $businesses[ $x ]->review_count ) . '&nbsp;' . __( 'reviews', 'yelp-widget' ); ?></span>
							<a class="yelp-branding"
							   href="<?php echo esc_url( $businesses[ $x ]->url ); ?>" <?php echo $targetBlank . $noFollow; ?>><?php yelp_widget_fusion_logo(); ?></a>

							<?php

							// Does the User want to display Address?
							if ( '1' === $address ) {
								?>
								<div class="yelp-address-wrap">
									<address>
										<?php
										// Iterate through Address Array
										foreach ( $businesses[ $x ]->location->display_address as $addressItem ) {
											echo $addressItem . '<br/>';
										}
										?>
										<address>
								</div>

								<?php
							} //endif address

							// Phone
							if ( '1' === $phone ) {
								?>
								<p class="ywp-phone">
									<a href="tel:<?php echo $businesses[ $x ]->phone; ?>"><?php
										// echo pretty display_phone (only avail in biz API)
										if ( ! empty( $businesses[ $x ]->display_phone ) ) {
											echo $businesses[ $x ]->display_phone;
										} else {
											echo $businesses[ $x ]->phone;
										}
										?></a>
								</p>


							<?php } //endif phone ?>

						</div>

						<?php
						/**
						 * Display Reviews
						 *
						 * a) if reviews option is enabled
						 * b + c) if review are present
						 */
						if (
							'1' === $reviewsOption
							&& isset( $businesses[0]->review_count )
							&& isset( $businesses[0]->reviews )
						) : ?>

							<div class="yelp-business-reviews">

								<?php
								/**
								 * Display Reviews
								 */
								foreach ( $businesses[0]->reviews as $review ) {

									$review_avatar = ! empty( $review->user->image_url ) ? $review->user->image_url : YELP_WIDGET_MS_URL . '/assets/src/images/yelp-default-avatar.png';
									?>

									<div class="yelp-review yelper-avatar-60 clearfix">

										<div class="yelp-review-avatar">

											<img src="<?php echo $review_avatar; ?>" width="60" height="60"
											     alt="<?php echo $review->user->name; ?>'s Review"/>
											<span class="name"><?php echo $review->user->name; ?></span>
										</div>

										<div class="yelp-review-excerpt">
											<?php yelp_widget_fusion_stars( $review->rating ); ?>
											<time><?php echo date( 'n/j/Y', strtotime( $review->time_created ) ); ?></time>

											<div class="yelp-review-excerpt-text">
												<?php echo wpautop( $review->text ); ?>
											</div>

											<?php
											//Read More Review
											$reviewMoreText = apply_filters( 'ywp_review_readmore_text', __( 'Read More &raquo;', 'yelp-widget' ) ); ?>
											<a href="<?php echo esc_url( $review->url ); ?>"
											   class="ywp-review-read-more" <?php echo $targetBlank . $noFollow; ?>><?php echo $reviewMoreText; ?></a>

										</div>

									</div>

								<?php } //end foreach ?>

							</div>

						<?php endif; ?>

					</div><!--/.yelp-business -->

					<?php

				}
			}
		} //Output Widget Contents.

		echo $args['after_widget'];

	}


	/**
	 * Saves the widget options
	 *
	 * @see WP_Widget::update
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 *
	 * @return array
	 */
	function update( $new_instance, $old_instance ) {
		$instance                         = $old_instance;
		$instance['title']                = isset( $new_instance['title'] ) ? esc_attr( $new_instance['title'] ) : '';
		$instance['display_option']       = isset( $new_instance['display_option'] ) ? esc_attr( $new_instance['display_option'] ) : '';
		$instance['display_reviews']      = isset( $new_instance['display_reviews'] ) ? esc_attr( $new_instance['display_reviews'] ) : '';
		$instance['term']                 = isset( $new_instance['term'] ) ? esc_attr( $new_instance['term'] ) : '';
		$instance['id']                   = isset( $new_instance['id'] ) ? esc_attr( $new_instance['id'] ) : '';
		$instance['location']             = isset( $new_instance['location'] ) ? esc_attr( $new_instance['location'] ) : '';
		$instance['display_address']      = isset( $new_instance['display_address'] ) ? esc_attr( $new_instance['display_address'] ) : '';
		$instance['display_phone']        = isset( $new_instance['display_phone'] ) ? esc_attr( $new_instance['display_phone'] ) : '';
		$instance['limit']                = isset( $new_instance['limit'] ) ? esc_attr( $new_instance['limit'] ) : '';
		$instance['profile_img_size']     = isset( $new_instance['profile_img_size'] ) ? esc_attr( $new_instance['profile_img_size'] ) : '';
		$instance['sort']                 = isset( $new_instance['sort'] ) ? esc_attr( $new_instance['sort'] ) : '';
		$instance['alignment']            = isset( $new_instance['alignment'] ) ? esc_attr( $new_instance['alignment'] ) : '';
		$instance['disable_title_output'] = isset( $new_instance['disable_title_output'] ) ? esc_attr( $new_instance['disable_title_output'] ) : '';
		$instance['target_blank']         = isset( $new_instance['target_blank'] ) ? esc_attr( $new_instance['target_blank'] ) : '';
		$instance['no_follow']            = isset( $new_instance['no_follow'] ) ? esc_attr( $new_instance['no_follow'] ) : '';
		$instance['cache']                = isset( $new_instance['cache'] ) ? esc_attr( $new_instance['cache'] ) : '';


		// Delete cache on widget update.
		$transient = $instance['display_option'] . $instance['term'] . $instance['id']  . $instance['location'] . $instance['limit'] . $instance['sort']  . $instance['profile_img_size'] ;
		delete_transient( $transient );

		return $instance;
	}


	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance
	 *
	 * @return string|void
	 */
	public function form( $instance ) {

		$title          = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$displayOption  = isset( $instance['display_option'] ) ? esc_attr( $instance['display_option'] ) : '1';
		$reviewsOption  = isset( $instance['display_reviews'] ) ? esc_attr( $instance['display_reviews'] ) : '1';
		$term           = isset( $instance['term'] ) ? esc_attr( $instance['term'] ) : '';
		$id             = isset( $instance['id'] ) ? esc_attr( $instance['id'] ) : '';
		$location       = isset( $instance['location'] ) ? esc_attr( $instance['location'] ) : '';
		$address        = isset( $instance['display_address'] ) ? esc_attr( $instance['display_address'] ) : '';
		$phone          = isset( $instance['display_phone'] ) ? esc_attr( $instance['display_phone'] ) : '';
		$limit          = isset( $instance['limit'] ) ? esc_attr( $instance['limit'] ) : '';
		$profileImgSize = isset( $instance['profile_img_size'] ) ? esc_attr( $instance['profile_img_size'] ) : '';
		$sort           = isset( $instance['sort'] ) ? esc_attr( $instance['sort'] ) : '';
		$titleOutput    = isset( $instance['disable_title_output'] ) ? esc_attr( $instance['disable_title_output'] ) : '';
		$targetBlank    = isset( $instance['target_blank'] ) ? esc_attr( $instance['target_blank'] ) : '';
		$noFollow       = isset( $instance['no_follow'] ) ? esc_attr( $instance['no_follow'] ) : '';
		$cache          = isset( $instance['cache'] ) ? esc_attr( $instance['cache'] ) : ''; ?>

		<!-- Title -->
		<p>
			<label
				for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Widget Title', 'yelp-widget' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
			       name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>"/>
		</p>

		<!-- Listing Options -->
		<p class="widget-api-option">
			<label
				for="<?php echo $this->get_field_id( 'display_option' ); ?>"><?php _e( 'Yelp API Request Method:', 'yelp-widget' ); ?></label>
			<br>
			<span class="yelp-method-span business-api-option-wrap">
				<input type="radio" name="<?php echo $this->get_field_name( 'display_option' ); ?>"
				       class="<?php echo $this->get_field_id( 'display_option' ); ?> business-api-option"
				       value="1" <?php checked( '1', $displayOption ); ?>>
				<span class="yelp-method-label"><?php _e( 'Business Method', 'yelp-widget' ); ?></span>
				<span class="dashicons dashicons-editor-help" title="<?php _e( 'Yelp\'s Business API allows business owners to display their business information and 3 reviews.', 'yelp-widget' ); ?>"></span>
			</span>
			<br>
			<span class="yelp-method-span search-api-option-wrap">
				<input type="radio" name="<?php echo $this->get_field_name( 'display_option' ); ?>"
				       class="<?php echo $this->get_field_id( 'display_option' ); ?> search-api-option"
				       value="0" <?php checked( '0', $displayOption ); ?>>
				<span class="yelp-method-label"><?php _e( 'Search Method', 'yelp-widget' ); ?></span>
				<span class="dashicons dashicons-editor-help" title="<?php _e( 'Yelp\'s Search API allows you to display results of a specific search term.', 'yelp-widget' ); ?>"></span>
			</span>
		</p>

		<div class="toggle-api-option-1 toggle-item <?php echo ( '0' === $displayOption ) ? 'toggled' : ''; ?>">
			<!-- Search Term -->
			<p>
				<label
					for="<?php echo $this->get_field_id( 'term' ); ?>"><?php _e( 'Search Term:', 'yelp-widget' ); ?>
					<span class="dashicons dashicons-editor-help" title="<?php _e( 'he term you would like to display results for, ie: \'Bars\', \'Daycare\', \'Restaurants\'.', 'yelp-widget' ); ?>"></span>
				</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'term' ); ?>"
				       name="<?php echo $this->get_field_name( 'term' ); ?>" type="text" value="<?php echo $term; ?>"/>
			</p>


			<!-- Location -->
			<p>
				<label
					for="<?php echo $this->get_field_id( 'location' ); ?>"><?php _e( 'Location:', 'yelp-widget' ); ?>
					<span class="dashicons dashicons-editor-help" title="<?php _e( 'The city name you would like to to search, ie \'San Diego\', \'New York\', \'Miami\'.', 'yelp-widget' ); ?>"></span>
				</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'location' ); ?>"
				       name="<?php echo $this->get_field_name( 'location' ); ?>" type="text"
				       value="<?php echo $location; ?>"/>
			</p>

			<!-- Limit -->
			<p>
				<label
					for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php _e( 'Number of Items:', 'yelp-widget' ); ?></label>
				<select name="<?php echo $this->get_field_name( 'limit' ); ?>"
				        id="<?php echo $this->get_field_id( 'limit' ); ?>" class="widefat">
					<?php
					$options = array( '1', '2', '3', '4', '5', '6', '7', '8', '9', '10' );
					foreach ( $options as $option ) {
						?>

						<option value="<?php echo $option; ?>" id="<?php echo $option; ?>"
							<?php
							if ( $limit == $option || empty( $limit ) && $option == '4' ) {
								echo 'selected="selected"';
							}
							?>
						><?php echo $option; ?></option>

					<?php } ?>
				</select>
			</p>

			<!-- Sort -->
			<p>
				<label
					for="<?php echo $this->get_field_id( 'sort' ); ?>"><?php _e( 'Sorting:', 'yelp-widget' ); ?></label>

				<select name="<?php echo $this->get_field_name( 'sort' ); ?>"
				        id="<?php echo $this->get_field_id( 'sort' ); ?>" class="widefat">
					<?php
					$options = array(
						__( 'Best Match', 'yelp-widget' ),
						__( 'Distance', 'yelp-widget' ),
						__( 'Highest Rated', 'yelp-widget' )
					);
					// Counter for Option Values
					$counter = 0;

					foreach ( $options as $option ) {
						echo '<option value="' . $counter . '" id="' . $option . '"', $sort == $counter ? ' selected="selected"' : '', '>', $option, '</option>';
						$counter ++;
					}
					?>
				</select>
			</p>

		</div><!-- /.toggle-api-option-1 -->


		<div class="toggle-api-option-2 toggle-item <?php echo ( '1' === $displayOption ) ? 'toggled' : ''; ?>">
			<!-- Business ID -->
			<p>
				<label
					for="<?php echo $this->get_field_id( 'id' ); ?>"><?php _e( 'Business ID:', 'yelp-widget' ); ?>
					<span class="dashicons dashicons-editor-help" title="<?php _e( 'The Business ID is the portion of the Yelp url after the \'http://www.yelp.com/biz/\' portion. For example, the following business\'s URL on Yelp is \'http://www.yelp.com/biz/the-barbeque-pit-seattle-2\' and the Business ID is \'the-barbeque-pit-seattle-2\'.', 'yelp-widget' ); ?>"></span>
				</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'id' ); ?>"
				       name="<?php echo $this->get_field_name( 'id' ); ?>" type="text" value="<?php echo $id; ?>"/>
			</p>

			<!-- Display Reviews -->
			<p>
				<input id="<?php echo $this->get_field_id( 'display_reviews' ); ?>" class="reviews-toggle"
				       name="<?php echo $this->get_field_name( 'display_reviews' ); ?>" type="checkbox"
				       value="1" <?php checked( '1', $reviewsOption ); ?>/>
				<label
					for="<?php echo $this->get_field_id( 'display_reviews' ); ?>"><?php _e( 'Display Business Reviews', 'yelp-widget' ); ?></label>
			</p>
		</div>


		<h4 class="yelp-toggler"><?php _e( 'Display Options:', 'yelp-widget' ); ?><span></span></h4>

		<div class="display-options toggle-item">

			<!-- Profile Image Size -->
			<p>
				<label
					for="<?php echo $this->get_field_id( 'profile_img_size' ); ?>"><?php _e( 'Business Profile Image Size:', 'yelp-widget' ); ?>
					<span class="dashicons dashicons-editor-help" title="<?php _e( 'Customize the width and height of the business Yelp profile image.', 'yelp-widget' ); ?>"></span>
				</label>
				<select name="<?php echo $this->get_field_name( 'profile_img_size' ); ?>"
				        id="<?php echo $this->get_field_id( 'profile_img_size' ); ?>" class="widefat">
					<?php
					$options = array( '40x40', '60x60', '80x80', '100x100' );
					foreach ( $options as $option ) {
						?>

						<option value="<?php echo $option; ?>" id="<?php echo $option; ?>"
							<?php
							if ( $profileImgSize == $option || empty( $profileImgSize ) && $option == '60x60' ) {
								echo 'selected="selected"';
							}
							?>
						><?php echo $option; ?></option>

					<?php } ?>
				</select>
			</p>

			<!-- Disable address checkbox -->
			<p>
				<input id="<?php echo $this->get_field_id( 'display_address' ); ?>"
				       name="<?php echo $this->get_field_name( 'display_address' ); ?>" type="checkbox"
				       value="1" <?php checked( '1', $address ); ?>/>
				<label
					for="<?php echo $this->get_field_id( 'display_address' ); ?>"><?php _e( 'Display Business Address', 'yelp-widget' ); ?></label>
			</p>

			<!-- Display phone -->
			<p>
				<input id="<?php echo $this->get_field_id( 'display_phone' ); ?>"
				       name="<?php echo $this->get_field_name( 'display_phone' ); ?>" type="checkbox"
				       value="1" <?php checked( '1', $phone ); ?>/>
				<label
					for="<?php echo $this->get_field_id( 'display_phone' ); ?>"><?php _e( 'Display Business Phone Number', 'yelp-widget' ); ?></label>
			</p>

		</div>


		<h4 class="yelp-toggler"><?php _e( 'Advanced Options', 'yelp-widget' ); ?>: <span></span></h4>

		<div class="advanced-options toggle-item">

			<!-- Disable title output checkbox -->
			<p>
				<input id="<?php echo $this->get_field_id( 'disable_title_output' ); ?>"
				       name="<?php echo $this->get_field_name( 'disable_title_output' ); ?>" type="checkbox"
				       value="1" <?php checked( '1', $titleOutput ); ?>/>
				<label
					for="<?php echo $this->get_field_id( 'disable_title_output' ); ?>"><?php _e( 'Disable Title Output', 'yelp-widget' ); ?>
					<span class="dashicons dashicons-editor-help" title="<?php _e( 'The title output is content within the \'Widget Title\' field above. Disabling the title output may be useful for some themes.', 'yelp-widget' ); ?>"></span>
				</label>
			</p>

			<!-- Open Links in New Window -->
			<p>
				<input id="<?php echo $this->get_field_id( 'target_blank' ); ?>"
				       name="<?php echo $this->get_field_name( 'target_blank' ); ?>" type="checkbox"
				       value="1" <?php checked( '1', $targetBlank ); ?>/>
				<label
					for="<?php echo $this->get_field_id( 'target_blank' ); ?>"><?php _e( 'Open Links in New Window', 'yelp-widget' ); ?>
					<span class="dashicons dashicons-editor-help" title="<?php _e( 'This option will add target=\'_blank\' to the widget\'s links. This is useful to keep users on your website.', 'yelp-widget' ); ?>"></span>
				</label>
			</p>
			<!-- No Follow Links -->
			<p>
				<input id="<?php echo $this->get_field_id( 'no_follow' ); ?>"
				       name="<?php echo $this->get_field_name( 'no_follow' ); ?>" type="checkbox"
				       value="1" <?php checked( '1', $noFollow ); ?>/>
				<label
					for="<?php echo $this->get_field_id( 'no_follow' ); ?>"><?php _e( 'No Follow Links', 'yelp-widget' ); ?>
					<img src="<?php echo YELP_WIDGET_MS_URL . '/assets/images/help.png'; ?>"
					     title="<?php _e( 'This option will add rel=\'nofollow\' to the widget\'s outgoing links. This option may be useful for SEO.', 'yelp-widget' ); ?>"
					     class="tooltip-info" width="16" height="16"/></label>
			</p>

			<!-- Transient / Cache -->
			<p>
				<label
					for="<?php echo $this->get_field_id( 'cache' ); ?>"><?php _e( 'Cache Data:', 'yelp-widget' ); ?>
					<img src="<?php echo YELP_WIDGET_MS_URL . '/assets/images/help.png'; ?>"
					     title="<?php _e( 'Caching data will save Yelp data to your database in order to speed up response times and conserve API requests. The suggested settings is 1 Day. ', 'yelp-widget' ); ?>"
					     class="tooltip-info" width="16" height="16"/></label>
				<select name="<?php echo $this->get_field_name( 'cache' ); ?>"
				        id="<?php echo $this->get_field_id( 'cache' ); ?>" class="widefat">
					<?php
					$options = array(
						__( 'None', 'yelp-widget' ),
						__( '1 Hour', 'yelp-widget' ),
						__( '3 Hours', 'yelp-widget' ),
						__( '6 Hours', 'yelp-widget' ),
						__( '12 Hours', 'yelp-widget' ),
						__( '1 Day', 'yelp-widget' ),
						__( '2 Days', 'yelp-widget' ),
						__( '1 Week', 'yelp-widget' ),
					);

					foreach ( $options as $option ) {
						?>
						<option value="<?php echo $option; ?>" id="<?php echo $option; ?>"
							<?php
							if ( $cache == $option || empty( $cache ) && $option == '1 Day' ) {
								echo ' selected="selected" ';
							}
							?>
						>
							<?php echo $option; ?>
						</option>
						<?php
						$counter ++;
					}
					?>
				</select>
			</p>

		</div>

		<div class="pro-option">
			<p>Upgrade to <a href="https://wpbusinessreviews.com" target="_blank" class="new-window"
			                 title="<?php _e( 'Get immediate access after purchase to additional features, priority support, and auto updates.', 'yelp-widget' ); ?>"><?php _e( 'WP Business Reviews', 'yelp-widget' ); ?></a>
			</p>
		</div>

		<?php

	}

	/**
	 * Handle Yelp Error Messages
	 *
	 * @param $response
	 */
	public function handle_yelp_api_error( $response ) { 

		$output = '<div class="yelp-error">';
		if ( $response->error->code == 'EXCEEDED_REQS' ) {
			$output .= __( 'The default Yelp API has exhausted its daily limit. Please enable your own API Key in your Yelp Widget Pro settings.', 'yelp-widget' );
		} elseif ( $response->error->code == 'BUSINESS_UNAVAILABLE' ) {
			$output .= __( '<strong>Error:</strong> Business information is unavailable. Either you mistyped the Yelp biz ID or the business does not have any reviews.', 'yelp-widget' );
		} elseif ( $response->error->code == 'TOKEN_MISSING' ) {
			$output .= sprintf(
				__( '%1$sSetup Required:%2$s Enter a Yelp Fusion API Key in the %3$splugin settings screen.%4$s', 'yelp-widget' ),
				'<strong>',
				'</strong>',
				'<a href="' . YWP_SETTINGS_URL . '">',
				'</a>'
			);
		} //output standard error
		else {
			if ( ! empty( $response->error->code ) ) {
				$output .= $response->error->code . ': ';
			}
			if ( ! empty( $response->error->description ) ) {
				$output .= $response->error->description;
			}
		}
		$output .= '</div>';

		echo $output;

	}
}

/**
 * Register Yelp Widget Pro.
 */
function ywp_register_widgets() {
	register_widget( 'Yelp_Widget' );
}

add_action( 'widgets_init', 'ywp_register_widgets' );


/**
 * CURLs the Yelp API with our url parameters and returns JSON response
 *
 * @param $signed_url
 *
 * @return array|mixed|object
 */
function yelp_widget_curl( $signed_url ) {

	// Send Yelp API Call using WP's HTTP API
	$data = wp_remote_get( $signed_url );

	// Use curl only if necessary
	if ( empty( $data['body'] ) ) {

		$ch = curl_init( $signed_url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		$data = curl_exec( $ch ); // Yelp response
		curl_close( $ch );
		$data     = yelp_update_http_for_ssl( $data );
		$response = json_decode( $data );

	} else {
		$data     = yelp_update_http_for_ssl( $data );
		$response = json_decode( $data['body'] );
	}

	// Handle Yelp response data
	return $response;

}

/**
 * Retrieves search results based on a search term and location.
 *
 * @since 1.5.0
 *
 * @param string $key Yelp Fusion API Key.
 * @param string $term The search term, usually a business name.
 * @param string $location The location within which to search.
 * @param string $limit Number of businesses to return.
 * @param string $sort_by Optional. Sort the results by one of the these modes:
 *                         best_match, rating, review_count or distance. Defaults to best_match.
 *
 * @return array Associative array containing the response body.
 */
function yelp_widget_fusion_search( $key, $term, $location, $limit, $sort_by ) {
	switch ( $sort_by ) {
		case '0':
			$sort_by = 'best_match';
			break;
		case '1':
			$sort_by = 'distance';
			break;
		case '2':
			$sort_by = 'rating';
			break;
		default:
			$sort_by = 'best_match';
	}

	$url = add_query_arg(
		array(
			'term'     => $term,
			'location' => $location,
			'limit'    => $limit,
			'sort_by'  => $sort_by,
		),
		'https://api.yelp.com/v3/businesses/search'
	);

	$args = array(
		'user-agent' => '',
		'headers'    => array(
			'authorization' => 'Bearer ' . $key,
		),
	);

	$response = yelp_widget_fusion_get( $url, $args );

	return $response;
}

/**
 * Retrieves business details based on Yelp business ID.
 *
 * @since 2.0.0
 *
 * @param string $key Yelp Fusion API Key.
 * @param string $id The Yelp business ID.
 * @param int $reviews_option 1 if reviews should be displayed. 0 otherwise.
 *
 * @return array Associative array containing the response body.
 */
function yelp_widget_fusion_get_business( $key, $id, $reviews_option = 0 ) {
	$url = 'https://api.yelp.com/v3/businesses/' . $id;

	$args = array(
		'user-agent' => '',
		'headers'    => array(
			'authorization' => 'Bearer ' . $key,
		),
	);

	$response = yelp_widget_fusion_get( $url, $args );

	if ( $reviews_option ) {
		$reviews_response = yelp_fusion_get_reviews( $key, $id );

		if ( ! empty( $reviews_response ) and isset( $reviews_response->reviews[0] ) ) {
			$response->reviews = $reviews_response->reviews;
		}
	}

	return $response;
}

/**
 * Retrieves reviews based on Yelp business ID.
 *
 * @since 2.0.0
 *
 * @param string $key Yelp Fusion API Key.
 * @param string $id The Yelp business ID.
 *
 * @return array Associative array containing the response body.
 */
function yelp_fusion_get_reviews( $key, $id ) {
	$url = 'https://api.yelp.com/v3/businesses/' . $id . '/reviews';

	$args = array(
		'user-agent' => '',
		'headers'    => array(
			'authorization' => 'Bearer ' . $key,
		),
	);

	$response = yelp_widget_fusion_get( $url, $args );

	return $response;
}

/**
 * Retrieves a response from a safe HTTP request using the GET method.
 *
 * @since 1.5.0
 *
 * @see   wp_safe_remote_get()
 *
 * @return bool|array Associative array containing the response body.
 */
function yelp_widget_fusion_get( $url, $args = array() ) {
	$response = wp_safe_remote_get( $url, $args );

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$response = yelp_update_http_for_ssl( $response );
	$response = json_decode( $response['body'] );

	/**
	 * Filters the Yelp Fusion API response.
	 *
	 * @since 1.5.0
	 */
	return apply_filters( 'yelp_fusion_api_response', $response );
}

/**
 * Generates a star image based on numerical rating.
 *
 * @since 1.5.0
 *
 * @param int|float $rating Numerical rating between 0 and 5 in increments of 0.5.
 *
 * @return string Responsive image element.
 */
function yelp_widget_fusion_stars( $rating = 0 ) {
	$ext          = '.png';
	$floor_rating = floor( $rating );

	if ( $rating != $floor_rating ) {
		$image_name = $floor_rating . '_half';
	} else {
		$image_name = $floor_rating;
	}

	$uri_image_name = YELP_WIDGET_MS_URL . '/assets/images/stars/regular_' . $image_name;
	$single         = $uri_image_name . $ext;
	$double         = $uri_image_name . '@2x' . $ext;
	$triple         = $uri_image_name . '@3x' . $ext;
	$srcset         = "{$single}, {$double} 2x, {$triple} 3x";
	$decimal_rating = number_format( $rating, 1, '.', '' );

	echo '<img class="rating" srcset="' . esc_attr( $srcset ) . '" src="' . esc_attr( $single ) . '" title="' . $decimal_rating . ' star rating" alt="' . $decimal_rating . ' star rating">';
}

/**
 * Displays responsive Yelp logo.
 *
 * @since 1.5.0
 *
 * @return string Responsive image element.
 */
function yelp_widget_fusion_logo() {
	$image_name     = 'yelp-widget-logo';
	$ext            = '.png';
	$uri_image_name = YELP_WIDGET_MS_URL . '/assets/images/' . $image_name;
	$single         = $uri_image_name . $ext;
	$double         = $uri_image_name . '@2x' . $ext;
	$srcset         = "{$single}, {$double} 2x";

	echo '<img class="ywp-logo" srcset="' . esc_attr( $srcset ) . '" src="' . esc_attr( $single ) . '" alt="Yelp logo">';
}

/**
 * Function update http for SSL
 *
 * @param $data
 *
 * @return mixed
 */
function yelp_update_http_for_ssl( $data ) {

	if ( ! empty( $data['body'] ) && is_ssl() ) {
		$data['body'] = str_replace( 'http:', 'https:', $data['body'] );
	} elseif ( is_ssl() ) {
		$data = str_replace( 'http:', 'https:', $data );
	}
	$data = str_replace( 'http:', 'https:', $data );

	return $data;

}
