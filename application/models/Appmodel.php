<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Appmodel extends CI_Model
{
	public function checkfile()
    {
        $allowed_mime_types = array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain');
        if(isset($_FILES['file']['name']) && $_FILES['file']['name'] != ""){
            $mime = get_mime_by_extension($_FILES['file']['name']);
            $fileAr = explode('.', $_FILES['file']['name']);
            $ext = end($fileAr);
            if(($ext == 'csv') && in_array($mime, $allowed_mime_types)){
                return array('status'=>true, 'reason'=>'', 'data'=>array());
            }else{
                return array('status'=>false, 'reason'=>'Please select only CSV file to upload.', 'data'=>array());
            }
        }else{
            return array('status'=>false, 'reason'=>'Please select a CSV file to upload.', 'data'=>array());
        }
    }
    public function uploadfile($table='',$requiredHeaders = array())
    {
    	$response = $this->checkfile();
    	$errors = array();
    	if($response['status']){
    		$config['upload_path']          = './uploads/';
            $config['allowed_types']        = 'csv';
            if(!file_exists($config['upload_path'])){
            	mkdir($config['upload_path'],0777);
            }
            $this->load->library('upload', $config);
            if ( ! $this->upload->do_upload('file') ){
            	$response['status'] = false;
                $response['reason'] = $this->upload->display_errors();
            } else {
            	$response['status'] = true;
                $response['reason'] = '';
                $data = $this->upload->data();
                $url = $config['upload_path'].$data['file_name'];
                $csvdata=array();
                $sqlData = array();
                if (($handle = fopen($url, "r")) !== FALSE) {
                	$h = fgetcsv($handle, 1000, ",");
					$colabs = array("st","nd","rd","th");
					$row = 0;
					foreach ($requiredHeaders as $hk => $rheading) {
						if($rheading['csv_col_name']!=$h[$hk]){
							$errors['header'][] = "'".$h[$hk]."' at ".((int)$hk+1).$colabs[((int)$hk>3?3:(int)$hk)]." column";
							$response['status'] = false;
						}
						if($h[$hk]==''){
							$errors['missing_'.$hk][] = $row;
							$response['status'] = false;
						}
						if(preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $h[$hk])){
							$errors['symbols_'.$hk][] = $row;
							$response['status'] = false;
						}
					}
				    while (($d = fgetcsv($handle, 1000, ",")) !== FALSE) {
				        $row++;
						$sqd = array();
						$emptyrow=true;
				    	foreach ($requiredHeaders as $hk => $rheading) {
				    		$emptyrow = $emptyrow && ($d[$hk]=='');
				    	}
				    	if(!$emptyrow){
					    	foreach ($requiredHeaders as $hk => $rheading) {
								if($d[$hk]==''){
									$errors['missing_'.$hk][] = $row;
									$response['status'] = false;
								}
								if(preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $d[$hk])){
									$errors['symbols_'.$hk][] = $row;
									$response['status'] = false;
								}
								if($response['status']){
									$sqd[$rheading['db_col_name']]=$d[$hk];
								}
							}
				    	}
						if($response['status'] && !empty($sqd)){
							$sqlData[]=$sqd;
						}
				    }
				    fclose($handle);
				}else{
					$response['status'] = false;
                	$response['reason'] = 'Unable to read the uploaded file.';
				}
				if($response['status']){
					if(!empty($sqlData)){
						$no=$this->db->insert_batch($table,$sqlData);
						if((int)$no==0){
							$response['status'] = false;
		                	$response['reason'] = 'Unable to save records in the file.';
						}
					}
				}else{
					$response['reason'] = 'Some columns have wrong values';
				}
            }
    	}
    	$errorMessages=array();
    	if(isset($errors['header'])){
    		if(!empty($errors['header'])){
    			$em = $this->tostring($errors['header']);
    			$errorMessages[]="Header column (".$em.") is incorrect in csv file";
    		}
    	}
    	foreach ($requiredHeaders as $hk => $rheading) {
    		if(isset($errors["missing_".$hk])){
	    		if(!empty($errors["missing_".$hk])){
	    			$em = $this->tostring($errors["missing_".$hk]);
	    			$errorMessages[]=$rheading['csv_col_name']." is missing at row ".$em;
	    		}
	    	}
    		if(isset($errors["symbols_".$hk])){
	    		if(!empty($errors["symbols_".$hk])){
	    			$em = $this->tostring($errors["symbols_".$hk]);
	    			$errorMessages[]=$rheading['csv_col_name']." contains symbols at row ".$em;
	    		}
	    	}
    	}
    	if(!$response['status']){
    		$response=$this->send_mail($errorMessages,$url);
    	}
    	return $response;
    }
    public function tostring($arr=array())
    {
    	$em='';
    	if(count($arr)==1){
			$em = $arr[0];
		}
		if(count($arr)==2){
			$em = implode(' and ', $arr);
		}
		if(count($arr)>2){
			$eh = array_pop($arr);
			$em = implode(', ', $arr)." and ".$eh;
		}
		return $em;
    }
    public function send_mail($errorMessages=array(),$url='')
    {
    	$url=trim(''.$url);
    	$response = array('status'=>false, 'reason'=>'Unable to send mail. Invalid cerdentials', 'data'=>array());
    	if(!empty($errorMessages) && $url!=''){
    		if($url[0]='.'){
    			$url=substr($url, 1);
    		}
    		if($url[0]='/'){
    			$url=substr($url, 1);
    		}
    		$message = '<div>File Location : <a href="'.base_url($url).'">'.base_url($url).'<a></div><div><pre>'.json_encode(array_values($errorMessages)).'</pre></div>';
    		$config = array();
			$config['protocol'] = 'smtp';
			$config['smtp_host'] = 'www.awsm.in';
			$config['smtp_user'] = 'awsm';
			$config['smtp_pass'] = 'xxx';
			$config['smtp_port'] = 25;
			$this->email->initialize($config);
    		$this->email->from('fileuploaderror@awsm.in', 'Error Notifications');
			$this->email->to('operations@awsm.in');
			$this->email->set_mailtype('html');
			$this->email->subject('Error Uploading File');
			$this->email->message($message);
			if($this->email->send()){
				$response['status']=true;
				$response['reason']='';
			}
    	}
    	return $response;
    }
}