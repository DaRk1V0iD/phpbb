<?php
/**
*
* @package testing
* @copyright (c) 2008 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

abstract class phpbb_database_test_case extends PHPUnit_Extensions_Database_TestCase
{
	static private $already_connected;

	protected $test_case_helpers;

	public function __construct($name = NULL, array $data = array(), $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->backupStaticAttributesBlacklist += array(
			'PHP_CodeCoverage' => array('instance'),
			'PHP_CodeCoverage_Filter' => array('instance'),
			'PHP_CodeCoverage_Util' => array('ignoredLines', 'templateMethods'),
			'PHP_Timer' => array('startTimes',),
			'PHP_Token_Stream' => array('customTokens'),
			'PHP_Token_Stream_CachingFactory' => array('cache'),

			'phpbb_database_test_case' => array('already_connected'),
		);
	}

	public function createXMLDataSet($path)
	{
		$db_config = $this->get_database_config();

		// Firebird requires table and column names to be uppercase
		if ($db_config['dbms'] == 'phpbb_db_driver_firebird')
		{
			$xml_data = file_get_contents($path);
			$xml_data = preg_replace_callback('/(?:(<table name="))([a-z_]+)(?:(">))/', 'phpbb_database_test_case::to_upper', $xml_data);
			$xml_data = preg_replace_callback('/(?:(<column>))([a-z_]+)(?:(<\/column>))/', 'phpbb_database_test_case::to_upper', $xml_data);

			$new_fixture = tmpfile();
			fwrite($new_fixture, $xml_data);
			fseek($new_fixture, 0);

			$meta_data = stream_get_meta_data($new_fixture);
			$path = $meta_data['uri'];
		}

		return parent::createXMLDataSet($path);
	}

	public function get_test_case_helpers()
	{
		if (!$this->test_case_helpers)
		{
			$this->test_case_helpers = new phpbb_test_case_helpers($this);
		}

		return $this->test_case_helpers;
	}

	public function get_database_config()
	{
		$config = phpbb_test_case_helpers::get_test_config();

		if (!isset($config['dbms']))
		{
			$this->markTestSkipped('Missing test_config.php: See first error.');
		}

		return $config;
	}

	public function getConnection()
	{
		$config = $this->get_database_config();

		$manager = $this->create_connection_manager($config);

		if (!self::$already_connected)
		{
			$manager->recreate_db();
		}

		$manager->connect();

		if (!self::$already_connected)
		{
			$manager->load_schema();
			self::$already_connected = true;
		}

		return $this->createDefaultDBConnection($manager->get_pdo(), 'testdb');
	}

	public function new_dbal()
	{
		global $phpbb_root_path, $phpEx;

		$config = $this->get_database_config();

		$dbms = $config['dbms'];
		$db = new $dbms();
		$db->sql_connect($config['dbhost'], $config['dbuser'], $config['dbpasswd'], $config['dbname'], $config['dbport']);

		return $db;
	}

	public function assertSqlResultEquals($expected, $sql, $message = '')
	{
		$db = $this->new_dbal();

		$result = $db->sql_query($sql);
		$rows = $db->sql_fetchrowset($result);
		$db->sql_freeresult($result);

		$this->assertEquals($expected, $rows, $message);
	}

	public function setExpectedTriggerError($errno, $message = '')
	{
		$this->get_test_case_helpers()->setExpectedTriggerError($errno, $message);
	}

	protected function create_connection_manager($config)
	{
		return new phpbb_database_test_connection_manager($config);
	}

	/**
	* Converts a match in the middle of a string to uppercase.
	* This is necessary for transforming the fixture information for Firebird tests
	*
	* @param $matches The array of matches from a regular expression
	*
	* @return string The string with the specified match converted to uppercase
	*/
	public static function to_upper($matches)
	{
		return $matches[1] . strtoupper($matches[2]) . $matches[3];
	}
}
