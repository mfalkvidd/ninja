<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Session library.
 *
 * $Id: Session.php 3917 2009-01-21 03:06:22Z zombor $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Session {

	// Session singleton
	private static $instance;

	/**
	 * Singleton instance of Session.
	 */
	public static function instance()
	{
		if (self::$instance == NULL)
		{
			// Create a new instance
			self::$instance = new static();
		}

		return self::$instance;
	}

	/**
	 * On first session instance creation,
	 */
	public function __construct()
	{
		$this->create();
	}

	public function csrf_token_valid($token)
	{
		return $token == $this->get(Kohana::config('csrf.csrf_token'), false);
	}
	/**
	 * Get the session id.
	 *
	 * @return  string
	 */
	public function id()
	{
		if(PHP_SAPI == 'cli') {
			return "";
		}
		return session_id();
	}

	/**
	 * Create a new session.
	 *
	 * @param   array  variables to set after creation
	 * @return  void
	 */
	public function create($vars = NULL)
	{
		if(PHP_SAPI == 'cli') {
			return;
		}
		// Destroy any current sessions
		$this->destroy();

		// Name the session, this will also be the name of the cookie
		session_name(Kohana::config('session.name'));

		// Set the session cookie parameters
		session_set_cookie_params
		(
			Kohana::config('cookie.expire'),
			Kohana::config('cookie.path'),
			Kohana::config('cookie.domain'),
			Kohana::config('cookie.secure'),
			Kohana::config('cookie.httponly')
		);

		// Start the session!
		session_start();

		// Set the new data
		$this->set($vars);
	}

	/**
	 * Destroys the current session.
	 *
	 * @return  void
	 */
	public function destroy()
	{
		if(PHP_SAPI == 'cli') {
			return;
		}
		if (session_id() !== '')
		{
			$name = session_name();
			session_destroy();
			$_SESSION = array();
			cookie::delete($name);
		}
	}

	/**
	 * Runs the system.session_write event, then calls session_write_close.
	 *
	 * @return  void
	 */
	public function write_close()
	{
		if(PHP_SAPI == 'cli') {
			return;
		}
		session_write_close();
	}

	/**
	 * Set a session variable.
	 *
	 * @param   string|array  key, or array of values
	 * @param   mixed         value (if keys is not an array)
	 * @return  void
	 */
	public function set($keys, $val = FALSE)
	{
		if(PHP_SAPI == 'cli') {
			return;
		}
		if (empty($keys))
			return FALSE;

		if ( ! is_array($keys))
		{
			$keys = array($keys => $val);
		}

		foreach ($keys as $key => $val)
		{
			$_SESSION[$key] = $val;
		}
	}

	/**
	 * Get a variable. Access to sub-arrays is supported with key.subkey.
	 *
	 * @param   string  variable key
	 * @param   mixed   default value returned if variable does not exist
	 * @return  mixed   Variable data if key specified, otherwise array containing all session data.
	 */
	public function get($key = FALSE, $default = FALSE)
	{
		if(PHP_SAPI == 'cli') {
			return $default;
		}
		if (empty($key))
			return $_SESSION;

		$result = isset($_SESSION[$key]) ? $_SESSION[$key] : Kohana::key_string($_SESSION, $key);

		return ($result === NULL) ? $default : $result;
	}

	/**
	 * Delete one or more variables.
	 *
	 * @param   string  variable key(s)
	 * @return  void
	 */
	public function delete($keys)
	{
		if(PHP_SAPI == 'cli') {
			return;
		}
		$args = func_get_args();

		foreach ($args as $key)
		{
			unset($_SESSION[$key]);
		}
	}
}
