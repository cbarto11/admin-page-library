<?php
/**
 * APL_AdminPage
 * 
 * The APL_AdminPage class is a representation of a admin page in WordPress that will 
 * appear in the main admin menu.
 * 
 * @package    apl
 * @author     Crystal Barton <cbarto11@uncc.edu>
 */

if( !class_exists('APL_AdminPage') ):
abstract class APL_AdminPage
{
	
	protected $handler;			// The handler that controls the admin page.
	protected $menu;			// The parent admin menu's name (eg. "themes.php")
								// or APL_AdminMenu object.
	
	public $name;				// The name/slug of the page.
	public $page_title;			// The title of the page.
	public $menu_title; 		// The title show on the left menu.
	public $capability;			// The capability needed to displayed to the user.
	
	public $is_main_page;		// True if this page is the main menu page.
	public $is_current_page;	// True if this is current page being shown.
	
	protected $tabs;			// The page's tabs, if any.
	protected $tab_names;		// The names of the tabs with their index within the
								//   the $tabs array (for searching purposes).
	
	public $use_custom_settings;// True if use the apl's custom "Settings API".
	protected $settings;		// All settings that have been registered.

	
	/**
	 * Creates an APL_AdminPage object.
	 * @param  string  $name        The name/slug of the page.
	 * @param  string  $page_title  The title shown on the top of the page.
	 * @param  string  $menu_title  The title shown on the left menu.
	 * @param  string  $capability  The capability needed to displayed to the user.
	 */
	public function __construct( $name, $page_title, $menu_title = null, $capability = 'administrator' )
	{
		$this->handler = null;
		$this->menu = null;
		
		$this->name = $name;
		$this->page_title = $page_title;
		$this->menu_title = ( $menu_title !== null ? $menu_title : $page_title );
		$this->capability = $capability;
		
		$this->is_main_page = false;
		$this->is_current_page = false;
		$this->current_tab = null;
		
		$this->tabs = array();
		$this->tab_names = array();
		
		$this->use_custom_settings = false;
		$this->settings = array();
	}

	
	/**
	 * Adds the admin page to the main menu and sets up all values, actions and filters.
	 */
	public function setup()
	{
		$menu_name = $this->menu;
		if( $this->menu instanceof APL_AdminMenu ) $menu_name = $this->menu->name;
		
		if( $this->is_main_page )
		{
			$hook = add_menu_page(
				$this->page_title, 
				$this->menu_title,
				$this->capability,
				$this->name,
				array( $this, 'display_page' )
			);
		}
		else
		{
			$hook = add_submenu_page(
				$menu_name,
				$this->page_title,
				$this->menu_title,
				$this->capability,
				$this->name,
				array( $this, 'display_page' )
			);
		}
		
		if( $this->handler->current_page !== $this ) return;
		
		$this->is_current_page = true;
		
        add_action( "load-$hook", array( $this, 'add_screen_options' ) );

		global $pagenow;
		switch( $pagenow )
		{
			case 'options.php':
				break;
			
			default:
				add_action( 'admin_enqueue_scripts', array($this, 'enqueue_scripts') );
				add_action( 'admin_head', array($this, 'add_head_script') );
				break;
		}
		
		add_action( $this->get_name().'-register-settings', array($this, 'register_settings') );
		add_action( $this->get_name().'-add-settings-sections', array($this, 'add_settings_sections') );
		add_action( $this->get_name().'-add-settings-fields', array($this, 'add_settings_fields') );
			
		add_filter( $this->get_name().'-process-input', array($this, 'process_settings'), 99, 2 );
		add_action( 'admin_init', array($this, 'process_page') );
		
		foreach( $this->tabs as $tab )
		{
			if( $tab instanceof APL_TabAdminPage ) { $tab->setup(); }
		}
	}
	
	
	/**
	 * Checks if this is the page that should perform an apl ajax request.  If the current
	 * page matches the request and a tab is not selected, then the ajax request is processed.
	 */
	public function perform_ajax_request()
	{
		$output = array( 'status' => true, 'message' => '' );
		$this->ajax_request( $_POST['apl-ajax-action'], $_POST['input'], $output );
		echo json_encode($output);
		exit;
	}
	
	
	/**
	 * Sets the page's handler.
	 * @param  APL_Handler  $handler  The handler controlling the page.
	 */
	public function set_handler( $handler )
	{
		$this->handler = $handler;
		foreach( $this->tabs as $tab )
		{
			if( $tab instanceof APL_TabAdminPage ) { $tab->set_handler( $handler ); }
		}
	}	
	

	/**
	 * Sets the page's parent menu.
	 * @param  APL_AdminMenu  $menu  The parent menu of the page.
	 */
	public function set_menu( $menu )
	{
		$this->menu = $menu;
		foreach( $this->tabs as $tab )
		{
			if( $tab instanceof APL_TabAdminPage ) { $tab->set_menu( $menu ); }
		}
	}	

	
	/**
	 * Adds a tab to the page's tab list.  The tab can be an APL_TabAdminPage or APL_TabLink.
	 * @param  APL_TabAdminPage|APL_TabLink  $tab  The tab class object.
	 */
	public function add_tab( $tab )
	{
		if( $tab instanceof APL_TabAdminPage )
		{
			$tab->set_handler( $this->handler );
			$tab->set_menu( $this->menu );
			$tab->set_page( $this );
			$this->tab_names[$tab->name] = count($this->tabs);
		}
		$this->tabs[] = $tab;
	}
	
	
	/**
	 * Determines the default tab to be chosen when the tab isn't specified.
	 * @return  string  The name of the tab. 
	 */
	public function get_default_tab()
	{
		$keys = array_keys($this->tab_names);
		if( count($keys) > 0 ) 
		{
			return $this->tabs[$this->tab_names[$keys[0]]];
		}
		return null;
	}
	
	
	/**
	 * Displays the tab list links.
	 */
	public function display_tab_list()
	{
		if( count($this->tabs) === 0 ) return;
		
		?><h2 class="admin-page nav-tab-wrapper"><?php

 		foreach( $this->tabs as $tab )
 		{
			$tab->display_tab();
 		}
		
		?></h2><?php
	}
	
	
	/**
	 * Retreives that APL_TabAdminPage object that matches the name.
	 * @param   string  $name  The name of the tab.
	 * @return  APL_TabAdminPage|null  The APL_TabAdminPage object if exists, otherwise null.
	 */
	public function get_tab_by_name( $name )
	{
		if( in_array($name, array_keys($this->tab_names)) )
		{
			return $this->tabs[$this->tab_names[$name]];
		}
		return null;
	}
	
	
	/**
	 * Enqueues all the scripts or styles needed for the admin page. 
	 */
	public function enqueue_scripts() { }
	
	
	/**
	 * HTML/JavaScript to add to the <head> portion of the page. 
	 */
	public function add_head_script() { }
	
	
	/**
	 * Register each individual settings for the Settings API.
	 */
	public function register_settings() { }
	
	
	/**
	 * Registers the settings key for the Settings API. The settings key is associated
	 * with an option key.  A filter is added for the option key, associated with
	 * process_settings function, which should be overwritten by child classes.
	 * @param  string  $key  The key for the data in the $_POST array, as well as the key
	 *                       for the option in the options table.
	 */
	public function register_setting( $key )
	{
		if( !$this->use_custom_settings )
		{
			register_setting( $this->name, $key );
		}

		add_filter( 'sanitize_option_'.$key, array($this, 'process_settings'), 10, 2 );
		$this->settings[] = $key;
	}
	
	
	/**
	 * Add the sections used for the Settings API. 
	 */
	public function add_settings_sections() { }
	
	
	/**
	 * Add the settings used for the Settings API. 
	 */
	public function add_settings_fields() { }
	
	
	/**
	 * Adds a "Settings API" section.
	 * @param  string  $name      The name/slug of the section.
	 * @param  string  $title     The title to display for the the section.
	 * @param  string  $callback  The function to call when displaying the section.
	 */
	public function add_section( $name, $title, $callback )
	{
		add_settings_section(
			$name, $title, array( $this, $callback ), $this->name.':'.$name
		);
	}
	

	/**
	 * Adds a "Settings API" field.
	 * @param  string  $section   The name/slug of the section.
	 * @param  string  $name      The name/slug of the field.
	 * @param  string  $title     The title to display for the the field.
	 * @param  string  $callback  The function to call when displaying the section.
	 * @param  array   $args      The arguments to pass to the callback function.
	 */
	public function add_field( $section, $name, $title, $callback, $args = array() )
	{
		add_settings_field( 
			$name, $title, array( $this, $callback ), $this->name.':'.$section, $section, $args
		);
	}
	
	
	/**
	 * Add the admin page's screen options.  
	 */
	public function add_screen_options() { }
	
	
	/**
	 * Processes the current admin page or tab by checking the nonce, updating settings,
	 * and running the process function (which should be overloaded by child class).
	 */
	public function process_page()
	{
		if( empty($_POST) ) return;
		
		if( !isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], $this->get_name().'-options') )
		{
			// TODO: error... The submitted data cannot be verified.
			return;
		}

		if( ($this->use_custom_settings) && (isset($_POST['action'])) && ($_POST['action'] == 'update') )
		{
			foreach( $this->settings as $setting )
			{
				if( !isset($_POST[$setting]) ) continue;
				
				if( is_network_admin() )
				{
					update_site_option( $setting, $_POST[$setting] );
				}
				else
				{
					update_option( $setting, $_POST[$setting] );
				}
			}
		}
		
		$this->process();
	}
	
	
	/**
	 * Displays the current admin page / tab. 
	 */
	public function display_page()
	{
		?>
		<div class="wrap">
	 
			<div id="icon-themes" class="icon32"></div>
			<h2><?php echo $this->page_title; ?></h2>
			<?php settings_errors(); ?>
		 
		 	<?php 
		 	if( ($this->menu) && ($this->menu instanceof APL_AdminMenu) )
		 	{
		 		$this->menu->display_tab_list();
		 	}
		 	$this->display_tab_list();
		 	?>
		 	
		 	<div class="page-contents">
		 	
		 	<?php
		 	
		 	if( $this->handler->current_tab ):
		 		$this->handler->current_tab->display();
		 	else:
		 		$this->display();
		 	endif;
		 	?>
		 	
		 	</div><!-- .page-contents -->
		 
		</div><!-- .wrap -->
		<?php
	}
	
	
	/**
	 * Processes the current admin page.  Only called when tab is not specified. 
	 */
	public function process() { }
	
	
	/**
	 * Processes the current admin page's Settings API input.
	 * @param   array   $settings  The inputted settings from the Settings API.
	 * @param   string  $option    The option key of the settings input array.
	 * @return  array   The resulted array to store in the db.
	 */
	public function process_settings( $settings, $option )
	{
		return $settings;
	}


	/**
	 * Displays the current admin page.  Only called when tab is not specified. 
	 */
	abstract public function display();
	
	
	/**
	 * Processes and displays the output of an ajax request.
	 */
	public function ajax_request( $action, $input, &$output ) { }

	
	/**
	 * Gets the URL to use as the action when constructing a form.
	 * @param   bool  $use_settings_api  True if the Settings API's saving mechinism will
	 *                                   be used.  Does not work with network admin pages.
	 * @return  string  The constructed form url.
	 */
	public function get_form_url( $use_settings_api = true )
	{
		if( $use_settings_api && !$this->use_custom_settings && !is_network_admin() )
		{
			return 'options.php';
		}

		return apl_get_page_url();
	}
	
	
	/**
	 * Displays the start form tag and mandatory fields when constructing a form using apl.
	 * @param  string  $class             The class to give the form.
	 * @param  bool    $use_settings_api  True if the Settings API's saving mechinism will be
	 *                                    used.  Does not work with network admin pages.
	 * @param  array   $attributes  Additional attributes to add to the form tag.
	 */
	public function form_start( $class, $use_settings_api = true, $attributes = array() )
	{
		?>

		<form action="<?php echo $this->get_form_url( $use_settings_api ); ?>" 
		      method="POST" 
		      class="<?php echo $class; ?>"
		      <?php
		      foreach( $attributes as $key => $value ):
		      	echo $key.'="'.$value.'" ';
		      endforeach;
		      ?>>
		<?php settings_fields( $this->name ); ?>
		
		<?php
	}
	
	
	/**
	 * Displays the end form tag.
	 */
	public function form_end()
	{
		?>
		</form>
		<?php
	}
	
	
	/**
	 * Displays a "Settings API" section that were added in "add_settings_sections".
	 * @param  string  $section_name  The name/slug of the section.
	 */
	public function print_section( $section_name )
	{
		do_settings_sections( $this->name.':'.$section_name );
	}
	
	
	/**
	 * Displays a button with the needed attributes to be used by the AplAjaxButton
	 * jQuery plugin for automated AJAX processing. 
	 * @param  string       $text          The text to display on the button.
	 * @param  string       $action        The action to send in the AJAX request.
	 * @param  array|null   $form_classes  The classes of the forms that should be 
	 *                                     processed via AJAX individually.  If no form
	 *                                     is given, then current form is assumed.
	 * @param  array|null   $input_names   The names of the form input values to process
	 *                                     via AJAX together.  If no input values are
	 *                                     given, then the entire form will be processed.
	 * @param  string|null  $callback_start       The JS function to call when processing 
	 *                                            begins, before the first loop.
	 * @param  string|null  $callback_end         The JS function to call when processing
	 *                                            finishes, after the last loop.
	 * @param  string|null  $callback_loop_start  The JS function to call when each form
	 *                                            begins processing.
	 * @param  string|null  $callback_loop_end    The JS function to call when each formatOutput
	 *                                            finishes processing.
	 */
	public function create_ajax_submit_button( $text, $action, $form_classes, $input_names, $callback_start = null, $callback_end = null, $callback_loop_start = null, $callback_loop_end = null )
	{
		if( is_array($form_classes) ) $form_classes = implode( ',', $form_classes );
		if( is_array($input_names) ) $input_names = implode( ',', $input_names );
		$nonce = wp_create_nonce( $this->handler->get_full_page_name().'-'.$action.'-ajax-request' );
		
		?>
		<button type="button" 
		        class="apl-ajax-button"
		        page="<?php echo $this->handler->get_page_name(); ?>"
		        tab="<?php echo $this->handler->get_tab_name(); ?>"
		        action="<?php echo $action; ?>"
		        form="<?php echo $form_classes; ?>"
		        input="<?php echo $input_names; ?>"
		        cb_start="<?php echo $callback_start; ?>"
		        cb_end="<?php echo $callback_end; ?>"
		        cb_loop_start="<?php echo $callback_loop_start; ?>"
		        cb_loop_end="<?php echo $callback_loop_end; ?>"
		        nonce="<?php echo $nonce; ?>">
		    <?php echo $text; ?>
		</button>
		<?php
	}
	
	
	/**
	 * Gets the name of the admin page.
	 * @return  string  The name of the admin page.
	 */
	public function get_name()
	{
		return $this->name;
	}


} // class APL_AdminPage
endif; // if( !class_exists('APL_AdminPage') ):

