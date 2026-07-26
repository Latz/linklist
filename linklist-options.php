<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'LinkList_Admin' ) ) {

	class LinkList_Admin {

		public $hook 		= 'linklist';
		public $longname	= 'LinkList Configuration';
		public $shortname	= 'LinkList';
		public $filename	= 'linklist/linklist.php';

		public function __construct() {
			add_action( 'admin_menu', array( &$this, 'register_settings_page' ) );
			add_filter( 'plugin_action_links', array( &$this, 'add_action_link' ), 10, 2 );

			add_action( 'admin_print_scripts', array( &$this, 'config_page_scripts' ) );
			add_action( 'admin_print_styles', array( &$this, 'config_page_styles' ) );
		}

		public function register_settings_page() {
			add_options_page( $this->longname, $this->shortname, 'manage_options', $this->hook, array( &$this, 'config_page' ) );
		}

		public function plugin_options_url() {
			return admin_url( 'options-general.php?page=' . $this->hook );
		}

		/**
		 * Add a link to the settings page to the plugins list
		 */
		public function add_action_link( $links, $file ) {
			static $this_plugin;
			if ( empty( $this_plugin ) ) {
				$this_plugin = $this->filename;
			}
			if ( $file == $this_plugin ) {
				$settings_link = '<a href="' . $this->plugin_options_url() . '">' . __( 'Settings', 'linklist' ) . '</a>';
				array_unshift( $links, $settings_link );
			}
			return $links;
		}

		public function config_page_scripts() {
			if ( isset( $_GET['page'] ) && $_GET['page'] == $this->hook ) {
				wp_enqueue_script( 'postbox' );
				wp_enqueue_script( 'dashboard' );
				wp_enqueue_script( 'thickbox' );
				wp_enqueue_script( 'media-upload' );
			}
		}

		public function config_page_styles() {
			if ( isset( $_GET['page'] ) && $_GET['page'] == $this->hook ) {
				wp_enqueue_style( 'dashboard' );
				wp_enqueue_style( 'thickbox' );
				wp_enqueue_style( 'global' );
				wp_enqueue_style( 'wp-admin' );
			}
		}

		/**
		 * Create a postbox widget
		 */
		public function postbox( $id, $title, $content ) {
		?>
			<div id="<?php echo esc_attr( $id ); ?>" class="postbox">
				<div class="handlediv" title="Click to toggle"><br /></div>
				<h3 class="hndle"><span><?php echo esc_html( $title ); ?></span></h3>
				<div class="inside">
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $content is pre-built HTML (form_table()/checkbox()/radiobutton() output) from trusted, developer-authored sources, not user input.
					echo $content;
					?>
				</div>
			</div>
		<?php
		}

		/**
		 * Create a form table from an array of rows
		 */
		public function form_table( $rows ) {
			$content = '<table class="form-table">';
			foreach ( $rows as $row ) {
				$content .= '<tr valign="top"><th scrope="row">';
				if ( isset( $row['id'] ) && $row['id'] != '' ) {
					$content .= '<label for="' . $row['id'] . '">' . $row['label'] . ':</label>';
				} else {
					$content .= $row['label'];
				}
				$content .= '</th><td>';
				$content .= $row['content'];
				if ( isset( $row['desc'] ) && $row['desc'] != '' ) {
					$content .= '<br/><small>' . $row['desc'] . '</small>';
				}
				$content .= '</td></tr>';
			}
			$content .= '</table>';
			return $content;
		}

		/**
		 * Info box with link to the support forums.
		 */
		public function plugin_support() {
			$content = '<p>' . __( 'If you have any problems with this plugin or good ideas for improvements or new features, please write an e-mail to <a href="mailto:info@elektroelch.de">info@elektroelch.de</a> or send a tweet to @latz.</p>', 'linklist' );
			$this->postbox( $this->hook . 'support', 'Need support?', $content );
		}

	public function radiobutton($name, $value, $text, $options) {
	      return '<label><input type="radio" name="' . $name . '" id="' . $value . '" value="' . $value . '"' .
		 ($options[$name] == $value ? ' checked' : '') .  '>&nbsp;' . $text . '</label>';
		}
    public function checkbox($text, $var, $options) {
	    return '<label id="lbl_' . $var . '"><input type="checkbox" id="cb_' . $var . '" name="' . $var . '"' .
        ($options [$var] ? "checked" : '') . '>&nbsp;' . $text . "</label><br/>\n";
		}

    public function option_trim($option) {
        return trim($option);
    }

	public function config_page() {

		$options = array('post_active',	  'page_active',   'feed_active',
						 'post_prolog',   'page_prolog',   'feed_prolog',
						 'post_style',    'page_style',    'feed_style',
						 'post_display',  'page_display',
						 'post_more',     'page_more',
						 'post_minlinks', 'page_minlinks', 'feed_minlinks',
						 'post_exclude',  'page_exclude',  'feed_exclude',
						 'post_sep',      'page_sep',      'feed_sep',
						 'post_sort',     'page_sort',     'feed_sort',
						 'post_last',     'page_last',
                         'exceptions', // divs or spans excepted from link harvest
						);

		if ( isset($_POST['submit']) ) {
			if (!current_user_can('manage_options')) {
				wp_die(esc_html__('You cannot edit the LinkList options.', 'linklist'));
			}
			check_admin_referer('linklist-config');


		   foreach($options as $option) {
			$ll_options[$option] = (isset  ($_POST [$option])) ? sanitize_text_field( wp_unslash( $_POST [$option] ) ) : '';
		   }

            // convert string list to array for easier access in main plugin
            if (isset($ll_options['exceptions'])) {
                $ll_options['exceptions'] = array_map( 'trim', explode( ',', $ll_options['exceptions'] ) );
            }
			update_option('linklist', $ll_options);

            if (isset($_POST['priority'])) {
                update_option('linklist_priority', absint($_POST['priority']));
            }

		}
		$options  = get_option('linklist');
        $option_priority = get_option('linklist_priority');
        $options['exceptions'] = implode(', ', $options['exceptions'] ?? array());

		?>
		<div class="wrap">
			<h2>LinkList options</h2>
			<div class="postbox-container" style="width:70%;">
				<div class="metabox-holder">
					<div class="meta-box-sortables">
						<form action="" method="post" id="linklist-conf">
						<?php
						if ( function_exists('wp_nonce_field') ) {
							wp_nonce_field('linklist-config');
						}



						// ----------------------------------------------------------------------
						$rows = array();

						$rows[] = array(
							"id" => "post_active",
							"label" => "Display linklist in posts",
							"desc" => "",
							"content" => $this->checkbox( __( 'Display LinkList in posts', 'linklist' ), 'post_active', $options)
						);

						$rows[] = array(
							"id" => "page_active",
							"label" => "Display linklist in pages",
							"desc" => "",
							"content" => $this->checkbox( __( 'Display LinkList in pages', 'linklist' ), 'page_active', $options)
						);

						$rows[] = array(
							"id" => "feed_active",
							"label" => "Display linklist in feed",
							"desc" => "",
							"content" => $this->checkbox( __( 'Display LinkList in feed', 'linklist' ), 'feed_active', $options)
						);

                        $content = '<input type="text" name="exceptions" class="regular-text"';
                        // Condition necessary for updates

                        if (isset($options['exceptions'])) {
                            $content .= ' value="' . esc_attr( $options['exceptions'] ) . '"';
                        }
                        $content .= '>';

                        $rows[] = array(
                            "id" => "exceptions",
                            "label" => "Exceptions",
                            "desc" => "Divs or spans excepted from link harvesting (comma separated)",
                            "content" => $content
                        );

                        $content = '<input type="text" name="priority" class="regular-text"';
                        // Condition necessary for updates

                        if (isset($option_priority)) {
                            $content .= ' value="' . esc_attr( $option_priority ) . '"';
                        }
                        $content .= '>';

                        $rows[] = array(
                            "id" => "priority",
                            "label" => "Priority",
                            "desc" => "Priority of Linklist (1 = high; 20 = low; default = 10)",
                            "content" => $content
                        );

                        $this->postbox('linklist_general','General settings', $this->form_table($rows));

						// ----------------------------------------------------------------------
						$rows = array();
						$rows[] = array(
							"id" => "post_prolog",
							"label" => "Content to put in front of list",
							"desc" => "",
							"content" => '<input type="text" name="post_prolog" class="regular-text" value="' .
										  esc_attr($options['post_prolog']). '">'
						);

						$rows[] = array(
							"id" => "style_type",
							"label" => "Style of list",
							"desc" => "",
							"content" => $this->radiobutton('post_style', 'rbol', __( 'ordered list', 'linklist' ), $options) . "<br/>\n" .
										 $this->radiobutton('post_style', 'rbul', __( 'unordered list', 'linklist' ), $options) . "<br/>\n" .
										 $this->radiobutton('post_style', 'rbli', __( 'char separated', 'linklist' ), $options) .
										 '&nbsp;<input type="text" size="10" name="post_sep" value="' .
										  esc_attr($options['post_sep']). '">'


						);

						$rows[] = array(
							"id" => "post_minlinks",
							"label" => "Minimum links",
							"desc" => "Minimum number of links to display LinkList",
							"content" => '<input type="text" name="post_minlinks" class="regular-text" value="' .
										  esc_attr($options['post_minlinks']). '">'
						);

						$rows[] = array(
							"id" => "post_sortlinks",
							"label" => "Sorting",
							"desc" => "",
							"content" => $this->checkbox( __( 'Sort links alphabetically', 'linklist' ), 'post_sort', $options)
						);

						$rows[] = array(
							"id" => "post_more",
							"label" => "More tag",
							"desc" => '',
							"content" => $this->checkbox( __( "Don't display if &lt;--more--&gt; tag is present", 'linklist' ), 'post_more', $options)
						);

						$rows[] = array(
							"id" => "post_display",
							"label" => "Single post",
							"desc" => "",
							"content" => $this->checkbox( __( 'Display only if single post is displayed (not on main blog page)', 'linklist' ), 'post_display', $options)
						);

						$rows[] = array(
							"id" => "post_last",
							"label" => "Last page only",
							"desc" => "",
							"content" => $this->checkbox( __( 'Display only on last page if post is splitted', 'linklist' ), 'post_last', $options)
						);

						$this->postbox('linklist_posts','Posts settings',$this->form_table($rows));

						// ----------------------------------------------------------------------
						$rows = array();
						$rows[] = array(
							"id" => "page_prolog",
							"label" => "Content to put in front of list",
							"desc" => "",
							"content" => '<input type="text" name="page_prolog" class="regular-text" value="' .
										  esc_attr($options['page_prolog']). '">'
						);

						$rows[] = array(
							"id" => "page_style",
							"label" => "Style of list",
							"desc" => "",
							"content" => $this->radiobutton('page_style', 'rbol', __( 'ordered list', 'linklist' ), $options) . "<br/>\n" .
										 $this->radiobutton('page_style', 'rbul', __( 'unordered list', 'linklist' ), $options) . "<br/>\n" .
										 $this->radiobutton('page_style', 'rbli', __( 'char separated', 'linklist' ), $options) .
										 '&nbsp;<input type="text" size="10" name="page_sep" value="' .
										  esc_attr($options['page_sep']). '">'
						);

						$rows[] = array(
							"id" => "page_minlinks",
							"label" => "Minimum links",
							"desc" => "Minimum number of links to display LinkList",
							"content" => '<input type="text" name="page_minlinks" class="regular-text" value="' .
										  esc_attr($options['page_minlinks']). '">'
						);

						$rows[] = array(
							"id" => "page_sortlinks",
							"label" => "Sorting",
							"desc" => "",
							"content" => $this->checkbox( __( 'Sort links alphabetically', 'linklist' ), 'page_sort', $options)
						);

						$rows[] = array(
							"id" => "page_last",
							"label" => "Last page only",
							"desc" => "",
							"content" => $this->checkbox( __( 'Display only on last page if post is splitted', 'linklist' ), 'page_last', $options)
						);

						$this->postbox('linklist_pages','Pages settings',$this->form_table($rows));

						// ----------------------------------------------------------------------
						$rows = array();
						$rows[] = array(
							"id" => "feed_prolog",
							"label" => "Content to put in front of list",
							"desc" => "",
							"content" => '<input type="text" name="feed_prolog" class="regular-text" value="' .
										  esc_attr($options['feed_prolog']). '">'
						);

						$rows[] = array(
							"id" => "style_type",
							"label" => "Style of list",
							"desc" => "",
							"content" => $this->radiobutton('feed_style', 'rbol', __( 'ordered list', 'linklist' ), $options) . "<br/>\n" .
										 $this->radiobutton('feed_style', 'rbul', __( 'unordered list', 'linklist' ), $options) . "<br/>\n" .
										 $this->radiobutton('feed_style', 'rbli', __( 'char separated', 'linklist' ), $options) .
										 '&nbsp;<input type="text" size="10" name="feed_sep" value="' .
										  esc_attr($options['feed_sep']). '">'


						);

						$rows[] = array(
							"id" => "feed_minlinks",
							"label" => "Minimum links",
							"desc" => "Minimum number of links to display LinkList",
							"content" => '<input type="text" name="feed_minlinks" class="regular-text" value="' .
										  esc_attr($options['feed_minlinks']). '">'
						);

						$rows[] = array(
							"id" => "feed_sortlinks",
							"label" => "Sorting",
							"desc" => "",
							"content" => $this->checkbox( __( 'Sort links alphabetically', 'linklist' ), 'feed_sort', $options)
						);


						$this->postbox('linklist_feed','Feed settings',$this->form_table($rows));

                        ?>

						<div class="submit">
							<input type="submit" class="button-primary" name="submit" value="Update LinkList Settings &raquo;" />
						</div>
						</form>
					</div>
				</div>
			</div>

			<div class="postbox-container" style="width:20%;">
				<div class="metabox-holder">
					<div class="meta-box-sortables">
						<?php
							$this->plugin_support();
						?>
					</div>
					<br/><br/><br/>
				</div>
			</div>


		</div>
<?php		}
	} //class
	$linklist_admin = new LinkList_Admin();
} //if
