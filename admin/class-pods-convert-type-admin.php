<?php
/**
 * Pods Convert Type.
 *
 * @package   Pods_Convert_Type_Admin
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * If you're interested in introducing public-facing
 * functionality, then refer to `class-plugin-name.php`
 *
 * @package Pods_Convert_Type_Admin
 */
class Pods_Convert_Type_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		/** @var Pods_Convert_Type $plugin */
		$plugin = Pods_Convert_Type::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Hook into the pods admin menu
		add_filter( 'pods_admin_menu', array( $this, 'add_plugin_admin_menu' ) );

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		/*
		 * - Uncomment following lines if the admin class should only be available for super admins
		 */
		/* if( ! is_super_admin() ) {
			return;
		} */

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), Pods_Convert_Type::VERSION );
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), Pods_Convert_Type::VERSION );
		}

	}

	/**
	 * Hooks the 'pods_admin_menu' filter
	 *
	 * @param $admin_menus
	 *
	 * @return array
	 */
	public function add_plugin_admin_menu ( $admin_menus ) {

		// Fresh array to insert our new menu item
		$new_menus = array();

		// New menu item to insert
		$plugin_menu = array(
			'label'    => 'Convert Type',
			'function' => array( $this, 'display_plugin_admin_page' ),
			'access'   => $this->plugin_slug
		);

		// Loop through the Pods menu items looking for the target to insert after
		foreach ( $admin_menus as $key => $this_menu_item ) {

			// Copy all the menu items
			$new_menus[ $key ] = $this_menu_item;

			// Insert our menu item after pods components
			if ( isset( $this_menu_item[ 'access' ] ) && 'pods_components' == $this_menu_item[ 'access' ] ) {
				$new_menus[ $this->plugin_slug ] = $plugin_menu;

				// ToDo: Proper way to do this?
				$this->plugin_screen_hook_suffix = 'pods-admin_page_' . $this->plugin_slug;
			}
		}

		return $new_menus;
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		include_once( 'views/admin.php' );
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);

	}

}
