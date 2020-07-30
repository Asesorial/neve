<?php
/**
 * Page settings metabox.
 *
 * @package Neve
 */

namespace Neve\Admin\Metabox;

use Neve\Core\Settings\Config;
use Neve\Core\Settings\Mods;

/**
 * Class Manager
 *
 * @package Neve\Admin\Metabox
 */
final class Manager {

	/**
	 * Control instances.
	 *
	 * @var array
	 */
	private $controls = array();

	/**
	 * Controls for meta sidebar in Block Editor.
	 *
	 * @var array
	 */
	private $meta_sidebar_controls = array();

	/**
	 * Control classes to get controls from.
	 *
	 * @var array
	 */
	private $control_classes;

	/**
	 * Init function
	 */
	public function init() {
		add_action( 'add_meta_boxes', array( $this, 'add' ) );
		add_action( 'init', array( $this, 'define_controls' ) );
		add_action( 'init', array( $this, 'load_controls' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'save_post', array( $this, 'save' ) );

		/**
		 * Gtb meta
		 */
		add_action( 'init', array( $this, 'register_meta_sidebar' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'meta_sidebar_script_enqueue' ) );
		add_action( 'init', array( $this, 'neve_register_meta' ), 11 );
	}

	/**
	 * Define the controls.
	 */
	public function define_controls() {
		$this->control_classes = array(
			'Neve\\Admin\\Metabox\\Main',
		);

		$this->control_classes = apply_filters( 'neve_filter_metabox_controls', $this->control_classes );
	}

	/**
	 * Instantiate the controls and actually load them into the control manager.
	 *
	 * @param bool  $is_sidebar Gutenberg sidebar flag.
	 * @param array $control_classes Controls classes.
	 */
	public function load_controls( $is_sidebar = false, $control_classes = array() ) {
		if ( empty( $control_classes ) ) {
			$control_classes = $this->control_classes;
		}
		if ( empty( $control_classes ) ) {
			return;
		}
		foreach ( $control_classes as $control_manager ) {
			$control_instance = new $control_manager();
			if ( ! $control_instance instanceof Controls_Base ) {
				continue;
			}

			$control_instance->init();

			if ( $is_sidebar ) {
				$this->meta_sidebar_controls = array_merge( $this->meta_sidebar_controls, $control_instance->get_controls() );
			} else {
				$this->controls = array_merge( $this->controls, $control_instance->get_controls() );
			}
		}
		$this->order_by_priority();
	}

	/**
	 * The metabox content.
	 */
	public function render_controls() {
		global $post;

		foreach ( $this->controls as $control ) {
			if ( method_exists( $control, 'render' ) ) {
				$control->render( $post->ID );
			}
		}
	}

	/**
	 * Save metabox content.
	 *
	 * @param int $post_id the post id.
	 */
	public function save( $post_id ) {
		foreach ( $this->controls as $control ) {
			if ( method_exists( $control, 'save' ) ) {
				$control->save( $post_id );
			}
		}
	}

	/**
	 * Register meta box to control layout on pages and posts.
	 */
	public function add() {
		if ( $this->should_add_meta() === false ) {
			return;
		}

		$post_type         = 'Neve';
		$post_type_from_db = get_post_type();
		if ( $post_type_from_db ) {
			$post_type = ucfirst( $post_type_from_db );
		}

		add_meta_box(
			'neve-page-settings',
			sprintf(
			/* translators: %s - post type */
				__( '%s Settings', 'neve' ),
				$post_type
			),
			array( $this, 'render_metabox' ),
			array( 'post', 'page', 'product' ),
			'side',
			'default',
			array(
				'__back_compat_meta_box' => true,
			)
		);

		if ( $this->is_gutenberg_active() ) {
			add_meta_box(
				'neve-page-settings-notice',
				sprintf(
				/* translators: %s - post type */
					__( '%s Settings', 'neve' ),
					$post_type
				),
				array( $this, 'render_metabox_notice' ),
				array( 'post', 'page' ),
				'side',
				'default',
				array(
					'__back_compat_meta_box' => false,
				)
			);
		}
	}

	/**
	 * Detect if is gutenberg editor.
	 *
	 * @return bool
	 */
	private  function is_gutenberg_active() {
		return get_current_screen()->is_block_editor();
	}

	/**
	 * The metabox content.
	 */
	public function render_metabox() {
		$this->render_controls();
	}

	/**
	 * Render the metabox notice.
	 */
	public function render_metabox_notice() {
		?>
		<script type="text/javascript">
			function focusSidebar() {
				var selector = document.querySelector('.components-button.has-icon[aria-label^="Neve"]');
				if ( ! selector ){
					selector = document.querySelector('.edit-post-more-menu button');
				}
				selector.focus();
			}
		</script>
		<?php
		echo '<div class="nv-meta-notice-wrapper">';
		echo '<h4>' . esc_html__( 'Page Settings are now accessible from the top bar', 'neve' ) . '</h4>';
		printf(
			/* translators: %1$s - Keyboard shortcut.   %2&s - svg icon */
			esc_html__( 'Click the %1$s icon in the top bar or use the keyboard shortcut ( %2$s ) to customise the layout settings for this page', 'neve' ),
			'<svg width="17" height="24" viewBox="0 0 17 24" fill="none" xmlns="http://www.w3.org/2000/svg" onclick="focusSidebar()">
				<path d="M4.77822 10.2133V19.3287H0.118347V0.802224C0.118347 0.712594 0.145598 0.649854 0.200099 0.614002C0.254601 0.578149 0.354519 0.622964 0.499857 0.748446L12.1359 10.2133V1.04422H16.7958V19.5976C16.7958 19.7051 16.7685 19.7724 16.714 19.7992C16.6595 19.8261 16.5596 19.7768 16.4143 19.6514L4.77822 10.2133Z"/>
				<rect x="0.118347" y="22.3334" width="16.6774" height="1.51613"/>
				</svg>',
			'<strong>CTRL + ALT + N</strong> '. __( 'or', 'neve') . ' <strong>control + option + N</strong>'
		);
		echo '</div>';
	}

	/**
	 * Decide if the metabox should be visible.
	 *
	 * @return bool
	 */
	public function should_add_meta() {
		global $post;

		if ( empty( $post ) ) {
			return false;
		}

		$restricted_pages_id = array();
		if ( in_array( $post->ID, $restricted_pages_id, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public function enqueue() {

		$screen = get_current_screen();

		if ( ! is_object( $screen ) ) {
			return;
		}
		if ( $screen->base !== 'post' ) {
			return;
		}

		wp_register_script( 'neve-metabox', NEVE_ASSETS_URL . 'js/build/all/metabox.js', array( 'jquery' ), NEVE_VERSION, true );

		wp_localize_script( 'neve-metabox', 'neveMetabox', $this->get_localization() );

		wp_enqueue_script( 'neve-metabox' );
	}

	/**
	 * Localize the Metabox script.
	 *
	 * @return array
	 */
	private function get_localization() {
		return array();
	}

	/**
	 * Order the controls by given priority.
	 */
	private function order_by_priority() {
		$order = array();

		foreach ( $this->controls as $key => $control_object ) {
			$order[ $key ] = $control_object->priority;
		}
		array_multisort( $order, SORT_ASC, $this->controls );
	}


	/**
	 * Register the metabox sidebar in Gutenberg editor
	 */
	public function register_meta_sidebar() {

		$control_classes = array(
			'Neve\\Admin\\Metabox\\Block_Editor_Meta',
		);
		$this->load_controls( true, $control_classes );

		wp_register_script(
			'neve-meta-sidebar',
			trailingslashit( get_template_directory_uri() ) . 'inc/admin/metabox/build/index.js',
			array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-keyboard-shortcuts' )
		);

	}

	/**
	 * Register meta
	 */
	public function neve_register_meta() {
		foreach ( $this->meta_sidebar_controls as $control ) {
			$options = get_object_vars( $control );
			$type    = 'string';
			if ( $options['type'] === 'range' ) {
				$type = 'integer';
			}

			$post_type = '';
			if ( array_key_exists( 'post_type', $options ) ) {
				$post_type = $options['post_type'];
			}
			register_post_meta(
				$post_type,
				$options['id'],
				array(
					'show_in_rest'      => true,
					'type'              => $type,
					'single'            => true,
					'sanitize_callback' => 'sanitize_text_field',
					'auth_callback'     => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}

	/**
	 * Register the metabox sidebar.
	 */
	public function meta_sidebar_script_enqueue() {
		wp_enqueue_script( 'neve-meta-sidebar' );

		global $post_type;
		$container        = $post_type === 'post' ? Mods::get( Config::MODS_SINGLE_POST_CONTAINER_STYLE, 'contained' ) : Mods::get( Config::MODS_DEFAULT_CONTAINER_STYLE, 'contained' );
		$editor_width     = Mods::get( Config::MODS_CONTAINER_WIDTH );
		$editor_width     = isset( $editor_width['desktop'] ) ? (int) $editor_width['desktop'] : 1170;
		$component_groups = apply_filters(
			'neve_meta_component_groups',
			[
				'group_page_layout' => [
					'title'    => __( 'Page Layout', 'neve' ),
					'controls' => [ 'neve_meta_sidebar', 'neve_meta_container', 'neve_meta_enable_content_width', 'neve_meta_content_width' ],
				],
				'group_page_title'  => [
					'title'    => __( 'Page Title', 'neve' ),
					'controls' => [ 'neve_meta_header_elements_order', 'neve_meta_title_alignment', 'neve_meta_author_avatar' ],
				],
				'group_elements'    => [
					'title'    => __( 'Elements', 'neve' ),
					'controls' => [ 'neve_meta_disable_header', 'neve_meta_disable_footer', 'neve_meta_disable_title', 'neve_meta_comments', 'neve_meta_tags' ],
				],
			]
		);
		wp_localize_script(
			'neve-meta-sidebar',
			'metaSidebar',
			array(
				'component_groups' => $component_groups,
				'controls'         => $this->meta_sidebar_controls,
				'actions'          => array(
					'neve_meta_content_width' => array(
						'container' => $container,
						'editor'    => $editor_width,
					),
				),
			)
		);

		wp_enqueue_style(
			'neve-meta-sidebar-css', // Handle.
			trailingslashit( get_template_directory_uri() ) . 'inc/admin/metabox/build/editor.css',
			array( 'wp-edit-blocks' )
		);

	}
}
