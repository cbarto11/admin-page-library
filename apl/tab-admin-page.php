<?php


if( !class_exists('APL_TabAdminPage') ):
abstract class APL_TabAdminPage extends APL_AdminPage
{

	protected $page;			// The parent admin page that contains the tab.
	protected $is_current_tab;	// True if this is current tab being shown.
	

	/*
	Default Constructor.
	Initatializes the default values for the tab-admin page.
	@param  $name        [string]         The name/slug of the tab.
	@param  $title       [string]         The title of the tab.
	@param  $page        [APL_AdminPage]  The parent admin page that contains the tab.
	@param  $capability  [string]         The capability needed to displayed to the user.
	*/
	public function __construct( $name, $title, $page, $capability = 'administrator' )
	{
		parent::__construct( $name, $title, $title, $capability );
		$this->page = $page;
	}
	
	
	/*
	Sets up the current tab-admin page.
	*/
	public function setup()
	{
		if( $this->handler->current_tab === $this->name )
		{
			$this->is_current_tab = true;
		}
		else
		{
			return;
		}

		add_filter(
			$this->page->name.'-'.$this->name.'-process-input',
			array($this, 'process_settings'),
			99, 2
		);
		add_action( 'admin_init', array($this, 'process_page') );
	}
	

	/*
	Sets the tab's parent admin page.
	@param  $page  [APL_AdminPage]  The parent page the contains the tab.
	*/
	public function set_page( $page )
	{
		$this->page = $page;
	}	


	/*
	Displays the tab link for the this tab.
	*/
	public function display_tab()
	{
		?>
		
		<a 
		 href="?page=<?php echo $this->page->name; ?>&tab=<?php echo $this->name; ?>"
		 class="nav-tab <?php if( $this->name == $this->handler->current_tab ) echo 'active'; ?>">
			<?php echo $this->page_title; ?>
		</a>
		
		<?php
	}

} // class APL_TabAdminPage
endif; // if( !class_exists('APL_TabAdminPage') ):

