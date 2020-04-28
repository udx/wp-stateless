<?php
add_filter( 'rwmb_meta_boxes', 'meta_box_tabs_demo_register' );

/**
 * Register meta boxes
 *
 * @param array $meta_boxes
 *
 * @return array
 */
function meta_box_tabs_demo_register( $meta_boxes ) {
	// 1st Meta Box
	$meta_boxes[] = array(
		'title'     => __( 'Meta Box Tabs Demo', 'rwmb' ),
		'id' => 'unique',
		'taxonomies' => 'category',
		// 'type' => 'user',

		// List of tabs, in one of the following formats:
		// 1) key => label
		// 2) key => array( 'label' => Tab label, 'icon' => Tab icon )
		'tabs'      => array(
			'contact' => array(
				'label' => __( 'Contact Information', 'rwmb' ),
				'icon'  => 'dashicons-email', // Dashicon
			),
			'social'  => array(
				'label' => __( 'Social Media Profiles', 'rwmb' ),
				'icon'  => 'dashicons-share', // Dashicon
			),
			'note'    => array(
				'label' => __( 'Other Information', 'rwmb' ),
				'icon'  => 'http://i.imgur.com/nJtag1q.png', // Custom icon, using image
			),
		),

		// Tab style: 'default', 'box' or 'left'. Optional
		'tab_style' => 'default',

		'fields'    => array(
			array(
				'name' => __( 'Name', 'rwmb' ),
				'id'   => 'name',
				'type' => 'text',

				// Which tab this field belongs to? Put tab key here
				'tab'  => 'contact',
			),
			array(
				'name' => __( 'Email', 'rwmb' ),
				'id'   => 'email',
				'type' => 'email',
				'tab'  => 'contact',
			),
			array(
				'name' => __( 'Facebook', 'rwmb' ),
				'id'   => 'facebook',
				'type' => 'text',
				'tab'  => 'social',
			),
			array(
				'name' => __( 'Google+', 'rwmb' ),
				'id'   => 'google',
				'type' => 'text',
				'tab'  => 'social',
			),
			array(
				'name' => __( 'Note', 'rwmb' ),
				'id'   => 'note',
				'type' => 'textarea',
				'tab'  => 'note',
			),
		),
	);

	// 2nd Meta Box: Tab style - boxed
	$meta_boxes[] = array(
		'title'     => __( 'Meta Box Tabs 2', 'rwmb' ),
		'taxonomies' => 'category',
		// 'type' => 'user',
		'tabs'      => array(
			'bio'      => __( 'Biography', 'rwmb' ),
			'interest' => __( 'Interest', 'rwmb' ),
		),
		'tab_style' => 'box',
		'fields'    => array(
			array(
				'name' => __( 'Bio', 'rwmb' ),
				'id'   => 'bio',
				'type' => 'textarea',
				'tab'  => 'bio',
			),
			array(
				'name' => __( 'Interest', 'rwmb' ),
				'id'   => 'interest',
				'type' => 'textarea',
				'tab'  => 'interest',
			),
		),
	);

	// 3rd Meta Box: Tab style - left
	$meta_boxes[] = array(
		'title'     => __( 'Meta Box Tabs 3', 'rwmb' ),
		'taxonomies' => 'category',
		// 'type' => 'user',

		'tabs'      => array(
			'bio'      => __( 'Biography Description', 'rwmb' ),
			'interest' => __( 'Interests and Hobbies', 'rwmb' ),
			'job'      => __( 'Job Title and Description', 'rwmb' ),
		),
		'tab_style' => 'left',
		'fields'    => array(
			array(
				'name' => __( 'Bio', 'rwmb' ),
				'id'   => 'bio',
				'type' => 'textarea',
				'tab'  => 'bio',
			),
			array(
				'name' => __( 'Interest', 'rwmb' ),
				'id'   => 'interest',
				'type' => 'textarea',
				'tab'  => 'interest',
			),
			array(
				'name' => __( 'Job Description', 'rwmb' ),
				'id'   => 'job_desc',
				'type' => 'textarea',
				'tab'  => 'job',
			),
		),
	);

	// 4th Meta Box: No wrapper
	$meta_boxes[] = array(
		'title'       => __( 'Meta Box Tabs 4', 'rwmb' ),
		'taxonomies' => 'category',
		// 'type' => 'user',
		'tabs'        => array(
			'contact' => array(
				'label' => __( 'Custom Information', 'rwmb' ),
				'icon'  => 'dashicons-email', // Dashicon
			),
			'social'  => array(
				'label' => __( 'Social Network Profiles', 'rwmb' ),
				'icon'  => 'dashicons-share', // Dashicon
			),
		),
		'tab_style'   => 'box',
		'tab_wrapper' => false,
		'fields'      => array(
			array(
				'name' => __( 'Name', 'rwmb' ),
				'id'   => 'name2',
				'type' => 'text',
				'tab'  => 'contact',
			),
			array(
				'name' => __( 'Email', 'rwmb' ),
				'id'   => 'email2',
				'type' => 'text',
				'tab'  => 'contact',
			),
			array(
				'name' => __( 'Address', 'rwmb' ),
				'id'   => 'address',
				'type' => 'text',
				'tab'  => 'contact',
			),
			array(
				'name' => __( 'Google+', 'rwmb' ),
				'id'   => 'googleplus2',
				'type' => 'text',
				'tab'  => 'social',
			),
		),
	);

	return $meta_boxes;
}
