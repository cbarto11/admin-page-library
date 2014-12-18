<?php
	

if( !class_exists('APL_Helper') ):
class APL_Helper
{

	protected $handler;			// 
	

	/*
	Default Constructor.
	Initializes the handler properties. 
	*/
	public function __construct( $handler )
	{
		$this->handler = $handler;
	}
	
	
	/*
	
	*/
	public function get_page_url()
	{
		$page_url = 'http';
		if( isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on') ) $page_url .= 's';
		$page_url .= '://';
		if( $_SERVER['SERVER_PORT'] != '80' )
			$page_url .= $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'];
		else
			$page_url .= $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
		return $page_url;
	}
	
} // class APL_Helper
endif; // if( !class_exists('APL_Helper') ):

