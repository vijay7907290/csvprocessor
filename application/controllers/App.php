<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class App extends CI_Controller
{
	/*****************************************
				First initialization
	******************************************/

	public function __construct() 
	{
		parent::__construct();
		$this->load->model('appmodel','',TRUE);
	}
	public function index()
	{
		$response = array('status'=>false, 'reason'=> "Invalid URL", 'data'=>array());
		echo json_encode($response);
	}
	public function uploadcsv()
	{
		$table="records";
		$requiredHeaders = array(array('db_col_name'=>"Module_code","csv_col_name"=>"Module code"),array('db_col_name'=>"Module_name","csv_col_name"=>"Module name"),array('db_col_name'=>"Module_term","csv_col_name"=>"Module term"));
		$response = $this->appmodel->uploadfile($table,$requiredHeaders);
		echo json_encode($response);
	}
}
