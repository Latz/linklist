<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'LinkListAdmin' ) ) {

	class LinkListAdmin {

		public const PROLOG_LABEL   = 'Content to put in front of list';
		public const MINLINKS_LABEL = 'Minimum links';
		public const MINLINKS_DESC  = 'Minimum number of links to display LinkList';

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
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only check of which admin page is loaded, to decide what to enqueue; no form data is being processed, so no nonce applies.
			if ( isset( $_GET['page'] ) && $_GET['page'] == $this->hook ) {
				wp_enqueue_script( 'postbox' );
				wp_enqueue_script( 'dashboard' );
				wp_enqueue_script( 'thickbox' );
				wp_enqueue_script( 'media-upload' );
			}
		}

		public function config_page_styles() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only check of which admin page is loaded, to decide what to enqueue; no form data is being processed, so no nonce applies.
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

		/**
		 * Build a form_table() row containing a single checkbox.
		 */
		private function checkbox_row( $id, $label, $desc, $checkbox_label, $option_key, $options ) {
			return array(
				'id'      => $id,
				'label'   => $label,
				'desc'    => $desc,
				'content' => $this->checkbox( $checkbox_label, $option_key, $options ),
			);
		}

		/**
		 * Build a form_table() row containing a single text input.
		 */
		private function text_row( $id, $label, $desc, $name, $value ) {
			return array(
				'id'      => $id,
				'label'   => $label,
				'desc'    => $desc,
				'content' => '<input type="text" name="' . $name . '" class="regular-text" value="' . esc_attr( $value ) . '">',
			);
		}

		/**
		 * Build the "style of list" form_table() row (radio buttons + separator field) shared by posts/pages/feed.
		 */
		private function style_row( $id, $prefix, $options ) {
			return array(
				'id'      => $id,
				'label'   => 'Style of list',
				'desc'    => '',
				'content' => $this->radiobutton( $prefix . '_style', 'rbol', __( 'ordered list', 'linklist' ), $options ) . "<br/>\n" .
							 $this->radiobutton( $prefix . '_style', 'rbul', __( 'unordered list', 'linklist' ), $options ) . "<br/>\n" .
							 $this->radiobutton( $prefix . '_style', 'rbli', __( 'char separated', 'linklist' ), $options ) .
							 '&nbsp;<input type="text" size="10" name="' . $prefix . '_sep" value="' . esc_attr( $options[ $prefix . '_sep' ] ) . '">',
			);
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
			<div style="display: flex; gap: 20px; align-items: start;">
			<div class="postbox-container" style="width: 70%;">
				<div class="metabox-holder">
					<div class="meta-box-sortables">
						<form action="" method="post" id="linklist-conf">
						<?php
						if ( function_exists('wp_nonce_field') ) {
							wp_nonce_field('linklist-config');
						}



						// ----------------------------------------------------------------------
						$rows = array();

						$rows[] = $this->checkbox_row( "post_active", "Display linklist in posts", "", __( 'Display LinkList in posts', 'linklist' ), 'post_active', $options );
						$rows[] = $this->checkbox_row( "page_active", "Display linklist in pages", "", __( 'Display LinkList in pages', 'linklist' ), 'page_active', $options );
						$rows[] = $this->checkbox_row( "feed_active", "Display linklist in feed", "", __( 'Display LinkList in feed', 'linklist' ), 'feed_active', $options );

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
						$rows[] = $this->text_row( "post_prolog", self::PROLOG_LABEL, "", "post_prolog", $options['post_prolog'] );
						$rows[] = $this->style_row( "style_type", "post", $options );
						$rows[] = $this->text_row( "post_minlinks", self::MINLINKS_LABEL, self::MINLINKS_DESC, "post_minlinks", $options['post_minlinks'] );
						$rows[] = $this->checkbox_row( "post_sortlinks", "Sorting", "", __( 'Sort links alphabetically', 'linklist' ), 'post_sort', $options );
						$rows[] = $this->checkbox_row( "post_more", "More tag", '', __( "Don't display if &lt;--more--&gt; tag is present", 'linklist' ), 'post_more', $options );
						$rows[] = $this->checkbox_row( "post_display", "Single post", "", __( 'Display only if single post is displayed (not on main blog page)', 'linklist' ), 'post_display', $options );
						$rows[] = $this->checkbox_row( "post_last", "Last page only", "", __( 'Display only on last page if post is splitted', 'linklist' ), 'post_last', $options );

						$this->postbox('linklist_posts','Posts settings',$this->form_table($rows));

						// ----------------------------------------------------------------------
						$rows = array();
						$rows[] = $this->text_row( "page_prolog", self::PROLOG_LABEL, "", "page_prolog", $options['page_prolog'] );
						$rows[] = $this->style_row( "page_style", "page", $options );
						$rows[] = $this->text_row( "page_minlinks", self::MINLINKS_LABEL, self::MINLINKS_DESC, "page_minlinks", $options['page_minlinks'] );
						$rows[] = $this->checkbox_row( "page_sortlinks", "Sorting", "", __( 'Sort links alphabetically', 'linklist' ), 'page_sort', $options );
						$rows[] = $this->checkbox_row( "page_last", "Last page only", "", __( 'Display only on last page if post is splitted', 'linklist' ), 'page_last', $options );

						$this->postbox('linklist_pages','Pages settings',$this->form_table($rows));

						// ----------------------------------------------------------------------
						$rows = array();
						$rows[] = $this->text_row( "feed_prolog", self::PROLOG_LABEL, "", "feed_prolog", $options['feed_prolog'] );
						$rows[] = $this->style_row( "style_type", "feed", $options );
						$rows[] = $this->text_row( "feed_minlinks", self::MINLINKS_LABEL, self::MINLINKS_DESC, "feed_minlinks", $options['feed_minlinks'] );
						$rows[] = $this->checkbox_row( "feed_sortlinks", "Sorting", "", __( 'Sort links alphabetically', 'linklist' ), 'feed_sort', $options );

						$this->postbox('linklist_feed','Feed settings',$this->form_table($rows));

                        ?>

						<div class="submit">
							<input type="submit" class="button-primary" name="submit" value="Update LinkList Settings &raquo;" />
						</div>
						</form>
					</div>
				</div>
			</div>

			<div class="postbox-container" style="width: 20%;">
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


		</div>
<?php		}
	} //class
	$linklist_admin = new LinkListAdmin();
} //if
