<?php
/*
 * See https://github.com/rogerlos/cmb2-metatabs-options
 * Version 1.0.2
 */
class Cmb2_Metatabs_Options {

	/**
	 * Whether settings notices have already been set
	 *
	 * @var bool
	 *
	 * @since  1.0.0
	 */
	protected static $once = false;

	/**
	 * Options page hook, equivalent to get_current_screen()['id']
	 *
	 * @var string
	 *
	 * @since  1.0.0
	 */
	protected static $options_page = '';

	/**
	 * $props: Properties which can be injected via constructor
	 * Note that
	 *
	 * @var array
	 *
	 * @since  1.0.0
	 */
	private static $props = array(
		'key'        => 'my_options',              // WP options slug
		'title'      => 'My Options',              // Page title
		'topmenu'    => '',                        // See 'parent_slug' in menuargs
		'postslug'   => '',                        // Slug of a custom post type
		'menuargs'   => array(
			'parent_slug' => '',                   // Existing WP menu, blank for new top-level menu
			'page_title'  => '',                   // Will be set from "title" if blank
			'menu_title'  => '',                   // WP menu title
			'capability'  => 'manage_options',     // Admin capability needed to access options page
			'menu_slug'   => '',                   // Menu slug
			'icon_url'    => '',                   // URL of WP admin icon
			'position'    => null,                 // Integer required, null places at bottom of admin menu
		),
		'jsuri'      => '',                        // Location of JS if not in same directory as this class
		'boxes'      => array(),                   // Array of CMB2 metaboxes
		'tabs'       => array(),                   // Array of tab config arrays
		'cols'		 => 1,                         // Allows use of sidebar
		'savetxt'    => 'Save',                    // Text on the save button, blank removes button
	);

	/**
	 * CONSTRUCT
	 * Inject anything within the self::$props array by matching the argument keys.
	 *
	 * @param array $args    Array of arguments
	 * @throws \Exception
	 *
	 * @since  1.0.2 recurse the menuargs array with internal function instead of wp_parse_args()
	 * @since  1.0.0
	 */
	public function __construct( $args ) {

		// require CMB2
		if ( ! class_exists( 'CMB2' ) )
			throw new Exception( 'CMB2_Metatabs_Options: CMB2 is required to use this class.' );

		// parse any injected arguments and add to self::$props
		self::$props = self::parse_args_r( $args, self::$props );

		// validate the properties we were sent
		$this->validate_props();

		// add tabs: several actions depend on knowing if tabs are present
		self::$props['tabs'] = $this->add_tabs();

		// Add actions
		$this->add_wp_actions();
	}

	/**
	 * PARSE ARGUMENTS RECURSIVELY
	 * Allows us to merge multidimensional properties
	 *
	 * Thanks: https://gist.github.com/boonebgorges/5510970
	 *
	 * @param $a
	 * @param $b
	 *
	 * @return array
	 *
	 * @since 1.0.2
	 */
	public static function parse_args_r( &$a, $b ) {
		$a = (array) $a;
		$b = (array) $b;
		$r = $b;
		foreach ( $a as $k => &$v ) {
			if ( is_array( $v ) && isset( $r[ $k ] ) ) {
				$r[ $k ] = self::parse_args_r( $v, $r[ $k ] );
			} else {
				$r[ $k ] = $v;
			}
		}
		return $r;
	}

	/**
	 * VALIDATE PROPS
	 * Checks the values of critical passed properties
	 *
	 * @throws \Exception
	 *
	 * @since 1.0.2 Removed validation of menu args. Sending a plain array will no longer work!
	 * @since 1.0.1 Moved menuargs validation to within this method
	 * @since 1.0.0
	 */
	private function validate_props() {

		// if key or title do not exist, throw exception
		if ( ! self::$props['key'] || ! self::$props['title']  )
			throw new Exception( 'CMB2_Metatabs_Options: Settings key or page title missing.' );

		// set JS url
		if ( ! self::$props['jsuri'] )
			self::$props['jsuri'] = plugin_dir_url( __FILE__ ) . 'cmb2multiopts.js';

		// set columns to 1 if illegal value sent
		self::$props['cols'] = intval( self::$props['cols'] );
		if ( self::$props['cols'] > 2 || self::$props['cols'] < 1 )
			self::$props['cols'] = 1;
	}

	/**
	 * ADD WP ACTIONS
	 * Note, some additional actions are added elsewhere as they cannot be added this early.
	 *
	 * @since  1.0.0
	 */
	private function add_wp_actions() {
		// Register setting
		add_action( 'admin_init', array( $this, 'register_setting' ) );

		// Adds page to admin with menu entry
		add_action( 'admin_menu', array( $this, 'add_options_page' ), 12 );

		// Include CSS for this options page as style tag in head if tabs are configured
		add_action( 'admin_head', array( $this, 'add_css' ) );

		// Adds JS to foot
		add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts' ) );

		// Adds custom save button field, allowing save button to be added to metaboxes
		add_action( 'cmb2_render_options_save_button', array( $this, 'render_save_button' ), 10, 1 );
	}

	/**
	 * REGISTER SETTING
	 *
	 * @since  1.0.0
	 */
	public function register_setting() {
		register_setting( self::$props['key'], self::$props['key'] );
	}

	/**
	 * ADD OPTIONS PAGE
	 *
	 * @since 1.0.2 Moved the callback determination to build_menu_args()
	 * @since 1.0.0
	 */
	public function add_options_page() {

		// build arguments
		$args = $this->build_menu_args();

		// this is kind of ugly, but so is the WP function!
		self::$options_page = $args['cb']( $args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6] );

		// Include CMB CSS in the head to avoid FOUC, called here as we need the screen ID
		add_action( 'admin_print_styles-' . self::$options_page , array( 'CMB2_hookup', 'enqueue_cmb_css' ) );

		// Adds existing metaboxes, see note in function, called here as we need the screen ID
		add_action( 'add_meta_boxes_' . self::$options_page, array( $this, 'add_metaboxes' ) );

		// On page load, do "metaboxes" actions, called here as we need the screen ID
		add_action( 'load-' . self::$options_page, array( $this, 'do_metaboxes' ) );
	}

	/**
	 * BUILD MENU ARGS
	 * Builds the arguments needed to add options page to admin menu if they are not injected.
	 *
	 * Including either self::$props['topmenu'] or self::$props['menuargs']['parent_slug'] will trigger the creation
	 * of a submenu, otherwise a new top menu will be added.
	 *
	 * @return array
	 *
	 * @since 1.0.2 Removed counting of menu arguments, function now uses menuargs keys
	 * @since 1.0.1 Removed menuargs validation to validate_props() method
	 * @since 1.0.0
	 */
	private function build_menu_args() {

		$args = array();

		// set the top menu if either topmenu or the menuargs parent_slug is set
		$parent = self::$props['topmenu'] ? self::$props['topmenu'] : '';
		$parent = self::$props['menuargs']['parent_slug'] ?
			self::$props['menuargs']['parent_slug'] : $parent;

		// set the page title; overrides 'title' with menuargs 'page_title' if set
		$pagetitle = self::$props['menuargs']['page_title'] ?
			self::$props['menuargs']['page_title'] : self::$props['title'];

		// sub[0] : parent slug
		if ( $parent ) {
			// add a post_type get variable, to allow post options pages, if set
			$add = self::$props['postslug'] ? '?post_type=' . self::$props['postslug'] : '';
			$args[] = $parent . $add;
		}

		// top[0], sub[1] : page title
		$args[] = $pagetitle;

		// top[1], sub[2] : menu title, defaults to page title if not set
		$args[] = self::$props['menuargs']['menu_title'] ?
			self::$props['menuargs']['menu_title'] : $pagetitle;

		// top[2], sub[3] : capability
		$args[] = self::$props['menuargs']['capability'];

		// top[3], sub[4] : menu_slug, defaults to options slug if not set
		$args[] = self::$props['menuargs']['menu_slug'] ?
			self::$props['menuargs']['menu_slug'] : self::$props['key'];

		// top[4], sub[5] : callable function
		$args[] = array( $this, 'admin_page_display' );

		// top menu icon and menu position
		if ( ! $parent ) {

			// top[5] icon url
			$args[] = self::$props['menuargs']['icon_url'] ?
				self::$props['menuargs']['icon_url'] : '';

			// top[6] menu position
			$args[] = intval( self::$props['menuargs']['position'] );
		}

		// sub[6] : unused, but returns consistent array
		else {
			$args[] = null;
		}

		// set which WP function will be called based on $parent
		$args['cb'] = $parent ? 'add_submenu_page' : 'add_menu_page';

		return $args;
	}

	/**
	 * ADD SCRIPTS
	 * Add WP's metabox script, either by itself or as dependency of the tabs script. Added only to this options page.
	 * If you roll your own script, note the localized values being passed here.
	 *
	 * @param string $hook_suffix
	 * @throws \Exception
	 *
	 * @since 1.0.1 Always add postbox toggle, removed toggle from tab handler JS
	 * @since 1.0.0
	 */
	public function add_scripts( $hook_suffix ) {

		// 'postboxes' needed for metaboxes to work properly
		wp_enqueue_script( 'postbox' );

		// toggle the postboxes
		add_action( 'admin_print_footer_scripts', array( $this, 'toggle_postboxes' ) );

		// only add the main script to the options page if there are tabs present
		if ( $hook_suffix !== self::$options_page || empty( self::$props['tabs'] ) )
			return;

		// if self::$props['jsuri'] is empty, throw exception
		if ( ! self::$props['jsuri'] )
			throw new Exception( 'CMB2_Metatabs_Options: Tabs included but JS file not specified.' );

		// check to see if file exists, throws exception if it does not
		$headers = @get_headers( self::$props['jsuri'] );
		if ( $headers[0] == 'HTTP/1.1 404 Not Found' )
			throw new Exception( 'CMB2_Metatabs_Options: Passed Javascript file missing.' );

		// enqueue the script
		wp_enqueue_script(  self::$props['key'] . '-admin', self::$props['jsuri'], array( 'postbox' ), false, true );

		// localize script to give access to this page's slug
		wp_localize_script(  self::$props['key'] . '-admin', 'cmb2OptTabs', array(
			'key'        =>  self::$props['key'],
			'posttype'   => self::$props['postslug'],
			'defaulttab' => self::$props['tabs'][0]['id'],
		) );
	}

	/**
	 * TOGGLE POSTBOXES
	 * Ensures boxes are toggleable on non tabs pages
	 *
	 * @since 1.0.0
	 */
	public function toggle_postboxes() {
		echo '<script>jQuery(document).ready(function(){postboxes.add_postbox_toggles("postbox-container");});</script>';
	}

	/**
	 * ADD CSS
	 * Adds a couple of rules to clean up WP styles if tabs are included
	 *
	 * @since 1.0.0
	 */
	public function add_css() {

		// if tabs are not being used, return
		if ( empty( self::$props['tabs'] ) )
			return;

		// add css to clean up tab styles in admin when used in a postbox
		$css = '<style type="text/css">';
		$css .= '#poststuff h2.nav-tab-wrapper{padding-bottom:0;margin-bottom: 20px;}';
		$css .= '.opt-hidden{display:none;}';
		$css .= '#side-sortables{padding-top:22px;}';
		$css .= '</style>';

		echo $css;
	}

	/**
	 * ADD METABOXES
	 * Adds CMB2 metaboxes.
	 *
	 * @since  1.0.0
	 */
	public function add_metaboxes() {

		// get the metaboxes
		self::$props['boxes'] = $this->cmb2_metaboxes();

		foreach ( self::$props['boxes'] as $box ) {

			// skip if this should not be shown
			if ( ! $this->should_show( $box ) )
				continue;

			$id = $box->meta_box['id'];

			// add notice if settings are saved
			add_action( 'cmb2_save_options-page_fields_' . $id, array( $this, 'settings_notices' ), 10, 2 );

			// add callback if tabs are configured which hides metaboxes until moved into proper tabs if not in sidebar
			if ( ! empty( self::$props['tabs'] ) && $box->meta_box['context'] !== 'side' )
				add_filter( 'postbox_classes_' . self::$options_page . '_' . $id, array( $this, 'hide_metabox_class' ) );

			// if boxes are closed by default...
			if ( $box->meta_box['closed'] )
				add_filter( 'postbox_classes_' . self::$options_page . '_' . $id, array( $this, 'close_metabox_class' ) );

			// add meta box
			add_meta_box(
				$box->meta_box['id'],
				$box->meta_box['title'],
				array( $this, 'metabox_callback' ),
				self::$options_page,
				$box->meta_box['context'],
				$box->meta_box['priority']
			);
		}
	}

	/**
	 * SHOULD SHOW
	 * Mimics the CMB2 "should show" function to prevent boxes which should not be shown on this options page from
	 * appearing.
	 *
	 * @param CMB2 $box
	 * @return bool
	 *
	 * @since  1.0.0
	 */
	private function should_show( $box ) {

		// if the show_on key is not set, don't show
		if ( ! isset( $box->meta_box['show_on']['key'] ) )
			return false;

		// if the key is set but is not set to options-page, don't show
		if ( $box->meta_box['show_on']['key'] != 'options-page' )
			return false;

		// if this options key is not in the show_on value, don't show
		if ( ! in_array( self::$props['key'], $box->meta_box['show_on']['value'] ) )
			return false;

		return true;
	}

	/** HIDE METABOX CLASS
	 * The "hidden" class hides metaboxes until they have been moved to appropriate tab, if tabs are used.
	 *
	 * @param array $classes
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function hide_metabox_class( $classes ) {
		$classes[] = 'opt-hidden';
		return $classes;
	}

	/**
	 * CLOSE METABOX CLASS
	 * Adds class to closed-by-default metaboxes
	 *
	 * @param array $classes
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function close_metabox_class( $classes ) {
		$classes[] = 'closed';
		return $classes;
	}

	/**
	 * DO METABOXES
	 * Triggers the loading of our metaboxes on this screen.
	 *
	 * @since 1.0.0
	 */
	public function do_metaboxes() {
		do_action( 'add_meta_boxes_' . self::$options_page, null );
		do_action( 'add_meta_boxes', self::$options_page, null );
	}

	/**
	 * METABOX CALLBACK
	 * Builds the fields and saves them.
	 *
	 * @since 1.0.1 Refactored the save tests to method should_save()
	 * @since 1.0.0
	 */
	public static function metabox_callback() {

		// get the metabox, fishing the ID out of the arguments array
		$args = func_get_args();
		$cmb = cmb2_get_metabox( $args[1]['id'], self::$props['key'] );

		// save fields
		if ( self::should_save( $cmb ) ) {
			$cmb->save_fields( self::$props['key'], $cmb->mb_object_type(), $_POST );
		}

		// show the fields
		$cmb->show_form();
	}

	/**
	 * SHOULD SAVE
	 * Determine whether the CMB2 object should be saved. All tests must be true, hence return false for
	 * any failure.
	 *
	 * @param \CMB2 $cmb
	 * @return bool
	 *
	 * @since 1.0.1
	 */
	private static function should_save( $cmb ) {
		// was this flagged to save fields?
		if ( ! $cmb->prop( 'save_fields' ) )
			return false;
		// are these values set?
		if ( ! isset( $_POST['submit-cmb'], $_POST['object_id'], $_POST[ $cmb->nonce() ] ) )
			return false;
		// does the nonce match?
		if ( ! wp_verify_nonce( $_POST[ $cmb->nonce() ], $cmb->nonce() ) )
			return false;
		// does the object_id equal the settings key?
		if ( ! $_POST['object_id'] == self::$props['key'] )
			return false;
		return true;
	}

	/**
	 * ADMIN PAGE DISPLAY
	 * Admin page markup.
	 *
	 * @since  1.0.0
	 */
	public function admin_page_display() {

		// Page wrapper
		echo '<div class="wrap cmb2-options-page ' . self::$props['key'] . '">';

		// Title
		echo '<h2>' . esc_html( get_admin_page_title() ) . '</h2>';

		// allows filter to inject HTML before the form
		echo apply_filters( 'cmb2metatabs_before_form', '' );

		// form wraps all tabs
		echo '<form class="cmb-form" method="post" id="cmo-options-form" '
			 . 'enctype="multipart/form-data" encoding="multipart/form-data">';

		// hidden object_id field
		echo '<input type="hidden" name="object_id" value="' . self::$props['key'] . '">';

		// add postbox, which allows use of metaboxes
		echo '<div id="poststuff">';

		// main column
		echo '<div id="post-body" class="metabox-holder columns-' . self::$props['cols'] . '">';

		// if two columns are called for
		if ( self::$props['cols'] == 2 ) {

			// add markup for sidebar
			echo '<div id="postbox-container-1" class="postbox-container">';
			echo '<div id="side-sortables" class="meta-box-sortables ui-sortable">';

			// add sidebar metaboxes
			do_meta_boxes( self::$options_page, 'side', null );

			echo '</div></div>';  // close sidebar
		}

		// open postbox container
		echo '<div id="postbox-container-';
		echo self::$props['cols'] == 2 ? '2' : '1';
		echo '" class="postbox-container">';

		// add tabs; the sortables container is within each tab
		echo $this->render_tabs();

		// place normal boxes, note that 'normal' and 'advanced' are rendered together when using tabs
		do_meta_boxes( self::$options_page, 'normal', null );

		// place advanced boxes
		do_meta_boxes( self::$options_page, 'advanced', null );

		echo '</div>';  // close postbox container
		echo '</div>';  // close post-body
		echo '</div>';	// close postbox

		// add submit button if savetxt was included
		if ( self::$props['savetxt'] ) {
			echo '<div style="clear:both;">';
			self::render_save_button( self::$props['savetxt'] );
			echo '</div>';
		}

		echo '</form>';  // close form

		// allows filter to inject HTML after the form
		echo apply_filters( 'cmb2metatabs_after_form', '' );

		echo '</div>';  // close wrapper

		// reset the notices flag
		self::$once = false;
	}

	/**
	 * RENDER SAVE BUTTON
	 * If this was called in the context of a CMB2 field, use the "desc" for the save text.
	 *
	 * @param string|\CMB2_Field $field
	 *
	 * @since 1.0.0
	 */
	public static function render_save_button( $field = '' ) {
		$text = is_string( $field ) ? $field : $field->args['desc'];
		if ( $text )
			echo '<input type="submit" name="submit-cmb" value="' . $text . '" class="button-primary">';
	}

	/**
	 * SETTINGS NOTICES
	 * Added a check to make sure its only called once for the page...
	 *
	 * @param string $object_id
	 * @param array  $updated
	 *
	 * @since 1.0.1 updated text domain
	 * @since 1.0.0
	 */
	public function settings_notices( $object_id, $updated ) {

		// bail if this isn't a notice for this page or we've already added a notice
		if ( $object_id !== self::$props['key'] || empty( $updated ) || self::$once )
			return;

		// add notifications
		add_settings_error( self::$props['key'] . '-notices', '', __( 'Settings updated.', 'cmb2' ), 'updated' );
		settings_errors( self::$props['key'] . '-notices' );

		// set the flag so we don't pile up notices
		self::$once = true;
	}

	/**
	 * RENDER TABS
	 * Echoes tabs if they've been configured. The containers will have their metaboxes moved into them by javascript.
	 *
	 * @since 1.0.0
	 */
	private function render_tabs() {

		if ( empty( self::$props['tabs'] ) )
			return '';

		$containers = '';
		$tabs = '';

		foreach( self::$props['tabs'] as $tab ) {

			// add tabs navigation
			$tabs .= '<a href="#" id="opt-tab-' . $tab['id'] . '" class="nav-tab opt-tab" ';
			$tabs .= 'data-optcontent="#opt-content-' . $tab['id'] . '">';
			$tabs .= $tab['title'];
			$tabs .= '</a>';

			// add tabs containers, javascript will use the data attribute to move metaboxes to within proper tab
			$contents = implode( ',', $tab['boxes'] );

			// tab container markup
			$containers .= '<div class="opt-content" id="opt-content-' . $tab['id'] . '" ';
			$containers .= ' data-boxes="' . $contents . '">';
			$containers .= $tab['desc'];
			$containers .= '<div class="meta-box-sortables ui-sortable">';
			$containers .= '</div>';
			$containers .= '</div>';
		}

		// add the tab structure to the page
		$return = '<h2 class="nav-tab-wrapper">';
		$return .= $tabs;
		$return .= '</h2>';
		$return .= $containers;

		return $return;
	}

	/**
	 * CMB2 METABOXES
	 * Allows three methods of adding metaboxes:
	 *
	 * 1) Injected boxes are added to the boxes array
	 * 2) Add additional boxes (or boxes if none were injected) the usual way within this function
	 * 3) If array is still empty, call CMB2_Boxes::get_all();
	 *
	 * @return array|\CMB2[]
	 *
	 * @since 1.0.0
	 */
	private function cmb2_metaboxes() {
		// add any injected metaboxes
		$boxes = self::$props['boxes'];
		// if $boxes is still empty, see if they've been configured elsewhere in the program
		return empty( $boxes ) ? CMB2_Boxes::get_all() : $boxes;
	}

	/**
	 * ADD TABS
	 * Add tabs to your options page. The array is empty by default. You can inject them into the constructor,
	 * or add them here, or leave empty for no tabs.
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	private function add_tabs() {
		// add any injected tabs
		$tabs = self::$props['tabs'];
		return $tabs;
	}
}