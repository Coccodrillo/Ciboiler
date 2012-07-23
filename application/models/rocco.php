<?php

class Rocco extends MY_Model {

     function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }
    
    function vnos(){
	$data = array(
			  'narocnik'=> '1',
	          'datumodhoda'=>$this->datvbaz($this->input->post('datumodhoda')),
			  'krajstart'=>$this->input->post('krajstart'),
			  'krajcilj'=>$this->input->post('krajcilj'),
			  'oseba'=>$this->input->post('oseba'),
			  'razdalja'=>number_format($this->brezvej($this->input->post('razdalja')), 1, '.', ''),
			  'drugistroski'=>number_format($this->brezvej($this->input->post('drugistroski')), 2, '.', ''),
			  'datumprihoda'=>$this->datvbaz($this->input->post('datumprihoda')),
			  'izplacilo'=>number_format($this->brezvej($this->input->post('izplacilo')), 2, '.', ''),
	        );
	$this->db->insert('postavke',$data);
  }
  
  function InsertTestData($args)   
  {     
    /* Prepare some fake data (10000 rows, 40,000 values total) */
    
    $rows = array_chunk($args, 10);
    $columns = array('arraysfrom', 'arraysto', 'arrayscc', 'date_sent_stamp', 'subject', 'tekst', 'size', 'read', 'message_id', 'email_fingerprint_auto');
    $this->insert_rows('mailbox', $columns, $rows);
  }
  
  function InsertData()   
  {     
    /* Prepare some fake data (10000 rows, 40,000 values total) */
    $rows = array('rok@loremipsum.si', 'vesna@loremipsum.si', 'vesna@interaktivnost.net', '2011-02-16 09:19:48', 'Test Message!', 'krneki', '2755', '0', '<4d5b88a48b8dc@codeigniter.com>', '3e050d3341a04a63e582bfc539e54de9');
    $columns = array('arraysfrom', 'arraysto', 'arrayscc', 'date_sent_stamp', 'subject', 'tekst', 'size', 'read', 'message_id', 'email_fingerprint_auto');
    $this->db->insert('mailbox', array_combine($columns, $rows));
  }
}
?>