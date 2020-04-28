<?php
add_filter( 'mb_settings_pages', function ( $settings_pages ) {
	$settings_pages[] = array(
		'id'          => 'theme-slug',
		'option_name' => 'theme_slug',
		'menu_title'  => __( 'Theme Options', 'textdomain' ),
		'parent'      => 'themes.php',
		'help_tabs'   => array(
			array(
				'title'   => 'General',
				'content' => '<p>This tab displays the general information about the theme.</p>',
			),
			array(
				'title'   => 'Homepage',
				'content' => '<p>This tab displays the instruction for setting up the homepage.</p>',
			),
		),
	);
	$settings_pages[] = array(
		'id'          => 'pencil',
		'option_name' => 'pencil',
		'menu_title'  => __( 'Pencil', 'textdomain' ),
		'icon_url'    => 'dashicons-edit',
		'style'       => 'no-boxes',
		'columns'     => 1,
	);

	return $settings_pages;
} );

add_filter( 'rwmb_meta_boxes', 'meta_box_tabs_demo_register' );

/**
 * Register meta boxes
 *
 * @param array $meta_boxes
 *
 * @return array
 */
function meta_box_tabs_demo_register( $meta_boxes ) {
	$custom = [];

	// Normal Fields.
	$custom[] = array(
		'title' => 'Normal Fields',
		'fields' => array(
			array(
				'name' => __( 'Mobile phone', 'textdomain' ),
				'id'   => 'mobile',
				'type' => 'tel',
			),
			array(
				'name' => __( 'Address', 'textdomain' ),
				'id'   => 'address',
				'type' => 'textarea',
			),
			array(
				'name'    => __( 'City', 'textdomain' ),
				'id'      => 'city',
				'type'    => 'select_advanced',
				'options' => array(
					'hanoi' => 'Hanoi',
					'hcm'   => 'Ho Chi Minh City'
				),
			),
			array(
				'name' => __( 'Upload avatar', 'textdomain' ),
				'id'   => 'avatar',
				'type' => 'image_advanced',
			),
			array(
				'name' => __( 'Upload 2', 'textdomain' ),
				'id'   => 'avatar2',
				'type' => 'image_upload',
			),
			array(
				'name'    => __( 'Type', 'textdomain' ),
				'id'      => 'type',
				'type'    => 'radio',
				'options' => array(
					'normal'  => 'Normal',
					'bronze'  => 'Bronze',
					'gold'    => 'Gold',
					'diamond' => 'Diamond',
				),
			),
			array(
				'name' => __( 'Description', 'textdomain' ),
				'id'   => 'desc',
				'type' => 'wysiwyg',
			),
		),
	);

	// Tabs: Default.
	$custom[] = array(
		'title'     => 'Tabs: Default',
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
		'tab_style' => 'default',
		'fields'    => array(
			array(
				'name' => __( 'Name', 'rwmb' ),
				'id'   => 'name',
				'type' => 'text',
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

	// Tabs: Boxed.
	$custom[] = array(
		'title'     => 'Tabs: Boxed',
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

	// Tabs: Left.
	$custom[] = array(
		'title'     => 'Tabs: Left',
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

	// Tabs: No wrapper.
	$custom[] = array(
		'title'       => 'Tabs: No wrapper',
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

	// Columns Only.
	$custom[] = array(
		'title'  => 'Columns Only',
		'columns' => array(
			'column-0' => 4,
			'column-1' => 4,
			'column-2' => array(
				'size' => 6,
				'class' => 'column-2-class',
			),
			'column-3' => 4,
		),
		'fields' => array(
			array(
				'name'    => __( 'Address', 'rwmb' ),
				'id'      => 'address',
				'type'    => 'text',
				'column'  => 'column-0',
			),
			array(
				'name'    => __( 'Twitter', 'rwmb' ),
				'id'      => 'twitter',
				'type'    => 'text',
				'column'  => 'column-0',
			),

			array(
				'name'    => __( 'Name', 'rwmb' ),
				'id'      => 'name',
				'type'    => 'text',
				'column'  => 'column-1',
			),
			array(
				'name'    => __( 'Email', 'rwmb' ),
				'id'      => 'email',
				'type'    => 'email',
				'column'  => 'column-1',
			),
			array(
				'name'    => __( 'Mobile', 'rwmb' ),
				'id'      => 'mobile',
				'type'    => 'text',
				'column'  => 'column-1',
			),

			array(
				'name'    => __( 'State', 'rwmb' ),
				'id'      => 'state',
				'type'    => 'select_advanced',
				'options' => array(
					'NY' => 'New York',
					'CA' => 'California',
				),
				'column'  => 'column-2',
			),
			array(
				'name'    => __( 'Zipcode', 'rwmb' ),
				'id'      => 'zipcode',
				'type'    => 'text',
				'column'  => 'column-2',
			),
			array(
				'name'    => __( 'Description', 'rwmb' ),
				'id'      => 'description',
				'type'    => 'textarea',
				'column'  => 'column-2',
			),

			array(
				'name'    => __( 'Google+', 'rwmb' ),
				'id'      => 'google',
				'type'    => 'text',
				'column'  => 'column-3',
			),
			array(
				'name'    => __( 'Facebook', 'rwmb' ),
				'id'      => 'facebook',
				'type'    => 'text',
				'column'  => 'column-3',
			),
		),
	);

	// Columns + Tabs.
	$custom[] = array(
		'title'  => 'Columns + Tabs',
		'tabs'   => array(
			'tab1' => __( 'Tab 1', 'rwmb' ),
			'tab2' => __( 'Tab 2', 'rwmb' ),
		),
		'columns' => array(
			'column-0' => 4,
			'column-1' => 8,
			'column-2' => array(
				'size' => 8,
				'class' => 'column-2-class',
			),
			'column-3' => 4,
		),
		'fields' => array(
			array(
				'name'    => __( 'Address', 'rwmb' ),
				'id'      => 'address',
				'type'    => 'text',
				'column'  => 'column-0',
				'tab'     => 'tab1',
			),
			array(
				'name'    => __( 'Twitter', 'rwmb' ),
				'id'      => 'twitter',
				'type'    => 'text',
				'column'  => 'column-0',
				'tab'     => 'tab1',
			),

			array(
				'name'    => __( 'Name', 'rwmb' ),
				'id'      => 'name',
				'type'    => 'text',
				'column'  => 'column-1',
				'tab'     => 'tab1',
			),
			array(
				'name'    => __( 'Email', 'rwmb' ),
				'id'      => 'email',
				'type'    => 'email',
				'column'  => 'column-1',
				'tab'     => 'tab1',
			),
			array(
				'name'    => __( 'Mobile', 'rwmb' ),
				'id'      => 'mobile',
				'type'    => 'text',
				'column'  => 'column-1',
				'tab'     => 'tab1',
			),

			array(
				'name'    => __( 'State', 'rwmb' ),
				'id'      => 'state',
				'type'    => 'select_advanced',
				'options' => array(
					'NY' => 'New York',
					'CA' => 'California',
				),
				'column'  => 'column-2',
				'tab'     => 'tab2',
			),
			array(
				'name'    => __( 'Zipcode', 'rwmb' ),
				'id'      => 'zipcode',
				'type'    => 'text',
				'column'  => 'column-2',
				'tab'     => 'tab2',
			),
			array(
				'name'    => __( 'Description', 'rwmb' ),
				'id'      => 'description',
				'type'    => 'textarea',
				'column'  => 'column-2',
				'tab'     => 'tab2',
			),

			array(
				'name'    => __( 'Google+', 'rwmb' ),
				'id'      => 'google',
				'type'    => 'text',
				'column'  => 'column-3',
				'tab'     => 'tab2',
			),
			array(
				'name'    => __( 'Facebook', 'rwmb' ),
				'id'      => 'facebook',
				'type'    => 'text',
				'column'  => 'column-3',
				'tab'     => 'tab2',
			),
		),
	);

	// Groups Only.
	$custom[] = [
		'title'  => 'Groups Only',
		'fields' => [
			array(
				'name'       => 'Group: No clone',
				'id'         => 'numbered_items2',
				'type'       => 'group',
				'fields'     => array(
					array(
						'name'    => __( 'Col 3', 'indigo-metaboxes' ),
						'id'      => 'title',
						'type'    => 'text',
						'class'   => 'big-text',
					),
					array(
						'name'    => __( 'Col 3', 'indigo-metaboxes' ),
						'id'      => 'desc',
						'type'    => 'text',
						'class'   => 'big-text2',
					),
				),
			),
			array(
				'name'       => 'Group: Collapsible',
				'type'       => 'group',
				'clone'      => true,
				'collapsible' => true,
				'fields'     => array(
					array(
						'name'    => __( 'Col 9', 'indigo-metaboxes' ),
						'id'      => 'title2',
						'type'    => 'text',
						'class'   => 'big-text3',
					),
					array(
						'name'    => __( 'Col 3', 'indigo-metaboxes' ),
						'id'      => 'desc2',
						'type'    => 'text',
						'class'   => 'big-text4',
					),
				),
			),
		],
	];

	// Columns + Groups.
	$custom[] = [
		'title'  => 'Columns + Groups',
		'fields' => [
			array(
				'name'       => 'Group 6: No clone',
				'id'         => 'numbered_items',
				'type'       => 'group',
				'columns'    => 6,
				'fields'     => array(
					array(
						'name'    => __( 'Col 3', 'indigo-metaboxes' ),
						'id'      => 'title',
						'type'    => 'text',
						'class'   => 'big-text',
						'columns' => 3,
					),
					array(
						'name'    => __( 'Col 3', 'indigo-metaboxes' ),
						'id'      => 'desc',
						'type'    => 'text',
						'class'   => 'big-text2',
						'columns' => 3,
					),
				),
			),
			array(
				'name'       => 'Group 6: Collapsible',
				'id'         => 'numbered_items2',
				'type'       => 'group',
				'columns'    => 6,
				'clone'      => true,
				'collapsible' => true,
				'fields'     => array(
					array(
						'name'    => __( 'Col 9', 'indigo-metaboxes' ),
						'id'      => 'title2',
						'type'    => 'text',
						'class'   => 'big-text3',
						'columns' => 9,
					),
					array(
						'name'    => __( 'Col 3', 'indigo-metaboxes' ),
						'id'      => 'desc2',
						'type'    => 'text',
						'class'   => 'big-text4',
						'columns' => 3,
					),
				),
			),
		],
	];

	// Set for posts, terms, categories, settings pages.
	foreach ( $custom as $mb ) {
		// Posts.
		$a = $mb;
		$a['id'] = uniqid();
		$meta_boxes[] = $a;

		// Users.
		$b = $mb;
		$b['id'] = uniqid();
		$b['type'] = 'user';
		$meta_boxes[] = $b;

		// Taxonomies.
		$c = $mb;
		$c['id'] = uniqid();
		$c['taxonomies'] = 'category';
		$meta_boxes[] = $c;

		// Settings Page: boxes.
		$d = $mb;
		$d['id'] = uniqid();
		$d['settings_pages'] = 'theme-slug';
		$meta_boxes[] = $d;

		// Settings Page: no-boxes.
		$e = $mb;
		$e['id'] = uniqid();
		$e['settings_pages'] = 'pencil';
		$meta_boxes[] = $e;
	}

	return $meta_boxes;
}
