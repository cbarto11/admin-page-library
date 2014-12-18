<?php


if( !class_exists('APL_TabLink') ):
class APL_TabLink
{

	public $title;		// The title to display on the tab.
	public $link;		// The link that the tab connects to.
	

	/*
	Default Constructor.
	Initatializes the default values for the tab-admin page.
	@param  $title  [string]  The title of the tab.
	@param  $link   [string]  The link that the tab connects to.
	*/
	public function __construct( $title, $link )
	{
		$this->title = $title;
		$this->link = $link;
	}
	

	/*
	Displays the tab link for the this tab.
	*/
	public function display_tab()
	{
		?>

		<a href="<?php echo $this->link; ?>" class="nav-tab">
			<?php echo $this->title; ?>
		</a>

		<?php
	}

} // class AP_TabLink
endif; // if( !class_exists('AP_TabLink') ):

