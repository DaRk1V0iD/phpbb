<?php
/**
*
* @package testing
* @copyright (c) 2013 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

class phpbb_dbal_sql_insert_buffer_test extends phpbb_database_test_case
{
	protected $db;
	protected $buffer;

	public function setUp()
	{
		parent::setUp();

		$this->db = $this->new_dbal();
		$this->buffer = new phpbb_db_sql_insert_buffer($this->db, 'phpbb_config', 2);
		$this->assert_config_count(2);
	}

	public function getDataSet()
	{
		return $this->createXMLDataSet(dirname(__FILE__) . '/fixtures/config.xml');
	}

	public function test_multi_insert_disabled_insert_and_flush()
	{
		$this->db->multi_insert = false;
		$this->assertTrue($this->buffer->insert($this->get_row(1)));
		$this->assert_config_count(3);
		$this->assertFalse($this->buffer->flush());
		$this->assert_config_count(3);
	}

	public function test_multi_insert_enabled_insert_and_flush()
	{
		$this->check_multi_insert_support();
		$this->assertFalse($this->buffer->insert($this->get_row(1)));
		$this->assert_config_count(2);
		$this->assertTrue($this->buffer->flush());
		$this->assert_config_count(3);
	}

	public function test_multi_insert_disabled_insert_with_flush()
	{
		$this->db->multi_insert = false;
		$this->assertTrue($this->buffer->insert($this->get_row(1)));
		$this->assert_config_count(3);
		$this->assertTrue($this->buffer->insert($this->get_row(2)));
		$this->assert_config_count(4);
	}

	public function test_multi_insert_enabled_insert_with_flush()
	{
		$this->check_multi_insert_support();
		$this->assertFalse($this->buffer->insert($this->get_row(1)));
		$this->assert_config_count(2);
		$this->assertTrue($this->buffer->insert($this->get_row(2)));
		$this->assert_config_count(4);
	}

	public function test_multi_insert_disabled_insert_all_and_flush()
	{
		$this->db->multi_insert = false;
		$this->assertTrue($this->buffer->insert_all($this->get_rows(3)));
		$this->assert_config_count(5);
	}

	public function test_multi_insert_enabled_insert_all_and_flush()
	{
		$this->check_multi_insert_support();
		$this->assertTrue($this->buffer->insert_all($this->get_rows(3)));
		$this->assert_config_count(4);
		$this->assertTrue($this->buffer->flush());
		$this->assert_config_count(5);
	}

	protected function assert_config_count($num_configs)
	{
		$sql = 'SELECT COUNT(*) AS num_configs
			FROM phpbb_config';
		$result = $this->db->sql_query($sql);
		$this->assertEquals($num_configs, $this->db->sql_fetchfield('num_configs'));
		$this->db->sql_freeresult($result);
	}

	protected function check_multi_insert_support()
	{
		if (!$this->db->multi_insert)
		{
			$this->markTestSkipped('Database does not support multi_insert');
		}
	}

	protected function get_row($rownum)
	{
		return array(
			'config_name'	=> "name$rownum",
			'config_value'	=> "value$rownum",
			'is_dynamic'	=> '0',
		);
	}

	protected function get_rows($n)
	{
		$result = array();
		for ($i = 0; $i < $n; ++$i)
		{
			$result[] = $this->get_row($i);
		}
		return $result;
	}
}
