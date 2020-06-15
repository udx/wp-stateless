<?php
/**
 * Test if maps are rendered correctly in tabs.
 */
add_filter(
	'rwmb_meta_boxes',
	function ( $meta_boxes ) {
		$meta_boxes[] = array(
			'title'  => 'Test maps',
			'tabs'   => array(
				'tab1' => 'No maps',
				'tab2' => 'Google Maps',
				'tab3' => 'OSM',
			),
			'fields' => array(
				array(
					'id'   => 'phone',
					'type' => 'text',
					'tab'  => 'tab1',
				),
				array(
					'id'   => 'address1',
					'name' => 'Address',
					'type' => 'text',
					'tab'  => 'tab2',
				),
				array(
					'id'            => 'gg',
					'name'          => 'Google Maps',
					'type'          => 'map',
					'tab'           => 'tab2',
					'address_field' => 'address1',
					'api_key'       => 'AIzaSyCXPnD7NI_THgsIXNEWFbsGSlQMPfe71vI',
				),
				array(
					'id'   => 'address2',
					'name' => 'Address',
					'type' => 'text',
					'tab'  => 'tab3',
				),
				array(
					'id'            => 'osm',
					'type'          => 'osm',
					'tab'           => 'tab3',
					'name'          => 'OSM',
					'address_field' => 'address2',
				),
			),
		);

		return $meta_boxes;
	}
);
