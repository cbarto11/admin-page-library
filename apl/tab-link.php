<?php
/**
 * The APL_TabLink class is the representation of a tab that link to another page.
 * 
 * @package    apl
 * @author     Crystal Barton <atrus1701@gmail.com>
 */
if( !class_exists('APL_TabLink') ):
class APL_TabLink
{
	/**
	 * The title to display on the tab.
	 * @var  string
	 */
	public $title;

	/**
	 * The link that the tab connects to.
	 * @var  string
	 */
	public $link;
	

	/**
	 * Creates an APL_TabLink object.
	 * @param  string  $title  The title of the tab.
	 * @param  string  $link  The link that the tab connects to.
	 */
	public function __construct( $title, $link )
	{
		$this->title = $title;
		$this->link = $link;
	}
	

	/**
	 * Displays the tab link for the this tab.
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

