<?php
	

require_once( dirname(__FILE__).'/functions.php' );
require_once( dirname(__FILE__).'/helper.php' );
require_once( dirname(__FILE__).'/admin-menu.php' );
require_once( dirname(__FILE__).'/admin-page.php' );
require_once( dirname(__FILE__).'/tab-admin-page.php' );
require_once( dirname(__FILE__).'/tab-link.php' );


if( !class_exists('APL_Handler') ):
class APL_Handler
{
	protected $helper;			// 

	protected $menus;			// 
	protected $pages;			// 
	
	public $current_page;		// 
	public $current_tab;		// 
	
	public $disable_redirect;   // 
	

	/*
	Default Constructor.
	Initializes the handler properties. 
	*/
	public function __construct()
	{
		$this->helper = new APL_Helper( $this );
		
		$this->menus = array();
		$this->pages = array();
		
		$this->current_page = null;
		$this->current_tab = null;
		
		$this->disable_redirect = false;
		$this->use_settings_api = true;
		
		add_filter( 'query_vars', array($this, 'query_vars') );
		add_action( 'parse_request', array($this, 'parse_request') );
	}
	

	/*
	Add a menu to the main admin menu.
	@param  $menu  [APL_AdminMenu]  An admin menu to be displayed in the main admin menu.
	*/
	public function add_menu( $menu )
	{
		$menu->set_handler( $this );
		$menu->set_helper( $this->helper );
		$this->menus[] = $menu;
	}
	

	/*
	Add a page to the main admin menu.
	@param  $page    [APL_AdminPage]  A admin page to be displayed in the main admin menu.
	@param  $parent  [string]         The parent page's name/slug.
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
		$page->set_helper( $this->helper );
		$page->set_menu( $parent );
		$this->pages[$parent][] = $page;
	}
	

	/*
	Sets up all the admin menus and pages.
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
	

	/*
	Determines the current page and tab being shown.
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
	

	/*
	Registers the settings, sections, fields, and processing for the settings API.
	*/
	public function register_settings()
	{
// 		apl_print( $this->current_page.'-register-settings', 'do_action' );
		
		do_action( $this->current_page.'-register-settings' );
		do_action( $this->current_page.'-add-settings-sections' );
		do_action( $this->current_page.'-add-settings-fields' );
	}
	
	
	/*
	Filter the query variables whitelist before processing.
	@param  $query_vars  [array]  The array of whitelisted query variables.
	*/
	public function query_vars( $query_vars )
	{
// 		$query_vars[] = 'connections-spoke-api';
		return $query_vars;
	}
	
	
	/*
	Parse request to find correct WordPress query.
	@param  $wp  [ptr->WP]  WordPress environment setup class.
	*/
	public function parse_request( &$wp )
	{
		global $wp;
// 		if( array_key_exists('connections-spoke-api', $wp->query_vars) )
// 		{
// 			require_once(dirname(__FILE__).'/api.php');
// 			ConnectionsSpoke_Api::init();
// 			ConnectionsSpoke_Api::process_post();
// 			ConnectionsSpoke_Api::output_data();
// 			exit();
// 		}
		return;
	}
	
	
	/*
	
	*/
	public function possible_redirect()
	{
		if( (empty($_POST)) || ($this->disable_redirect) ) return;
		
		unset($_POST);
		
 		wp_redirect( $this->helper->get_page_url() );
		exit;
	}
	
} // class APL_Handler
endif; // if( !class_exists('APL_Handler') ):

