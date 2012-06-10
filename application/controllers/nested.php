<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Nested extends CI_Controller {

	function __construct() {
		parent::__construct();
	}
	
	public function index()
	{
		$this->load->model('MPTtree');
		$this->MPTtree->set_table('sections');
		
		$root = $this->MPTtree->get_root();
		$this->db->select('descendants.id, descendants.lft, descendants.rgt, descendants.title, Count(pages.id) as num_pages, depth');
		$this->MPTtree->AR_from_alldescendants_of($root['lft'],$root['rgt']);
		$this->db->join('pages','descendants.id = pages.cat_id', 'left outer');
		$this->db->group_by('descendants.id');
		$this->db->order_by('descendants.lft');
		$query = $this->db->get();
		$rows = $query->result_array();
		$data["sections"] = $rows;
		$this->load->view('nested', $data);
	}
	
	public function get_json()
	{
		$this->load->model('MPTtree');
		$this->MPTtree->set_table('sections');
		
		$root = $this->MPTtree->get_root();
		$this->db->select('descendants.id, descendants.lft, descendants.rgt, descendants.title, Count(pages.id) as num_pages, depth');
		$this->MPTtree->AR_from_alldescendants_of($root['lft'],$root['rgt']);
		$this->db->join('pages','descendants.id = pages.cat_id', 'left outer');
		$this->db->group_by('descendants.id');
		$this->db->order_by('descendants.lft');
		$query = $this->db->get();
		$rows = $query->result_array();
		echo json_encode($rows);
	}	
	
	public function ajax_sort()
	{		
     	$item = $this->input->post('item');	
     	$moveto = $this->input->post('moveto');
     	$type = $this->input->post('type');
     	$this->load->model('MPTtree');
  	 	$this->MPTtree->set_table('sections'); 
     	$node = $this->MPTtree->get_node_byid($item);
  	 	$snode = $this->MPTtree->get_node_byid($moveto);   
	 
     	if ($type == "appendfirst" )
     	  $this->MPTtree->move_node_append($node['lft'], $snode['lft']);
     	else
     	  $this->MPTtree->move_node_after($node['lft'], $snode['lft']);
		 
	}
	
	public function ajax_addnew()
	{
										
		$newdata=array("title" => $this->input->post('title'));	
		$this->load->model('MPTtree');
		$this->MPTtree->set_table('sections'); 
		$this->MPTtree->append_node_last(1, $newdata); 
		echo $this->input->post('title');

	}
	public function ajax_delete()
	{
										
		$this->load->model('MPTtree');
		$this->MPTtree->set_table('sections'); 
		$this->MPTtree->set_debug(TRUE);
		$this->MPTtree->delete_node($this->input->post('lft'));
		echo "OK";

	}	
	
}
