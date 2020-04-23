<?php
/**
 * Test if a registered tab has no  fields.
 * Expected result: Show empty tab panel.
 */
add_filter(
	'rwmb_meta_boxes',
	function ( $meta_boxes ) {
		$meta_boxes[] = array(
			'title'  => __( 'Meta Box Tabs Demo', 'rwmb' ),

			'tabs'   => array(
				'tab1' => 'Title 1',
				'tab2' => 'Title 2',
			),
			'fields' => array(
				array(
					'id'   => 'phone',
					'type' => 'text',
					'tab'  => 'tab1',
				),
			),
		);

		return $meta_boxes;
	}
);
