<?php
/**
 * The main plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @since      1.0.0
 */
class Viewpoints_Plugin {

    /**
     * The singleton instance of this class.
     *
     * @since    1.0.0
     * @access   private
     * @var      Viewpoints_Plugin    $instance    The single instance of the class.
     */
    private static $instance = null;

    /**
     * The post type instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Viewpoints_Post_Type    $post_type    Handles custom post type registration.
     */
    protected $post_type;

    /**
     * The field groups instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Viewpoints_Field_Groups    $field_groups    Handles ACF field groups registration.
     */
    protected $field_groups;

    /**
     * The shortcode instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Viewpoints_Shortcode    $shortcode    Handles shortcode functionality.
     */
    protected $shortcode;

    /**
     * Main Viewpoints_Plugin Instance.
     *
     * Ensures only one instance of Viewpoints_Plugin is loaded or can be loaded.
     *
     * @since    1.0.0
     * @return Viewpoints_Plugin - Main instance.
     */
    public static function get_instance() {
        viewpoints_plugin_log('Getting plugin instance');
        if ( is_null( self::$instance ) ) {
            viewpoints_plugin_log('Creating new plugin instance');
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone() {
        viewpoints_plugin_log('Clone attempt detected', 'warning');
        _doing_it_wrong( __FUNCTION__, 'Cloning is forbidden.', '1.0.0' );
    }

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup() {
        viewpoints_plugin_log('Unserialize attempt detected', 'warning');
        _doing_it_wrong( __FUNCTION__, 'Unserializing instances of this class is forbidden.', '1.0.0' );
    }

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    private function __construct() {
        viewpoints_plugin_log('Plugin constructor started');
        $this->setup_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        viewpoints_plugin_log('Plugin constructor completed');
    }

    /**
     * Setup plugin dependencies.
     *
     * @since    1.0.0
     * @access   private
     */
	private function setup_dependencies() {
		viewpoints_plugin_log('Setting up dependencies');

		// Initialize components
		$this->post_type = new Viewpoints_Post_Type();
		viewpoints_plugin_log('Post Type component initialized');

		// Use the ACF Manager instead of Field Groups
		$this->field_groups = Viewpoints_ACF_Manager::get_instance();
		viewpoints_plugin_log('ACF Manager component initialized');

		$this->shortcode = new Viewpoints_Shortcode();
		viewpoints_plugin_log('Shortcode component initialized');
	}

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        viewpoints_plugin_log('Defining admin hooks');

        // Admin hooks
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        viewpoints_plugin_log('Defining public hooks');

        // Public hooks
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_styles' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_scripts' ) );
    }

	/**
	 * Run the plugin functionalities.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		viewpoints_plugin_log('Plugin initialization started');

		// Initialize various components
		viewpoints_plugin_log('Registering post type');
		$this->post_type->register();

		viewpoints_plugin_log('Registering field groups');
		$this->field_groups->register();

		viewpoints_plugin_log('Registering shortcode');
		$this->shortcode->register();

		viewpoints_plugin_log('Plugin initialization completed');
	}

    /**
     * Register the admin stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_admin_styles() {
        // Only load on plugin admin pages
        $screen = get_current_screen();
        if ( ! isset( $screen->post_type ) || 'viewpoints' !== $screen->post_type ) {
            return;
        }

        viewpoints_plugin_log('Enqueuing admin styles for screen: ' . $screen->id);

        $css_file = VIEWPOINTS_PLUGIN_ASSETS_DIR . 'css/viewpoints-admin.css';
        $version = file_exists($css_file) ? filemtime($css_file) : VIEWPOINTS_PLUGIN_VERSION;

        viewpoints_plugin_log('Admin CSS file version: ' . $version . ' (Path: ' . $css_file . ')');

        wp_enqueue_style(
            'viewpoints-admin',
            VIEWPOINTS_PLUGIN_ASSETS_URL . 'css/viewpoints-admin.css',
            array(),
            $version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_admin_scripts() {
        // Only load on plugin admin pages
        $screen = get_current_screen();
        if ( ! isset( $screen->post_type ) || 'viewpoints' !== $screen->post_type ) {
            return;
        }

        viewpoints_plugin_log('Enqueuing admin scripts for screen: ' . $screen->id);

        $js_file = VIEWPOINTS_PLUGIN_ASSETS_DIR . 'js/viewpoints-admin.js';
        $version = file_exists($js_file) ? filemtime($js_file) : VIEWPOINTS_PLUGIN_VERSION;

        viewpoints_plugin_log('Admin JS file version: ' . $version . ' (Path: ' . $js_file . ')');

        wp_enqueue_script(
            'viewpoints-admin',
            VIEWPOINTS_PLUGIN_ASSETS_URL . 'js/viewpoints-admin.js',
            array( 'jquery' ),
            $version,
            false
        );
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_public_styles() {
        // Only enqueue on single viewpoint or when shortcode is used
        if ( is_singular( 'viewpoints' ) || $this->shortcode->is_shortcode_used() ) {
            viewpoints_plugin_log('Enqueuing public styles for viewpoint content');

            $css_file = VIEWPOINTS_PLUGIN_ASSETS_DIR . 'css/viewpoints-public.css';
            $version = file_exists($css_file) ? filemtime($css_file) : VIEWPOINTS_PLUGIN_VERSION;

            viewpoints_plugin_log('Public CSS file version: ' . $version . ' (Path: ' . $css_file . ')');

            wp_enqueue_style(
                'viewpoints-public',
                VIEWPOINTS_PLUGIN_ASSETS_URL . 'css/viewpoints-public.css',
                array(),
                $version,
                'all'
            );
        }
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_public_scripts() {
        // Only enqueue on single viewpoint or when shortcode is used
        if ( is_singular( 'viewpoints' ) || $this->shortcode->is_shortcode_used() ) {
            viewpoints_plugin_log('Enqueuing public scripts for viewpoint content');

            $js_file = VIEWPOINTS_PLUGIN_ASSETS_DIR . 'js/viewpoints-public.js';
            $version = file_exists($js_file) ? filemtime($js_file) : VIEWPOINTS_PLUGIN_VERSION;

            viewpoints_plugin_log('Public JS file version: ' . $version . ' (Path: ' . $js_file . ')');

            wp_enqueue_script(
                'viewpoints-public',
                VIEWPOINTS_PLUGIN_ASSETS_URL . 'js/viewpoints-public.js',
                array( 'jquery' ),
                $version,
                false
            );
        }
    }
}
