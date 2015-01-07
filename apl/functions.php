<?php


/**
 * Print the content of a variable with a label as a "title".  The entire contents is 
 * enclosed in a <pre> block.
 * @param  mixed        $var    The variable to "dumped"/printed to screen.
 * @param  string|null  $label  The label/title of the variable information.
 */
if( !function_exists('apl_print') ):
function apl_print( $var, $label = null )
{
	echo '<pre>';
	if( $label !== null ) echo "<strong>$label:</strong><br/>";
	var_dump($var);
	echo '</pre>';
}
endif;


/**
 * Prints the name of an input field.
 * @param  {args}  The keys of the input name.  For example:
 *                 apl_name_e( 'a', 'b', 'c' ) will echo "a[b][c]"
 *                 apl_name_e( array( 'a', 'b', 'c' ) ) will echo "a[b][c]"
 */
if( !function_exists('apl_name_e') ):
function apl_name_e()
{
	echo apl_name( func_get_args() );
}
endif;


/**
 * Constructs the name of an input field.
 * @param   array|{args}  The keys of the input name.  For example:
 *                         apl_name( 'a', 'b', 'c' ) will return "a[b][c]"
 *                         apl_name( array( 'a', 'b', 'c' ) ) will return "a[b][c]"
 * @return  string        The constructed input name. 
 */
if( !function_exists('apl_name') ):
function apl_name()
{
	$args = func_get_args();
	if( count($args) == 1 && is_array($args[0]) ) $args = $args[0];
	
	$name = '';
	
	foreach( $args as $arg )
	{
		if( is_array($arg) )
			$name .= apl_name( $arg );
		else
			$name .= "[$arg]";
	}

	return $name;
}
endif;


/**
 * Constructs the current page's complete url.
 * @return  string  The constructed page URL.
 */
if( !function_exists('apl_get_page_url') ):
function apl_get_page_url()
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
endif;

