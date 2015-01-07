<?php


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
	
	public $use_custom_settings;	// 	
	protected $settings;			// Registered settings.

	
	/*
	Default constructor.
	Sets up the values for the admin page.
	@param  $name        [string]  The name/slug of the page.
	@param  $page_title  [string]  The title shown on the top of the page.
	@param  $menu_title  [string]  The title shown on the left menu.
	@param  $capability  [string]  The capability needed to displayed to the user.
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

	
	/*
	Adds the admin page to the main menu and sets up all values, actions and filters.
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
		
        add_action( "load-$hook", array( $this, 'add_screen_options' ) );

		if( $this->handler->current_page === $this->name )
		{
			$this->is_current_page = true;
			
			if( (!$this->handler->current_tab) ||
				(!in_array($this->handler->current_tab, array_keys($this->tab_names))) )
				$this->handler->current_tab = $this->get_default_tab();
		}
		else
		{
			return;
		}
		
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
		
		add_action( $this->name.'-register-settings', array($this, 'register_settings') );
		add_action( $this->name.'-add-settings-sections', array($this, 'add_settings_sections') );
		add_action( $this->name.'-add-settings-fields', array($this, 'add_settings_fields') );
			
		add_filter( $this->name.'-process-input', array($this, 'process_settings'), 99, 2 );
		add_action( 'admin_init', array($this, 'process_page') );
		
		foreach( $this->tabs as $tab )
		{
			if( $tab instanceof APL_TabAdminPage ) { $tab->setup(); }
		}
	}
	
	
	/*
	Sets the page's handler.
	@param  $handler  [APL_Handler]  The handler controlling the page.
	*/
	public function set_handler( $handler )
	{
		$this->handler = $handler;
		foreach( $this->tabs as $tab ) { $tab->set_handler( $handler ); }
	}	
	

	/**
	 * Sets the page's parent menu.
	 * @param  APL_AdminMenu  $menu  The parent menu of the page.
	 */
	public function set_menu( $menu )
	{
		$this->menu = $menu;
		foreach( $this->tabs as $tab ) { $tab->set_menu( $menu ); }
	}	

	
	/*
	Adds a tab to the page's tab list.  The tab can be an APL_TabAdminPage or APL_TabLink.
	@param  $tab  [APL_TabAdminPage|APL_TabLink]  The tab class object.
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
	
	
	/*
	Determines the default tab to be chosen when the tab isn't specified.
	@return  [string]  The name of the tab. 
	*/
	public function get_default_tab()
	{
		$keys = array_keys($this->tab_names);
		if( count($keys) > 0 ) return $keys[0];
		return null;
	}
	
	
	/*
	Displays the tab list links.
	*/
	public function display_tab_list()
	{
		if( count($this->tabs) === 0 ) return;
		
		?><h2 class="admin-page nav-tab-wrapper"><?php

 		foreach( $this->tabs as $tab )
 		{
			$tab->display_tab( $this->current_tab );
 		}
		
		?></h2><?php
	}
	
	
	/*
	Enqueues all the scripts or styles needed for the admin page. 
	*/
	public function enqueue_scripts() { }
	
	
	/*
	HTML/JavaScript to add to the <head> portion of the page. 
	*/
	public function add_head_script() { }
	
	
	/*
	Register each individual settings for the Settings API.
	*/
	public function register_settings() { }
	
	
	/*
	Registers the settings key for the Settings API. The settings key is associated with 
	an option key.  A filter is added for the option key, associated with process_settings
	function, which should be overwritten by child classes.
	@param  $key  [string]  The key for the data in the $_POST array, as well as the key
	                        for the option in the options table.
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
	
	
	/*
	Add the sections used for the Settings API. 
	*/
	public function add_settings_sections() { }
	
	
	/*
	Add the settings used for the Settings API. 
	*/
	public function add_settings_fields() { }
	
	
	/*
	
	*/
	public function add_section( $name, $title, $callback )
	{
		add_settings_section(
			$name, $title, array( $this, $callback ), $this->name.':'.$name
		);
	}
	

	/*
	
	*/
	public function add_field( $section_name, $name, $title, $callback, $args = array() )
	{
		add_settings_field( 
			$name, $title, array( $this, $callback ), $this->name.':'.$section_name, $section_name, $args
		);
	}
	
	
	/*
	Add the admin page's screen options.  
	*/
	public function add_screen_options() { }
	
	
	/*
	Processes the current admin page / tab.
	*/
	public function process_page()
	{
		if( empty($_POST) ) return;
		
		if( !isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], $this->name.'-options') )
		{
			// TODO: error... The submitted data cannot be verified.
			return;
		}

		if( ($this->use_custom_settings) && (isset($_POST['action'])) && ($_POST['action'] == 'update') )
		{
			foreach( $this->settings as $setting )
			{
				if( !isset($_POST[$settings]) ) continue;
				
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
	
	
	/*
	Displays the current admin page / tab. 
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
		 		$index = $this->tab_names[$this->handler->current_tab];
		 		$this->tabs[$index]->display();
		 	else:
		 		$this->display();
		 	endif;
		 	?>
		 	
		 	</div><!-- .page-contents -->
		 
		</div><!-- .wrap -->
		<?php
	}
	
	
	/*
	Processes the current admin page.  Only called when tab is not specified. 
	*/
	public function process() { }
	
	
	/*
	Processes the current admin page's Settings API input.
	@param  $settings  [array]   The inputted settings from the Settings API.
	@param  $option    [string]  The option key of the settings input array.
	@return            [array]   The resulted array to store in the db.
	*/
	public function process_settings( $settings, $option )
	{
		return $settings;
	}


	/*
	Displays the current admin page.  Only called when tab is not specified. 
	*/
	abstract public function display();
	
	
	/*
	
	*/
	public function get_form_url( $use_settings_api = true )
	{
		if( $use_settings_api && !$this->use_custom_settings && !is_network_admin() )
		{
			return "options.php";
		}

		return $this->helper->get_page_url();
	}
	
	
	/*
	*/
	public function form_start( $use_settings_api = true )
	{
		?>

		<form action="<?php echo $this->get_form_url( $use_settings_api ); ?>" method="POST">
		<?php settings_fields( $this->name ); ?>
		
		<?php
	}
	
	
	/*
	*/
	public function form_end()
	{
		?>
		</form>
		<?php
	}
	
	
	public function print_section( $section_name )
	{
		do_settings_sections( $this->name.':'.$section_name );
	}


} // class APL_AdminPage
endif; // if( !class_exists('APL_AdminPage') ):

