<?php
require_once( dirname(__FILE__).'/functions.php' );
require_once( dirname(__FILE__).'/admin-menu.php' );
require_once( dirname(__FILE__).'/admin-page.php' );
require_once( dirname(__FILE__).'/tab-admin-page.php' );
require_once( dirname(__FILE__).'/tab-link.php' );


/**
 * APL_Handler
 * 
 * The APL_Handler class is the main class which controls or "handles" the admin menus
 * and pages created.
 * 
 * @package    apl
 * @author     Crystal Barton <cbarto11@uncc.edu>
 */

if( !class_exists('APL_Handler') ):
class APL_Handler
{
	protected $menus;			// A collection of menus with associated admin pages.
	protected $pages;			// Single main admin pages or admin pages that are
	                            // children of an existing page (ex. "themes.php").
	
	public $current_page;		// The APL_AdminPage object of the current page.
	public $current_tab;		// The APL_TabAdminPage object of the current tab.
	
	public $current_page;		// 
	public $current_tab;		// 
	
	public $disable_redirect;   // 
	

	/**
	 * Creates an APL_Handler object.
	 * @param  bool  $is_network_admin  True if only show pages on the network admin menu,
	 *                                  otherwise False to only show on a site's admin menu.
	 */
	public function __construct( $is_network_admin = false )
	{
		$this->menus = array();
		$this->pages = array();
		
		$this->current_page = null;
		$this->current_tab = null;
		
		$this->disable_redirect = false;
		$this->use_settings_api = true;
		
		add_action( 'admin_enqueue_scripts', array($this, 'enqueue_scripts') );
		add_action( 'wp_ajax_apl-ajax-action', array($this, 'perform_ajax_request') );
	}
	

	/**
	 * Add a menu to the main admin menu.
	 * @param  APL_AdminMenu  $menu  An admin menu to be displayed in the main admin menu.
	 */
	public function add_menu( $menu )
	{
		$menu->set_handler( $this );
		$this->menus[] = $menu;
	}
	

	/**
	 * Add a page to the main admin menu.
	 * @param  APL_AdminPage  $page    Admin page to be displayed in the main admin menu.
	 * @param  string         $parent  The parent page's name/slug.
	*/
	public function add_page( $page, $parent = null )
	{
		if( $parent === null )
		{
			$parent = $page->name;
			$page->is_main_page = true;
			$this->pages[$parent] = array();
		}
		elseif( !in_array($parent, array_keys($this->pages)) )
		{
			$this->pages[$parent] = array();
		}
		
		$page->set_handler( $this );
		$page->set_menu( $parent );
		$this->pages[$parent][] = $page;
	}
	
	/**
	 * Sets up all the admin menus and pages.
	 */
	public function setup()
	{
		$this->set_current_page();

		add_action( 'admin_init', array($this, 'register_settings') );
		
		foreach( $this->menus as $menu )
		{
			$menu->setup();
		}
		
		foreach( $this->pages as $page )
		{
			$page->setup();
		}
		
		if( !$this->disable_redirect )
		{
			add_action( 'admin_init', array($this, 'possible_redirect') );
		}
	}
	

	/**
	 * Determines the current page and tab being shown.
	 */
	protected function set_current_page()
	{
		global $pagenow;

		switch( $pagenow )
		{
			case 'options.php':
				$this->current_page = ( !empty($_POST['option_page']) ? $_POST['option_page'] : null );
				$this->disable_redirect = true;
				break;
			
			case 'admin.php':
			default:
				$this->current_page = ( !empty($_GET['page']) ? $_GET['page'] : null );
				break;
		}
		
		$this->current_tab = ( isset($_GET['tab']) ? $_GET['tab'] : null );
	}
	

	/**
	 * Retrieves the page that name/slug matches the page name.
	 * @param   string  $page_name   The name/slug of the page.
	 * @return  APL_AdminPage|false  The APL_AdminPage object that matches the page name,
	 *                               otherwise False.
	 */
	protected function get_page( $page_name )
	{
// 		apl_print( $this->current_page.'-register-settings', 'do_action' );
		
		do_action( $this->current_page.'-register-settings' );
		do_action( $this->current_page.'-add-settings-sections' );
		do_action( $this->current_page.'-add-settings-fields' );
	}
	

	/**
	 * Registers the settings, sections, fields, and processing for the settings API.
	 */
	public function register_settings()
	{
// 		$query_vars[] = 'connections-spoke-api';
		return $query_vars;
	}
	
	
	{
	}
	
		
	/**
	 * Determines if the page has POST data and needs to redirect to a "clean" page.
	 * If a redirect is deemed necessary, then page to redirect to is determine and
	 * redirected to.
	 */
	public function possible_redirect()
	{
		if( (empty($_POST)) || ($this->disable_redirect) ) return;
		
		unset($_POST);
		
 		wp_redirect( $redirect_url );
		exit;
	}
	
} // class APL_Handler
endif; // if( !class_exists('APL_Handler') ):

