<?php
/**
 * CMB2 Metatabs Options (CMO) Example
 * -----------------------------------
 * This file creates an options page located in under the WordPress "Settings" menu.
 *
 * If you try and run this file directly, you will see the page listed under settings, but if you try to go there, you
 * will be told you lack permissions, as this file won't have run, and hence the options page won't exist!
 *
 * Instead, uncomment 'include(example.php);' in the main plugin file to see CMO in action.
 *
 * Much more information is available at this CMB2 Metatabs Option's wiki:
 * https://github.com/rogerlos/cmb2-metatabs-options/wiki
 */


// add action to hook option page creation to
add_action( 'cmb2_admin_init', 'cmb2_metatabs_options_go' );


/**
 * Callback for 'cmb2_admin_init'.
 *
 * 'boxes' and 'tabs' call functions simply to separate "normal" CMB2 configuration
 * from configuration CMO requires. You could add them inline or see the wiki for more options.
 */
function cmb2_metatabs_options_go() {

	$options_key = 'cmb2metatabs';

	// use CMO filter to add an intro at the top of the options page
	add_filter( 'cmb2metatabs_before_form', 'cmb2_metatabs_options_add_intro_via_filter' );

	// configuration array
	$args = array(
		'key'     => $options_key,
		'title'   => 'Example Options Page',
		'topmenu' => 'options-general.php',
		'cols'    => 2,
		'boxes'	  => cmb2_metatabs_options_add_boxes( $options_key ),
		'tabs'	  => cmb2_metatabs_options_add_tabs(),
	);

	// create the options page
	new Cmb2_Metatabs_Options( $args );
}


/**
 * Callback for CMO filter. The two filters in this plugin simply echo the return from this callback, so no
 * need to manipulate the empty string they send to the filter callback.
 *
 * @return string
 */
function cmb2_metatabs_options_add_intro_via_filter() {
	return '<p>This is an options page created with CMB2 Metatabs Options. Learn more at '
		   . '<a href="https://github.com/rogerlos/cmb2-metatabs-options/" target="_blank">github</a>.</p>';
}


/**
 * We're going to add five boxes and some random fields. Two boxes will go into each tab, and the fifth will
 * go into the sidebar.
 *
 * This is a typical CMB2 configuration of metaboxes and their fields, but note the two crucial extra items:
 *
 * - ['show_on'] property is configured
 * - call to object_type method
 *
 * Explanation of what their values are and why they're important is on the wiki.
 *
 * @param string $options_key
 * @return array
 */
function cmb2_metatabs_options_add_boxes( $options_key ) {

	// holds all CMB2 box objects
	$boxes = array();

	// we will be adding this to all boxes
	$show_on = array(
		'key' => 'options-page',
		'value' => array( $options_key ),
	);

	// first box
	$cmb = new_cmb2_box( array(
		'id'        => 'ex_dogs',
		'title'     => __( 'Internet Doggies', 'cmb2' ),
		'show_on'   => $show_on, // critical, see wiki for why
	));
	$cmb->add_field( array(
		'name'       => __( 'That\'s a Good Dog!', 'cmb2' ),
		'desc'       => __( 'What do you say when you see a dog on the internet?', 'cmb2' ),
		'id'         => 'ex_dogs_say',
		'type'       => 'text',
	));
	$cmb->add_field( array(
		'name'       => __( 'Repeated How Many Times?', 'cmb2' ),
		'desc'       => __( 'To the nearest multiple 3, how many times do you say it?', 'cmb2' ),
		'id'         => 'ex_dogs_repeat',
		'type'       => 'text_small',
	));
	$cmb->object_type( 'options-page' );  // critical, see wiki for why
	$boxes[] = $cmb;

	// second box
	$cmb = new_cmb2_box( array(
		'id'        => 'ex_cats',
		'title'     => __( 'Internet Kitties', 'cmb2' ),
		'show_on'   => $show_on,
	));
	$cmb->add_field( array(
		'name'       => __( 'Nice kitty!', 'cmb2' ),
		'desc'       => __( 'What do you say when you see a cat on the internet?', 'cmb2' ),
		'id'         => 'ex_cats_say',
		'type'       => 'text',
	));
	$cmb->add_field( array(
		'name'       => __( 'Repeated How Many Times?', 'cmb2' ),
		'desc'       => __( 'To the nearest multiple 3, how many times do you say it?', 'cmb2' ),
		'id'         => 'ex_cats_repeat',
		'type'       => 'text_small',
	));
	$cmb->object_type( 'options-page' );
	$boxes[] = $cmb;

	// third box
	$cmb = new_cmb2_box( array(
		'id'        => 'ex_healthy',
		'title'     => __( 'Eating for Good Health', 'cmb2' ),
		'show_on'   => $show_on,
	));
	$cmb->add_field( array(
		'name'       => __( 'What is a healthy food?', 'cmb2' ),
		'desc'       => __( 'Examples: Apple, Ding Dong', 'cmb2' ),
		'id'         => 'ex_healthy_food',
		'type'       => 'text',
	));
	$cmb->add_field( array(
		'name'       => __( 'How Many Servings?', 'cmb2' ),
		'desc'       => __( 'How many times per day should you eat this?', 'cmb2' ),
		'id'         => 'ex_healthy_servings',
		'type'       => 'text_small',
	));
	$cmb->object_type( 'options-page' );
	$boxes[] = $cmb;

	// fourth box
	$cmb = new_cmb2_box( array(
		'id'        => 'ex_bad',
		'title'     => __( 'Foods to Avoid', 'cmb2' ),
		'show_on'   => $show_on,
	));
	$cmb->add_field( array(
		'name'       => __( 'What is an unhealthy food?', 'cmb2' ),
		'desc'       => __( 'Examples: Apple, not Ding Dong', 'cmb2' ),
		'id'         => 'ex_bad_food',
		'type'       => 'text',
	));
	$cmb->add_field( array(
		'name'       => __( 'How Many Pushups?', 'cmb2' ),
		'desc'       => __( 'To the nearest 1, how many pushups do you need to counter your bad decision?', 'cmb2' ),
		'id'         => 'ex_bad_servings',
		'type'       => 'text_small',
	));
	$cmb->object_type( 'options-page' );
	$boxes[] = $cmb;

	// fifth box
	$cmb = new_cmb2_box( array(
		'id'        => 'ex_side',
		'title'     => __( 'Judging', 'cmb2' ),
		'show_on'   => $show_on,
		'context'   => 'side'
	));
	$cmb->add_field( array(
		'name'       => '',
		'desc'       => __( 'This example page offers no judgment on your choices.', 'cmb2' ),
		'id'         => 'ex_judge',
		'type'       => 'title',
	));
	$cmb->object_type( 'options-page' );
	$boxes[] = $cmb;

	return $boxes;
}


/**
 * Configure a couple of tabs to hold the boxes. Tabs are completely optional and removing them would
 * have the configured metaboxes display on the options page sequentially.
 *
 * Note that the box in the sidebar doesn't need to be in a tab. But using tabs will hide all boxes
 * not explicitly assigned to a tab.
 *
 * @return array
 */
function cmb2_metatabs_options_add_tabs() {

	$tabs = array();

	$tabs[] = array(
		'id'    => 'ex_tab1',
		'title' => 'Critters',
		'desc'  => '<p>Everyone likes dogs and/or cats, right?</p>',
		'boxes' => array(
			'ex_cats',
			'ex_dogs',
		),
	);
	$tabs[] = array(
		'id'    => 'ex_tab2',
		'title' => 'Eats',
		'desc'  => '',
		'boxes' => array(
			'ex_healthy',
			'ex_bad',
		),
	);

	return $tabs;
}