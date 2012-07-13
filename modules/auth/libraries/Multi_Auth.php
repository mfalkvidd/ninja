<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * User authentication and authorization library.
 *
 * @package    Auth
 * @author     
 * @copyright  
 * @license    
 */
class Multi_Auth_Core extends Auth_Core {
	private $drivers = array();

	public function __construct( $config )
	{
		$this->config = $config;
		
		/* Say that we have support for multiple backends */
		$this->backend_supports['multiple_backends'] = true;
		
		$drivers = $config['driver'];
		foreach( $drivers as $drv_class => $drv_name ) {
			$class = $drv_class . '_Auth';
			$this->drivers[$drv_class] = new $class( $config );
		}
	}

	/**
	 * Returns the currently logged in user, or FALSE.
	 *
	 * @return  mixed
	 */
	public function get_user() 
	{
		$driver = Session::instance()->get( 'auth_method' );
		if( !$driver ) {
			return false;
		}
		return $this->drivers[$driver]->get_user();
	}
	
	/**
	 * Attempt to log in a user by using an ORM object and plain-text password.
	 *
	 * @param   string   username to log in
	 * @param   string   password to check against
	 * @param   string   specifies the authentication method, if multiple is avalible, ignore otherwise
	 * @return  user	 user object or FALSE
	 */
	public function login($username, $password, $auth_method = FALSE) {
		if( !isset( $this->drivers[$auth_method] ) ) {
			return false;
		}
		$result = $this->drivers[$auth_method]->login( $username, $password, $auth_method );
		if( $result !== false ) {
			Session::instance()->set( 'auth_method', $auth_method );
			return $result;
		}
		return false;
	}
	
	/**
	 * Attempt to automatically log a user in.
	 *
	 * @return  boolean
	 */
	public function auto_login()
	{
		return false;
	}

	/**
	 * Force a login for a specific username.
	 *
	 * @param   mixed    username
	 * @return  boolean
	 */
	public function force_login($username)
	{
		return false;
	}

	/**
	 * Log out a user by removing the related session variables.
	 *
	 * @param   boolean  completely destroy the session
	 * @return  boolean
	 */
	public function logout($destroy = FALSE) {
		$driver = Session::instance()->get( 'auth_method' );
		if( !$driver ) {
			return false;
		}
		Session::instance()->delete( 'auth_method' );
		return $this->drivers[$driver]->logout( $destroy );
	}

	/**
	 * Returns an array of authentication methods with keys representing the
	 * internal name of the authentication mehtod, and the value is a user
	 * readable name
	 *
	 * @return  array  list of authentication methods, or false if only a single
	 *                 is avalible
	 */
	public function get_authentication_methods() {
		return $this->config['driver'];
	}
	
	
	public function support_for( $task ) {
		if( in_array( $task, $this->supports ) )
			return true;
		foreach( $this->drivers as $driver ) {
			if( $driver->supports( $task ) ) {
				return true;
			}
		}
		return false;
	}
} // End Auth
