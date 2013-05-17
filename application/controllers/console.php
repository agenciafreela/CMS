<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Console extends CI_Controller {

	private $force_migration = TRUE;

	public function __construct()
	{
		parent::__construct();
		$this->input->is_cli_request() or exit("Execute via command line: php index.php console");
		$this->load->helper('file');
	}

	/**
	 * Run Migrations
	 */
	public function migration($version = null)
	{
		$this->load->library('migration');

		if(is_null($version))
		{
			// Run the lasted migration
			if(!$this->migration->latest()) show_error($this->migration->error_string());
	  }
		else
		{
			// Run a version
			if(!$this->migration->version($version)) show_error($this->migration->error_string());  
		}
	}

	public function scaffold()
	{
		$args = func_get_args();

		$this->force_migration = TRUE;
		call_user_func_array(array(&$this,'generate_model'),$args);
		call_user_func_array(array(&$this,'generate_controller'),$args);
		call_user_func_array(array(&$this,'generate_views'),$args);
	}



	public function generate_views()
	{
		$args = func_get_args();

		$name = $args[0];
		unset($args[0]);

		$attr = $this->parse_attributes($args);
		$data = array('name' => $name, 'attrs' => $attr);

		$form = str_replace('</?', '<?', $this->load->view('console/views/form', $data, true));
		$edit = str_replace('</?', '<?', $this->load->view('console/views/edit', $data, true));
		$index = str_replace('</?', '<?', $this->load->view('console/views/index', $data, true));
		$insert = str_replace('</?', '<?', $this->load->view('console/views/insert', $data, true));
		$show = str_replace('</?', '<?', $this->load->view('console/views/show', $data, true));
		$trash = str_replace('</?', '<?', $this->load->view('console/views/trash', $data, true));

		$path = sprintf("%s/views/%s", APPPATH, plural($name));
		$form_file = sprintf("%s/views/%s/form.php", APPPATH, plural($name));
		$edit_file = sprintf("%s/views/%s/edit.php", APPPATH, plural($name));
		$index_file = sprintf("%s/views/%s/index.php", APPPATH, plural($name));
		$insert_file = sprintf("%s/views/%s/insert.php", APPPATH, plural($name));
		$show_file = sprintf("%s/views/%s/show.php", APPPATH, plural($name));
		$trash_file = sprintf("%s/views/%s/trash.php", APPPATH, plural($name));

		if(!is_dir($path))
			mkdir($path);

		write_file($form_file,$form);	
		write_file($edit_file,$edit);
		write_file($index_file,$index);
		write_file($insert_file,$insert);
		write_file($show_file,$show);
		write_file($trash_file,$trash);
	}

	/**
	 * Generate Model
	 */
	public function generate_controller()
	{
		$args = func_get_args();

		if(count($args) < 1) 
			$this->show_error("Model deve conter um nome");

		$name = $args[0];

		$controller = '<?php ' . $this->load->view('console/controller', array('name' => $name), true);
		$controller_file = sprintf("%s/controllers/%s.php", APPPATH, plural($name));

		write_file($controller_file,$controller);

	}

	/**
	 * Generate Model
	 */
	public function generate_model()
	{
		$args = func_get_args();

		if(count($args) < 1) 
			$this->show_error("Model deve conter um nome");
		elseif(count($args) > 1)
			if($this->force_migration || $this->get_input('Criar migration? [y/n]') == 'y')
				call_user_func_array(array(&$this,'generate_migration'),$args);

		$name = $args[0];

		$model = '<?php ' . $this->load->view('console/model', array('name' => $name), true);
		$model_file = sprintf("%s/models/%s_model.php", APPPATH, $name);

		write_file($model_file,$model);

	}

	/**
	 * Generate Migration
	 */
	public function generate_migration()
	{
		$args = func_get_args();

		if(count($args) < 2) 
			$this->show_error("Migration deve conter um nome e no mínimo um atributo");

		$name = $args[0];

		unset($args[0]);

		$attr = $this->parse_attributes($args);

		$data = array('name' => $name, 'attrs' => $attr);

		$migration = '<?php ' . $this->load->view('console/migration', $data, true);
		
		
		$this->load->library('migration');

		$migrations = $this->migration->find_migrations();
		
		$last_migration = basename(end($migrations));
		$version = sscanf($last_migration, "%d");
		
		$migration_file = sprintf("%s/migrations/%03s_%s.php", APPPATH, $version[0] + 1, $name);

		write_file($migration_file,$migration);

	}

	/**
	 * Parse atributues
	 */
	private function parse_attributes($attr)
	{

		// If is_array use recursive
		if(is_array($attr))
		{
			$ret = array();
			foreach ($attr as $value) {
				$ret[] = $this->parse_attributes($value);
			}

			return $ret;
		}

		$default_properties = array();
		$default_properties['string'] = $default_properties['file'] = array('type' => 'varchar', 'constraint' => '240');
		$default_properties['text'] = array('type' => 'text');
		$default_properties['date'] = array('type' => 'date');
		$default_properties['datetime'] = array('type' => 'datetime');
		$default_properties['bool'] = array('type' => 'tinyint');

		// Function
		$ret = array();
		@list($ret['name'],$str_properties) = explode(':', $attr);
		$array_properties = explode(',', $str_properties);

		$properties = array();
		foreach ($array_properties as $value) {
			$tmp = explode('=', $value);
			
			if(isset($default_properties[$tmp[0]]))
			{
				$properties = array_merge($properties, $default_properties[$tmp[0]]);
			}
			elseif($tmp[0] && $ret['name'] != 'belong_to')
				$properties[$tmp[0]] = $tmp[1];
			elseif($ret['name'] == 'belong_to')
				$properties = $tmp[0];
		}

		$ret['properties'] = $properties;

		return $ret;
	}

	public function test_attr()
	{
		$args = func_get_args();
		$attr = $this->parse_attributes($args);
		print_r($attr);
	}


	// Console Tools
	// --------------------------------------

	private function show_error($msg)
	{
		echo "********************************************************************************************************\n";
		echo "ERRO: $msg \n";
		echo "********************************************************************************************************\n";
		exit;
	}

	private function get_input($msg){
	  fwrite(STDOUT, "$msg: ");
	  $varin = trim(fgets(STDIN));
	  return $varin;
	}

}

/* End of file console.php */
/* Location: ./application/controllers/console.php */