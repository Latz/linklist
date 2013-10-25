<?php
if ( ! class_exists( 'LinkList_Admin' ) ) {

	require_once('yst_plugin_tools.php');

	class LinkList_Admin extends Yoast_Plugin_Admin {

		var $hook 		= 'linklist';
		var $longname	= 'LinkList Configuration';
		var $shortname	= 'LinkList';
		var $filename	= 'linklist/linklist.php';
		var $ozhicon	= 'feed_edit.png';

	function radiobutton($name, $value, $text, $options) {
	      return '<label><input type="radio" name="' . $name . '" id="' . $value . '" value="' . $value . '"' .
		 ($options[$name] == $value ? ' checked' : '') .  '>&nbsp;' . __($text, 'linklist') . '</label>';
		}
    function checkbox($text, $var, $options) {
	    return '<label id="lbl_' . $var . '"><input type="checkbox" id="cb_' . $var . '" name="' . $var . '"' .
        ($options [$var] ? "checked" : '') . '>&nbsp;' . __($text, 'linklist') . "</label><br/>\n";
		}

	function config_page() {

		$options = array('post_active',	  'page_active',   'feed_active',
						 'post_prolog',   'page_prolog',   'feed_prolog',
						 'post_style',    'page_style',    'feed_style',
						 'post_display',  'page_display',
						 'post_more',     'page_more',
						 'post_minlinks', 'page_minlinks', 'feed_minlinks',
						 'post_exclude',  'page_exclude',  'feed_exclude',
						 'post_sep',      'page_sep',      'feed_sep',
						 'post_sort',     'page_sort',     'feed_sort',
						 'post_last',     'page_last'
						);

		if ( isset($_POST['submit']) ) {
			if (!current_user_can('manage_options')) die(__('You cannot edit the LinkList options.'));
			check_admin_referer('linklist-config');

		   foreach($options as $option)
			$ll_options[$option] = (isset  ($_POST [$option])) ? addslashes ( $_POST [$option] ) : '';
			   update_option('linklist', $ll_options);
		}

		$options  = get_option('linklist');
		//var_dump($options);

		?>
		<div class="wrap">
			<h2>LinkList options</h2>
			<div class="postbox-container" style="width:70%;">
				<div class="metabox-holder">
					<div class="meta-box-sortables">
						<form action="" method="post" id="linklist-conf">
						<?php
						if ( function_exists('wp_nonce_field') )
							wp_nonce_field('linklist-config');



						// ----------------------------------------------------------------------
						$rows = array();

						$rows[] = array(
							"id" => "post_active",
							"label" => "Display linklist in posts",
							"desc" => "",
							"content" => $this->checkbox('Display LinkList in posts', 'post_active', $options)
						);

						$rows[] = array(
							"id" => "page_active",
							"label" => "Display linklist in pages",
							"desc" => "",
							"content" => $this->checkbox('Display LinkList in pages', 'page_active', $options)
						);

						$rows[] = array(
							"id" => "feed_active",
							"label" => "Display linklist in feed",
							"desc" => "",
							"content" => $this->checkbox('Display LinkList in feed', 'feed_active', $options)
						);


						$this->postbox('linklist_general','General settings',$this->form_table($rows));

						// ----------------------------------------------------------------------
						$rows = array();
						$rows[] = array(
							"id" => "post_prolog",
							"label" => "Content to put in front of list",
							"desc" => "",
							"content" => '<input type="text" name="post_prolog" class="regular-text" value="' .
										  stripslashes(htmlentities($options['post_prolog'])). '">'
						);

						$rows[] = array(
							"id" => "style_type",
							"label" => "Style of list",
							"desc" => "",
							"content" => $this->radiobutton('post_style', 'rbol', 'ordered list', $options) . "<br/>\n" .
										 $this->radiobutton('post_style', 'rbul', 'unordered list', $options) . "<br/>\n" .
										 $this->radiobutton('post_style', 'rbli', 'char separated', $options) .
										 '&nbsp;<input type="text" size="10" name="post_sep" value="' .
										  stripslashes(htmlentities($options['post_sep'])). '">'


						);

						$rows[] = array(
							"id" => "post_minlinks",
							"label" => "Minimum links",
							"desc" => "Minimum number of links to display LinkList",
							"content" => '<input type="text" name="post_minlinks" class="regular-text" value="' .
										  stripslashes(htmlentities($options['post_minlinks'])). '">'
						);

						$rows[] = array(
							"id" => "post_sortlinks",
							"label" => "Sorting",
							"desc" => "",
							"content" => $this->checkbox('Sort links alphabetically', 'post_sort', $options)
						);

						$rows[] = array(
							"id" => "post_more",
							"label" => "More tag",
							"desc" => '',
							"content" => $this->checkbox("Don't display if &lt;--more--&gt; tag is present", 'post_more', $options)
						);

						$rows[] = array(
							"id" => "post_display",
							"label" => "Single post",
							"desc" => "",
							"content" => $this->checkbox('Display only if single post is displayed (not on main blog page)', 'post_display', $options)
						);

						$rows[] = array(
							"id" => "post_last",
							"label" => "Last page only",
							"desc" => "",
							"content" => $this->checkbox('Display only on last page if post is splitted', 'post_last', $options)
						);

						$this->postbox('linklist_posts','Posts settings',$this->form_table($rows));

						// ----------------------------------------------------------------------
						$rows = array();
						$rows[] = array(
							"id" => "page_prolog",
							"label" => "Content to put in front of list",
							"desc" => "",
							"content" => '<input type="text" name="page_prolog" class="regular-text" value="' .
										  stripslashes(htmlentities($options['page_prolog'])). '">'
						);

						$rows[] = array(
							"id" => "page_style",
							"label" => "Style of list",
							"desc" => "",
							"content" => $this->radiobutton('page_style', 'rbol', 'ordered list', $options) . "<br/>\n" .
										 $this->radiobutton('page_style', 'rbul', 'unordered list', $options) . "<br/>\n" .
										 $this->radiobutton('page_style', 'rbli', 'char separated', $options) .
										 '&nbsp;<input type="text" size="10" name="page_sep" value="' .
										  stripslashes(htmlentities($options['page_sep'])). '">'



						);

						$rows[] = array(
							"id" => "page_minlinks",
							"label" => "Minimum links",
							"desc" => "Minimum number of links to display LinkList",
							"content" => '<input type="text" name="page_minlinks" class="regular-text" value="' .
										  stripslashes(htmlentities($options['page_minlinks'])). '">'
						);

						$rows[] = array(
							"id" => "page_sortlinks",
							"label" => "Sorting",
							"desc" => "",
							"content" => $this->checkbox('Sort links alphabetically', 'page_sort', $options)
						);

						$rows[] = array(
							"id" => "page_last",
							"label" => "Last page only",
							"desc" => "",
							"content" => $this->checkbox('Display only on last page if post is splitted', 'page_last', $options)
						);

						$this->postbox('linklist_pages','Pages settings',$this->form_table($rows));



						// ----------------------------------------------------------------------
						$rows = array();
						$rows[] = array(
							"id" => "feed_prolog",
							"label" => "Content to put in front of list",
							"desc" => "",
							"content" => '<input type="text" name="feed_prolog" class="regular-text" value="' .
										  stripslashes(htmlentities($options['feed_prolog'])). '">'
						);

						$rows[] = array(
							"id" => "style_type",
							"label" => "Style of list",
							"desc" => "",
							"content" => $this->radiobutton('feed_style', 'rbol', 'ordered list', $options) . "<br/>\n" .
										 $this->radiobutton('feed_style', 'rbul', 'unordered list', $options) . "<br/>\n" .
										 $this->radiobutton('feed_style', 'rbli', 'char separated', $options) .
										 '&nbsp;<input type="text" size="10" name="feed_sep" value="' .
										  stripslashes(htmlentities($options['feed_sep'])). '">'


						);

						$rows[] = array(
							"id" => "feed_minlinks",
							"label" => "Minimum links",
							"desc" => "Minimum number of links to display LinkList",
							"content" => '<input type="text" name="feed_minlinks" class="regular-text" value="' .
										  stripslashes(htmlentities($options['feed_minlinks'])). '">'
						);

						$rows[] = array(
							"id" => "feed_sortlinks",
							"label" => "Sorting",
							"desc" => "",
							"content" => $this->checkbox('Sort links alphabetically', 'feed_sort', $options)
						);


						$this->postbox('linklist_feed','Feed settings',$this->form_table($rows)); ?>

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
							//$this->plugin_like();
							$this->plugin_support();
							$this->news();
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
