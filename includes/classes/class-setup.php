<?php
/**
 * Setup class for the Invisible Post Status plugin.
 *
 * @package InvisiblePostStatus
 * @since   0.1.0
 */

if ( ! class_exists( 'Invisible_Post_Status_Setup' ) ) {
	/**
     *
	 *
	 * @since 0.1.0
	 */
	class Invisible_Post_Status_Setup {

		/**
		 * Single instance of the class.
		 *
		 * @since 0.1.0
		 * @var Invisible_Post_Status_Setup|null
		 */
		private static ?Invisible_Post_Status_Setup $instance = null;

		/**
		 * Private constructor to prevent direct instantiation.
		 *
		 * @since 0.1.0
		 */
		private function __construct() {
			$this->init_hooks();
		}

		/**
		 * Get the singleton instance.
		 *
		 * @since 0.1.0
		 * @return Invisible_Post_Status_Setup The singleton instance.
		 */
		public static function get_instance(): Invisible_Post_Status_Setup {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Initialize WordPress hooks.
		 *
		 * @since 0.1.0
		 * @return void
		 */
		private function init_hooks(): void {
            add_action( 'init', array( $this, 'register_invisible_status' ) );
            add_action( 'pre_get_posts', array( $this, 'allow_invisible_singular_query' ), 9 ); // Runs on 9 to be before the subsite query manipulation happens.
            add_filter( 'display_post_states', array( $this, 'display_invisible_post_state' ), 10, 2 );
            add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_status_script' ) );
            add_action( 'admin_footer-edit.php', array( $this, 'add_invisible_to_quick_edit' ) );
        }

        /**
         * Register the custom "invisible" post status.
         *
         * @see https://developer.wordpress.org/reference/functions/register_post_status/
         *
         * @since 0.1.0
         * @return void
         */
        public function register_invisible_status() :void {
            register_post_status(
                'invisible',
                array(
                    'label'                     => _x( 'Invisible', 'post status', 'textdomain' ),
                    'public'                    => false, // Excludes from blog index, archives, feeds.
                    'publicly_queryable'        => true,  // Allows singular URL requests.
                    'exclude_from_search'       => true,  // Excludes from site search.
                    'show_in_admin_all_list'    => true,  // Shows under "All" in admin table.
                    'show_in_admin_status_list' => true,  // Shows status filter link at top of table.
                    /* translators: %s: number of invisible posts */
                    'label_count'               => _n_noop(
                        'Invisible <span class="count">(%s)</span>',
                        'Invisible <span class="count">(%s)</span>',
                        'textdomain'
                    ),
                )
            );
        }

        /**
         * Allow single posts with status "invisible" to be loaded by URL.
         *
         * @see https://developer.wordpress.org/reference/hooks/pre_get_posts/
         *
         * @param  WP_Query $query The WordPress Query class (passed by reference).
         * @return void
         */
        public function allow_invisible_singular_query( WP_Query $query ) :void {
            global $pagenow, $typenow;

            $allow_invisible   = false;
            $parent_post_type  = 'gatherpress_play';
            $subsite_post_type = 'gatherpress_play_sub';

            if ( ! $query->is_main_query() ) {
                return;
            }

            $post_status = (array) $query->get( 'post_status' );
            
            if (
                // Only apply to main frontend singular queries for 'gatherpress_play_sub'.
                ! is_admin() && $query->is_singular() && $subsite_post_type === $query->get( 'post_type' )
            ) {
                $allow_invisible = true;
                $post_status = ( empty( $post_status ) || empty( $post_status[0] ) ) ? array( 'publish' ) : $post_status;
                $post_status = ( is_user_logged_in() ) ? array_merge( $post_status, array( 'private' ) ) : $post_status;
            } elseif (
                // Or, apply to main admin queries for the admin list tables.
                is_admin() && $pagenow === 'edit.php' && ( empty( $post_status ) || empty( $post_status[0] ) ) &&
                // Apply to main admin queries for 'gatherpress_play' or 'gatherpress_play_sub' in the admin list table.
                in_array( $typenow, array( $parent_post_type, $subsite_post_type ), true )
            ) {
                $allow_invisible = true;
                $post_status = ( empty( $post_status ) || empty( $post_status[0] ) ) ? array( 'publish' ) : $post_status;
                $post_status = array_merge( $post_status, array( 'private', 'protected', 'draft', 'pending' ) );
            }

            if ( $allow_invisible === true ) {
                $query->set( 'post_status', array_merge( $post_status, array( 'invisible' ) ) );
            }
        }


        /**
         * Display "Invisible" badge in the admin post list table.
         *
         * @see https://developer.wordpress.org/reference/hooks/display_post_states/
         *
         * @param  array<string, string> $post_states An array of post states for the current post.
         * @param  WP_Post                $post        The current post object.
         * @return array<string, string> Modified array of post states.
         */
        public function display_invisible_post_state( array $post_states, WP_Post $post ) :array {
            // skip if we're already filtering by this status
            if( 'invisible' === get_query_var( 'post_status' ) ) {
                return $post_states;
            }

            if ( 'gatherpress_play_sub' === $post->post_type && 'invisible' === $post->post_status ) {
                $post_states['invisible'] = esc_html__( 'Invisible', 'textdomain' );
            }

            return $post_states;
        }

        /**
         * Add "Invisible" dropdown option in Block Editor (Gutenberg) Status panel.
         *
         * @return void
         */
        public function enqueue_status_script() :void {
            $screen = get_current_screen();
            if ( ! $screen || 'gatherpress_play_sub' !== $screen->post_type ) {
                return;
            }

            $script = "
            ( function( wp ) {
                var registerPlugin = wp.plugins.registerPlugin;
                var PluginPostStatusInfo = wp.editPost.PluginPostStatusInfo;
                var SelectControl = wp.components.SelectControl;
                var useSelect = wp.data.useSelect;
                var useDispatch = wp.data.useDispatch;
                var el = wp.element.createElement;

                function InvisibleStatusSelect() {
                    var currentPostType = useSelect( function( select ) {
                        return select( 'core/editor' ).getCurrentPostType();
                    } );

                    var status = useSelect( function( select ) {
                        return select( 'core/editor' ).getEditedPostAttribute( 'status' );
                    } );

                    var editPost = useDispatch( 'core/editor' ).editPost;

                    if ( currentPostType !== 'gatherpress_play_sub' ) {
                        return null;
                    }

                    return el(
                        PluginPostStatusInfo,
                        {},
                        el( SelectControl, {
                            label: 'Custom Status',
                            value: status,
                            options: [
                                { label: 'Published (Standard)', value: 'publish' },
                                { label: 'Invisible (Direct URL only)', value: 'invisible' },
                                { label: 'Draft', value: 'draft' },
                                { label: 'Pending Review', value: 'pending' },
                                { label: 'Private', value: 'private' }
                            ],
                            onChange: function( newStatus ) {
                                editPost( { status: newStatus } );
                            }
                        } )
                    );
                }

                registerPlugin( 'my-cpt-invisible-status-plugin', {
                    render: InvisibleStatusSelect
                } );
            } )( window.wp );
            ";

            wp_add_inline_script( 'wp-edit-post', $script );
        }

        /**
         * Add "Invisible" option to Quick Edit post statuses (and Classic Editor).
         *
         * @return void
         */
        public function add_invisible_to_quick_edit() :void {
            global $current_screen;
            if ( ! $current_screen || 'gatherpress_play_sub' !== $current_screen->post_type ) {
                return;
            }
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('select[name="_status"]').append('<option value="invisible"><?php echo esc_js( __( 'Invisible', 'textdomain' ) ); ?></option>');
            });
            </script>
            <?php
        }
	}
}
