<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends Admin_Controller
{
	  	function __construct()
  		{
    		parent::__construct();
			$this->load->library(array('form_validation','csv_import'));
			$this->load->language(array('flash_message','form_validation', 'itinerary', 'common'), 'english');			
			if(!$this->session->has_userdata('admin_logged_in')){
					redirect(SITE_ADMIN_URI);
			}			
			$this->load->model('admin_model'); 
		}
		/**
		 * List the records from itineraries Table
		 *
		 * @param       No
		 * @return      Void
		 */
		public function index()
		{  			
			$config["base_url"]    = base_url().SITE_ADMIN_URI."/itinerary";
			$data['main_content'] = 'itinerary/index';
			$data['page_title']  = 'INWEIGH - Itineraries - List'; 
			$this->load->view(ADMIN_LAYOUT_PATH, $data); 	
		}
		
		/**
		 * List the records from itineraries Table using AJAX
		 *
		 * @param       No
		 * @return      JSON
		 */
		public function ajax()
		{			
			$records = array();
			if (($this->input->post('customActionType') != null) && $this->input->post('customActionType') == 'group_action') {
				//customActionName
				$action_name = $this->input->post('customActionName');
				$ids = $this->input->post('id');
				if($action_name == 'inactive' || $action_name == 'active')
				{
					$this->admin_model->change_status($ids, $action_name);
					$records["customActionStatus"] = "OK"; // pass custom message(useful for getting status of group actions)
					$records["customActionMessage"] = "Record(s) status has been successfully changed. Well done!"; // pass custom message(useful for getting status of group actions)
				}
				else if($action_name == 'delete')
				{
					$this->admin_model->delete_multiple_itineraries($ids);
					$records["customActionStatus"] = "OK"; // pass custom message(useful for getting status of group actions)
					$records["customActionMessage"] = "Record(s) has been successfully deleted. Well done!"; // pass custom message(useful for getting status of group actions)
				}
			}
			if (($this->input->post('customActionType') != null) && $this->input->post('customActionType') == 'individual_delete') 
			{
				$id = $this->input->post('id');			
				$this->admin_model->delete_itinerary($id);
				$records["customActionStatus"] = "OK"; // pass custom message(useful for getting status of group actions)
				$records["customActionMessage"] = "Delete action successfully has been completed. Well done!"; // pass custom message(useful for getting status of group actions)
			}
			$requestData= $this->input->post();
			$query = $this->admin_model->get_list($requestData);
			$result_array = $query->result_array();
			$iTotalRecords = $query->num_rows();
			$iDisplayLength = intval($this->input->post('length'));
			$iDisplayLength = $iDisplayLength < 0 ? $iTotalRecords : $iDisplayLength; 
			$iDisplayStart = intval($this->input->post('start'));
			$sEcho = intval($this->input->post('draw'));		
			$records["data"] = array(); 			
			$end = $iDisplayStart + $iDisplayLength;
			$end = $end > $iTotalRecords ? $iTotalRecords : $end;			
			for($i = $iDisplayStart; $i < $end; $i++)
			{	
				$status = $result_array[$i]['is_active'];
				$route_type = $result_array[$i]['route_type'];
				if($route_type == 1 || $route_type == 2){
					$route_text = "One Way";
				}else if($route_type == 3){
					$route_text = "Round Trip";
				}
				if($status == 1)
				{
					$status_class = 'success';
					$status_text = 'Active';
				}
				else
				{
					$status_class = 'danger';
					$status_text = 'Inactive';
				}
				$admin_unit = getSiteInfo(1);
				$unit_type = $admin_unit[0]->unit_type;
				if($unit_type == 1)
				{
					$unit_text = "Kgs";
				}
				else if($unit_type == 2)
				{
					$unit_text = "Lbs";
				}
				else
				{
					$unit_text = "";
				}
				$user_id = $result_array[$i]['user_id'];
				$passenger_detail = $this->admin_model->get_passenger_detail($result_array[$i]['id'],$user_id);
				$passenger_counts = $this->admin_model->get_passenger_count($result_array[$i]['id'],$user_id,$route_type);
				//echo '<pre>';
				//print_r($passenger_counts);
				$passenger_count = array();
				$bag_count = array();
				foreach($passenger_counts as $counts)
				{
					$passenger_count[$result_array[$i]['id']] += count($counts['id']);
					//$bag_count[$result_array[$i]['id']] += $counts['total_baggage_count'];
				}
				//print_r($passenger_count);
				$j=0;
				
				
				foreach($passenger_detail as $passengers)
				{
					$baggage_detail = $this->admin_model->get_baggage_detail($passengers['travel_id'],$passengers['id']);
					if(!empty($baggage_detail))
					{
						$k=0;
						foreach($baggage_detail as $baggage)
						{
							$actual_weight = $baggage['actual_weight'];
							if($unit_type == 1 && $baggage['unit_type'] == 2)
							{
								$actual_wt = $actual_weight*0.453592;
							}
							else if($unit_type == 2 && $baggage['unit_type'] == 1)
							{
								$actual_wt = $actual_weight*2.20462;
							}
							else if($unit_type && $baggage['unit_type'])
							{
								$actual_wt = $actual_weight;
							}
							else
							{
								$actual_wt = 0;
							}
							$total_weight[$baggage['passenger_id']] += round($actual_wt);
							$total_bag_count[$baggage['passenger_id']] += count($baggage['id']);
							$k++;
						}
					}
					else
					{
						$total_weight[$passengers['id']] = 0;
					}
					
					$total[$result_array[$i]['id']] += $total_weight[$passengers['id']];
					$bag_count[$result_array[$i]['id']] += $total_bag_count[$passengers['id']];
					
					$j++;
				}
				
				$b_c = isset($bag_count[$result_array[$i]['id']])?$bag_count[$result_array[$i]['id']]:'0';
				//print_r($passenger_count);
				$id = $result_array[$i]['id'];
				$sn_no = ($i + 1);
				$records["data"][] = array(
				  '<label class="mt-checkbox mt-checkbox-single mt-checkbox-outline"><input name="id[]" type="checkbox" class="checkboxes" value="'.$id.'"/><span></span></label>',
				  $sn_no,		
				  date('d/m/Y',strtotime($result_array[$i]['created'])),
				  $result_array[$i]['name'],
				  $route_text,
				  $result_array[$i]['flight_name'],
				  //isset($result_array[$i]['flight_no'])?substr($result_array[$i]['flight_no'],2):"--",
				  isset($result_array[$i]['flight_no'])?$result_array[$i]['flight_no']:"--",
				  //$result_array[$i]['departure_from'],
				  //$result_array[$i]['arrival_to'],
				  $result_array[$i]['departure_code'],
				  $result_array[$i]['arrival_code'],
				  date('d/m/Y',strtotime($result_array[$i]['travel_date'])),
				  //$result_array[$i]['passenger_count'],
				  isset($passenger_count[$result_array[$i]['id']])?$passenger_count[$result_array[$i]['id']]:0,
				  nl2br('Bag(s) : '.$b_c."\r\n".'Wt : '.round($total[$result_array[$i]['id']]).$unit_text),
				  //'<span class="label label-sm label-'.$status_class.'">'.$status_text.'</span>',
				  '<a class="edit btn btn-circle btn-icon-only btn-default" href="'.base_url().'admin/itinerary/view/'.$id.'/'.$route_type.'/'.$user_id.'" title="View"> <i class="fa icon-eye"></i> </a>  <a class="delete btn btn-circle btn-icon-only btn-default" href="javascript:;" title="Delete"> <i class="icon-trash"></i> </a> <a class="btn btn-circle btn-icon-only btn-default" href="'.base_url().'appdata/weigh_bag/'.$id.'_'.$user_id.'.xml" title="XML" target="_blank"> <i class="fa fa-send-o"></i> </a>',
				);
			}	
					
			$records["draw"] = $sEcho;
			$records["recordsTotal"] = $iTotalRecords;
			$records["recordsFiltered"] = $iTotalRecords;			  
			echo json_encode($records);
		}
		/**
		 * Edit the record to itineraries Table
		 *
		 * @param       No
		 * @return      Void
		 */
		/*public function edit($id)
		{  						
			$this->load->helper('form');
			$this->load->library('form_validation');
			$itinerary_query = $this->admin_model->get_city($id);
			$data['itinerary_result_array'] = $itinerary_query->row_array();
			$country_id = $data['itinerary_result_array']['country_id'];
			$state_id = $data['itinerary_result_array']['state_id'];
			if ($this->input->server('REQUEST_METHOD') === 'POST')
			{				
				$this->form_validation->set_rules('name', 'City Name', 'trim|required|callback_unique_name_edit[' . $id . ']');
				//$this->form_validation->set_rules('code', 'City Code', 'trim|required');
				$this->form_validation->set_rules('country', 'Country', 'trim|required');
				$this->form_validation->set_rules('state', 'State', 'trim|required');
				if ($this->form_validation->run() === TRUE)
				{	
					$this->admin_model->update_city($id);
					$this->session->set_flashdata('flash_success_message', $this->lang->line('success_edit_msg'));
					redirect(SITE_ADMIN_URI.'/itinerary');												
				}
				$country_id = $this->input->post('country');
				$state_id = $this->input->post('state');
			}
			$state_query = $this->admin_model->get_states($country_id);				
			$data['state_result_array'] = $state_query->result_array();	
			$country_query = $this->admin_model->get_countries();
			$data['country_result_array'] = $country_query->result_array();	
			$config["base_url"]    = base_url().SITE_ADMIN_URI."/itinerary";
			$data['main_content'] = 'itinerary/edit';
			$data['page_title']  = 'INWEIGH - Itineraries - Edit'; 
			$this->load->view(ADMIN_LAYOUT_PATH, $data);  	
		}*/
		
	/**
	* View record from itineraries Table
	*
	* @param       No
	* @return      Void
	*/
	public function view($id,$route_type="",$user_id="") 
	{
		if($route_type == 3){
			$itinerary_routes = $this->admin_model->get_itinerary_routes($id,$user_id);
			foreach($itinerary_routes as $routes){
				$itinerary_details[] = $this->admin_model->get_itinerary_detail($id,$route_type,$user_id,$routes['route_id']);
				$passenger_details[] = $this->admin_model->get_passenger_detail($id,$user_id,$routes['route_id']);
			}
		}else{
			$itinerary_details = $this->admin_model->get_itinerary_detail($id,$route_type,$user_id);
			$passenger_details = $this->admin_model->get_passenger_detail($id,$user_id);
		}
		
		$data['itinerary_result_arrays'] = $itinerary_details;
		$data['passenger_result_arrays'] = $passenger_details;
		$data['route_type'] = $route_type;
		//echo "<pre>";print_r($data);die;
		
		$config["base_url"]  = base_url().SITE_ADMIN_URI."/itinerary";
		$data['page_title']  = 'INWEIGH - Itineraries - View'; 
		$data['main_content'] = 'itinerary/view';
		$this->load->view(ADMIN_LAYOUT_PATH, $data);
	}
		
}
