<?php
add_filter( 'rwmb_meta_boxes', function ( $meta_boxes ) {
	$meta_boxes[] = array(
		'title'     => __( 'Meta Box Tabs 3', 'rwmb' ),
		'taxonomies' => 'category',

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
		'validation' => [
			'rules' => [
				'bio'      => 'required',
				'interest' => 'required',
			],
		],
	);

	return $meta_boxes;
} );
