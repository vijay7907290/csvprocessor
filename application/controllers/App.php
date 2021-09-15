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
		$response = $this->appmodel->uploadfile();
		echo json_encode($response);
	}
}
