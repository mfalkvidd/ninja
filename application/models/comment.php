<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Handle comments for hosts and services
 */
class Comment_Model extends Model {
	/***************************** COMMENT TYPES *******************************/
	const HOST_COMMENT = 1;
	const SERVICE_COMMENT = 2;

	/****************************** ENTRY TYPES ********************************/
	const USER_COMMENT = 1;
	const DOWNTIME_COMMENT = 2;
	const FLAPPING_COMMENT = 3;
	const ACKNOWLEDGEMENT_COMMENT = 4;

	/**
	*	Fetch saved comments for host or service
	*
	*/
	public function fetch_comments($host=false, $service=false, $num_per_page=false, $offset=false, $count=false)
	{
		$host = trim($host);
		$service = trim($service);
		if (empty($host)) {
			return false;
		}
		$db = new Database();
		$auth = new Nagios_auth_Model();

		$host_query = $auth->authorized_host_query();
		if ($host_query === true) {
			# don't use auth_host fields etc
			$auth_host_alias = 'h';
			$auth_from = ', host AS '.$auth_host_alias;
			$auth_where = ' AND ' . $auth_host_alias . ".host_name = c.host_name";
		} else {
			$auth_host_alias = $host_query['host_field'];
			$auth_from = ' ,'.$host_query['from'];
			$auth_where = ' AND '.sprintf($host_query['where'], "c.host_name");
		}

		$num_per_page = (int)$num_per_page;
		if ($count === false) {
			if (empty($service)) {
				$sql = "SELECT c.* FROM comment c".$auth_from." WHERE c.host_name=".$db->escape($host)." AND".
				" (c.service_description='' OR c.service_description is null) ".$auth_where.
				" ORDER BY c.host_name LIMIT ".$offset.", ".$num_per_page;
			} else {
				$sql = "SELECT c.* FROM comment c".$auth_from." WHERE c.host_name=".$db->escape($host)." AND".
				" c.service_description=".$db->escape($service)." ".$auth_where.
				" ORDER BY c.host_name LIMIT ".$offset.", ".$num_per_page;
			}

			$result = $db->query($sql);
			return $result->count() ? $result: false;
		} else {
			if (empty($service)) {
				$sql = "SELECT COUNT(*) AS cnt FROM comment c".$auth_from." WHERE c.host_name=".$db->escape($host)." AND".
				" (c.service_description='' OR c.service_description is null) ".$auth_where;
			} else {
				$sql = "SELECT COUNT(*) AS cnt FROM comment c".$auth_from." WHERE c.host_name=".$db->escape($host)." AND".
				" c.service_description=".$db->escape($service)." ".$auth_where;
			}
			$result = $db->query($sql);
			return $result->count() ? (int)$result->current()->cnt : 0;
		}
	}

	/**
	*	Wrapper method to fetch nr of comments for host or service
	*/
	public function count_comments($host=false, $service=false)
	{
		return self::fetch_comments($host, $service, false, false, true);
	}

	/**
	*	Fetch all host- or service comments
	*/
	public function fetch_all_comments($host=false, $service=false, $num_per_page=false, $offset=false, $count=false)
	{
		$host = trim($host);
		$service = trim($service);
		$num_per_page = (int)$num_per_page;
		$db = new Database();
		$auth = new Nagios_auth_Model();

		$host_query = $auth->authorized_host_query();
		if ($host_query === true) {
			# don't use auth_host fields etc
			$auth_host_alias = 'h';
			$auth_from = ', host AS '.$auth_host_alias;
			$auth_where = ' AND ' . $auth_host_alias . ".host_name = c.host_name";
		} else {
			$auth_host_alias = $host_query['host_field'];
			$auth_from = ' ,'.$host_query['from'];
			$auth_where = ' AND '.sprintf($host_query['where'], "c.host_name");
		}

		if ($count === false) {
			if (empty($service)) {
				$sql = "SELECT c.* FROM comment c ".$auth_from." WHERE".
					" c.host_name!='' AND (c.service_description='' OR c.service_description is null) ".$auth_where.
					" ORDER BY c.host_name LIMIT ".$offset.", ".$num_per_page;
			} else {
				$sql = "SELECT c.* FROM comment c ".$auth_from." WHERE".
					" c.host_name!='' AND c.service_description!='' ".$auth_where.
					" ORDER BY c.host_name LIMIT ".$offset.", ".$num_per_page;
			}

			$result = $db->query($sql);
			return $result->count() ? $result: false;
		} else {
			if (empty($service)) {
				$sql = "SELECT COUNT(*) AS cnt FROM comment c ".$auth_from." WHERE".
					" c.host_name!='' AND (c.service_description='' OR c.service_description is null) ".$auth_where;
					$result = $db->query($sql);
					return $result->count() ? (int)$result->current()->cnt : 0;
			} else {
				$sql = "SELECT COUNT(*) AS cnt FROM comment c ".$auth_from." WHERE".
					" c.host_name!='' AND c.service_description!=''".$auth_where;
					$result = $db->query($sql);
					return $result->count() ? (int)$result->current()->cnt : 0;
				}
		}
		$result = $db->query($sql);
		return $result->result();
	}

	/**
	*	Wrapper method to fetch a count of all service- or host comments
	*/
	public function count_all_comments($host=false, $service=false)
	{
		return self::fetch_all_comments($host, $service, false, false, true);
	}
}
