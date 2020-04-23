<?php
// Prevent loading this file directly.
defined( 'ABSPATH' ) || die;

if ( ! class_exists( 'MB_Tabs' ) ) {
	class MB_Tabs {
		/**
		 * Indicate that the instance of the class is working on a meta box that has tabs or not.
		 * It will be set 'true' BEFORE meta box is display and 'false' AFTER.
		 *
		 * @var bool
		 */
		protected $active = false;

		/**
		 * Store all output of fields.
		 * This is used to put fields in correct <div> for tabs.
		 * The fields' output will be get via filter 'rwmb_outer_html'.
		 *
		 * @var array
		 */
		protected $fields_output = array();

		public function __construct() {
			add_action( 'rwmb_enqueue_scripts', array( $this, 'enqueue' ) );

			add_action( 'rwmb_before', array( $this, 'opening_div' ), 1 ); // 1 = display first, before tab nav.
			add_action( 'rwmb_after', array( $this, 'closing_div' ), 100 ); // 100 = display last, after tab panels.

			// Change the title position of metabox
			add_action( 'rwmb_after', array( $this, 'show_nav' ), 20 );
			add_action( 'rwmb_after', array( $this, 'show_panels' ), 30 );

			add_filter( 'rwmb_outer_html', array( $this, 'capture_fields' ), 10, 2 );
		}

		public function enqueue() {
			list( , $url ) = RWMB_Loader::get_path( dirname( __FILE__ ) );
			wp_enqueue_style( 'rwmb-tabs', $url . 'tabs.css', '', '1.1.5' );
			wp_enqueue_script( 'rwmb-tabs', $url . 'tabs.js', array( 'jquery' ), '1.1.5', true );
		}

		/**
		 * Display opening div for tabs for meta box.
		 *
		 * @param RW_Meta_Box $obj Meta Box object.
		 */
		public function opening_div( RW_Meta_Box $obj ) {
			if ( empty( $obj->meta_box['tabs'] ) ) {
				return;
			}

			$class = 'rwmb-tabs';
			if ( isset( $obj->meta_box['tab_style'] ) && 'default' !== $obj->meta_box['tab_style'] ) {
				$class .= ' rwmb-tabs-' . $obj->meta_box['tab_style'];
			}

			if ( isset( $obj->meta_box['tab_wrapper'] ) && false === $obj->meta_box['tab_wrapper'] ) {
				$class .= ' rwmb-tabs-no-wrapper';
			}

			echo '<div class="' . esc_attr( $class ) . '">';

			// Set 'true' to let us know that we're working on a meta box that has tabs.
			$this->active = true;
		}

		/**
		 * Display closing div for tabs for meta box.
		 */
		public function closing_div() {
			if ( ! $this->active ) {
				return;
			}

			echo '</div>';

			// Reset to initial state to be ready for other meta boxes.
			$this->active        = false;
			$this->fields_output = array();
		}

		/**
		 * Display tab navigation.
		 *
		 * @param RW_Meta_Box $obj Meta Box object.
		 */
		public function show_nav( RW_Meta_Box $obj ) {
			if ( ! $this->active ) {
				return;
			}

			$tabs           = $obj->meta_box['tabs'];
			$default_active = $obj->tab_default_active;

			echo '<ul class="rwmb-tab-nav">';

			$i = 0;
			foreach ( $tabs as $key => $tab_data ) {
				if ( is_string( $tab_data ) ) {
					$tab_data = ['label' => $tab_data];
				}
				$tab_data = wp_parse_args( $tab_data, [
					'icon'  => '',
					'label' => '',
				] );

				if ( filter_var( $tab_data['icon'], FILTER_VALIDATE_URL ) ) { // If icon is an URL.
					$icon = '<img src="' . esc_url( $tab_data['icon'] ) . '">';
				} else { // If icon is icon font.
					// If icon is dashicons, auto add class 'dashicons' for users.
					if ( false !== strpos( $tab_data['icon'], 'dashicons' ) ) {
						$tab_data['icon'] .= ' dashicons';
					}
					// Remove duplicate classes.
					$tab_data['icon'] = array_filter( array_map( 'trim', explode( ' ', $tab_data['icon'] ) ) );
					$tab_data['icon'] = implode( ' ', array_unique( $tab_data['icon'] ) );

					$icon = $tab_data['icon'] ? '<i class="' . esc_attr( $tab_data['icon'] ) . '"></i>' : '';
				}

				$class = "rwmb-tab-$key";
				if ( ( $default_active && $default_active === $key ) || ( ! $default_active && ! $i ) ) {
					$class .= ' rwmb-tab-active';
				}

				printf(
					'<li class="%s" data-panel="%s"><a href="#">%s%s</a></li>',
					esc_attr( $class ),
					esc_attr( $key ),
					$icon,
					esc_html( $tab_data['label'] )
				);
				$i ++;
			}

			echo '</ul>';
		}

		/**
		 * Display tab panels.
		 * Note that: this public function is hooked to 'rwmb_after', when all fields are outputted.
		 * (and captured by 'capture_fields' public function).
		 *
		 * @param RW_Meta_Box $obj Meta Box object.
		 */
		public function show_panels( RW_Meta_Box $obj ) {
			if ( ! $this->active ) {
				return;
			}

			// Store all tabs.
			$tabs = $obj->meta_box['tabs'];

			echo '<div class="rwmb-tab-panels">';
			foreach ( $this->fields_output as $tab => $fields ) {
				// Remove rendered tab.
				if ( isset( $tabs[ $tab ] ) ) {
					unset( $tabs[ $tab ] );
				}

				echo '<div class="rwmb-tab-panel rwmb-tab-panel-' . esc_attr( $tab ) . '" data-panel="' . esc_attr( $tab ) . '">';
				echo implode( '', $fields );
				echo '</div>';
			}

			// Print unrendered tabs.
			foreach ( $tabs as $tab_id => $tab_data ) {
				echo '<div class="rwmb-tab-panel rwmb-tab-panel-' . esc_attr( $tab_id ) . '">';
				echo '</div>';
			}

			echo '</div>';
		}

		/**
		 * Save field output into class variable to output later.
		 *
		 * @param string $output Field output.
		 * @param array  $field  Field configuration.
		 *
		 * @return string
		 */
		public function capture_fields( $output, $field ) {
			// If meta box doesn't have tabs, do nothing.
			if ( ! $this->active || ! isset( $field['tab'] ) ) {
				return $output;
			}

			$tab = $field['tab'];

			if ( ! isset( $this->fields_output[ $tab ] ) ) {
				$this->fields_output[ $tab ] = array();
			}
			$this->fields_output[ $tab ][] = $output;

			// Return empty string to let Meta Box plugin echoes nothing.
			return '';
		}
	}

	new MB_Tabs();
}
