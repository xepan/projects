<?php

namespace xepan\projects;

class Model_CommunicationMessages extends \xepan\base\Model_Table
{	
	function init()
	{
		parent::init();
	}
/**
Project Application
*/
	//Model_Comment
	function afterInsert(){
		$this->app->employee->
		addActivity("Comment On Task: '".$task_name."' Comment By'".$this->app->employee['name']."'",null, $this['employee_id'] /*Related Contact ID*/,null,null,null)->
		notifyTo([$this['employee_id'],$task_created_by]," Comment : '".$this['comment']."' :: Commented by '".$this->app->employee['name']."' :: On Task '".$task_name."' ");
	}

	// Model Task
	function notifyAssignement(){
			
			$this->app->employee
	            ->addActivity("Task '".$this['task_name']."' assigned to '". $emp_name ."'",null, $this['created_by_id'] /*Related Contact ID*/,null,null,null)
	            ->notifyTo([$this['assign_to_id']],$assigntask_notify_msg); 
	}

	function submit(){
		
		if($this['assign_to_id']){
			$this->app->employee
		              ->addActivity("Task '".$this['task_name']."' submitted by '".$this->app->employee['name']."'",null, $this['assign_to_id'] /*Related Contact ID*/,null,null,null)
		              ->notifyTo([$this['created_by_id']],"Task : '" . $this['task_name'] ."' Submitted by '".$this->app->employee['name']."'");
		}
		
	 	$this->app->page_action_result = $this->app->js()->_selector('.xepan-mini-task')->trigger('reload');
	}

	function receive(){
		// throw new \Exception($this->id." = ".$this['status']);
		
		
		if($this['assign_to_id']){
			$this->app->employee
		            ->addActivity("Task '".$this['task_name']."' received by '".$this->app->employee['name']."'",null, $this['assign_to_id'] /*Related Contact ID*/,null,null,null)
		            ->notifyTo([$this['created_by_id']],"Task : '".$this['task_name']."' Received by '".$this->app->employee['name']."'");
		}	

		return true;
	}

	function reject(){

		if($this['assign_to_id']){
			$this->app->employee
		            ->addActivity("Task '".$this['task_name']."' rejected by '".$this->app->employee['name']."'",null, $this['assign_to_id'] /*Related Contact ID*/,null,null,null)
		            ->notifyTo([$this['created_by_id']],"Task :'".$this['task_name']."' Rejected by '".$this->app->employee['name']."'");
		}

		return true;	
	}

	function mark_complete(){		
		if($this['assign_to_id'] == $this['created_by_id']){
			$this->app->employee
		            ->addActivity("Task '".$this['task_name']."' completed by '".$this->app->employee['name']."'",null, $this['assign_to_id'] /*Related Contact ID*/,null,null,null);
		}else{
			$this->app->employee
		            ->addActivity("Task '".$this['task_name']."' completed by '".$this->app->employee['name']."'",null, $this['assign_to_id'] /*Related Contact ID*/,null,null,null)
		            ->notifyTo([$this['created_by_id'],$this['assign_to_id']],"Task : ".$this['task_name']."' marked Complete by '".$this->app->employee['name']."'");
		}

	 	$this->app->page_action_result = $this->app->js()->_selector('.xepan-mini-task')->trigger('reload');
	}

	function page_reopen($p){
		
		if($form->isSubmitted()){
			$this->reopen($form['comment']);
			if($this['assign_to_id']){
				$this->app->employee
			            ->addActivity("Task '".$this['task_name']."' reopen by '".$this->app->employee['name']."'",null, $this['assign_to_id'] /*Related Contact ID*/,null,null,null)
			            ->notifyTo([$this['assign_to_id']],"Task : '".$this['task_name']."' ReOpenned by '".$this->app->employee['name']."' Due To Reason : '".$form['comment']."'");
			}
			return $p->js()->univ()->closeDialog();
		}
	}

	//model_Project
	
	function run(){		
		$this['status']='Running';
		$this->app->employee
            ->addActivity("Project '".$this['name']."' in progress and its status being 'Running' ", null/* Related Document ID*/, $this->id /*Related Contact ID*/,null,null,"xepan_projects_projectdetail&project_id=".$this->id."")
            ->notifyWhoCan('onhold,complete','Running',$this);
		$this->save();
	}

	function onhold(){
		$this['status']='Onhold';
		$this->app->employee
            ->addActivity("Project '".$this['name']."' kept on hold ", null/* Related Document ID*/, $this->id /*Related Contact ID*/,null,null,"xepan_projects_projectdetail&project_id=".$this->id."")
            ->notifyWhoCan('complete,run','Onhold',$this);
		$this->save();
	}

	function complete(){
		$this['status']='Completed';
		$this['actual_completion_date'] = $this->app->today;
		$this->app->employee
            ->addActivity("Project '".$this['name']."' has been completed on date of '".$this['actual_completion_date']."' ", null/* Related Document ID*/, $this->id /*Related Contact ID*/,null,null,"xepan_projects_projectdetail&project_id=".$this->id."")
            ->notifyWhoCan(' ','Completed',$this);
		$this->save();
	}

/**
HR Application
*/

	// Model_Leave
	function submit(){
		$this['status']='Submitted';
		$this->app->employee
            ->addActivity("Employee '".$this->app->employee['name']."' Submitted Leave", null/* Related Document ID*/, $this['employee_id'] /*Related Contact ID*/,null,null,"xepan_hr_employee_hr&contact_id=".$this['employee_id']."")
            ->notifyWhoCan('approve','reject','Submitted',$this);
		$this->save();
	}
	function approve(){
		$this['status']='Approved';
		$this->app->employee
            ->addActivity("Employee '".$this->app->employee['name']."' Approved Leave", null/* Related Document ID*/, $this['employee_id'] /*Related Contact ID*/,null,null,"xepan_hr_employee_hr&contact_id=".$this['employee_id']."")
            ->notifyWhoCan(' ','Approved',$this);
		$this->save();
	}
	function reject(){
		$this['status']='Rejected';
		$this->app->employee
            ->addActivity("Employee '".$this->app->employee['name']."' Rejected Leave", null/* Related Document ID*/, $this['employee_id'] /*Related Contact ID*/,null,null,"xepan_hr_employee_hr&contact_id=".$this['employee_id']."")
            ->notifyWhoCan(' ','Rejected',$this);
		$this->save();
	}

	// Model_Affiliate
	function activate(){
		$this['status']='Active';
		$this->app->employee
            ->addActivity("Affiliate : '".$this['name']."' is now active", null/* Related Document ID*/, $this->id /*Related Contact ID*/,null,null,"xepan_hr_affiliatedetails&contact_id=".$this->id."")
            ->notifyWhoCan('deactivate','Active',$this);
		$this->save();
	}


	function deactivate(){
		$this['status']='InActive';
		$this->app->employee
            ->addActivity("Affiliate : '".$this['name']."' has been deactivated", null/* Related Document ID*/, $this->id /*Related Contact ID*/,null,null,"xepan_hr_affiliatedetails&contact_id=".$this->id."")
            ->notifyWhoCan('activate','InActive',$this);
		$this->save();
	}

	//Model_Department
	function activate(){
		$this['status']='Active';
		$this->app->employee
            ->addActivity("Department : '".$this['name']."' Acivated", null/* Related Document ID*/, $this->id /*Related Contact ID*/,null,null,null)
            ->notifyWhoCan('deactivate','Active',$this);
		$this->saveAndUnload();
	}

	function deactivate(){
		$this['status']='InActive';
		$this->app->employee
            ->addActivity("Department : '".$this['name']."'  has been deactivated", null/* Related Document ID*/, $this->id /*Related Contact ID*/,null,null,null)
            ->notifyWhoCan('activate','InActive',$this);
		$this->saveAndUnload();
	}

	// Model_Employee
	function deactivate(){
		$this['status']='InActive';
		$this->app->employee
            ->addActivity("Employee : '".$this['name']."' has been deactivated", null/* Related Document ID*/, $this->id /*Related Contact ID*/,null,null,"xepan_hr_employeedetail&contact_id=".$this->id."")
            ->notifyWhoCan('activate','InActive',$this);
		$this->save();
		if(($user = $this->ref('user_id')) && $user->loaded()) $user->deactivate();
	}

	function activate(){
		$this['status']='Active';
		$this->app->employee
            ->addActivity("Employee : '".$this['name']."' is now active", null/* Related Document ID*/, $this->id /*Related Contact ID*/,null,null,"xepan_hr_employeedetail&contact_id=".$this->id."")
            ->notifyWhoCan('deactivate','Active',$this);
		$this->save();
		if(($user = $this->ref('user_id')) && $user->loaded()) $user->activate();
	}

	// Model_Post
	function activate(){
		$this['status']='Active';
		$this->app->employee
            ->addActivity("Post : '".$this['name']."'  now active, related to Department : '".$this['department']."' ", null/* Related Document ID*/, $this->id /*Related Contact ID*/,null,null,null)
            ->notifyWhoCan('deactivate','Active',$this);
		$this->saveAndUnload();
	}

	function deactivate(){
		$this['status']='InActive';
		$this->app->employee
            ->addActivity(" Post : '".$this['name']."' has been deactivated, related to Department : '".$this['department']."' ", null/* Related Document ID*/, $this->id /*Related Contact ID*/,null,null,null)
            ->notifyWhoCan('activate','InActive',$this);
		$this->saveAndUnLoad();
	}
/**
Commerce Application
*/

	//Model_Category
	function activate(){
		$this['status'] = "Active";
		$this->app->employee
            ->addActivity("Item's Category : '".$this['name']."' Activated", $this->id/* Related Document ID*/, null /*Related Contact ID*/,null,null,null)
            ->notifyWhoCan('deactivate','Active',$this);
		$this->save();
	}

	function deactivate(){
		$this['status'] = "InActive";
		$this->app->employee
            ->addActivity("Item's Category'". $this['name'] ."' Deactivated", $this->id /*Related Document ID*/, null /*Related Contact ID*/,null,null,null)
            ->notifyWhoCan('activate','InActive',$this);
		$this->save();
	}

	//Model_Mater

	// Activity Message
	function page_send(){
		$qsp_mdl_for_msg = $this->load($this->id);
		$this->app->employee
			->addActivity("'".$qsp_mdl_for_msg['type']."' No. '".$qsp_mdl_for_msg['document_no']."' successfully sent to '".$qsp_mdl_for_msg['contact']."' ", $qsp_mdl_for_msg->id/* Related Document ID*/, $qsp_mdl_for_msg['contact_id'] /*Related Contact ID*/,null,null,"xepan_commerce_".strtolower($qsp_mdl_for_msg['type'])."detail&document_id=".$qsp_mdl_for_msg->id."")
			->notifyWhoCan('send',' ',$qsp_mdl_for_msg);
	}

	// Model_Dispatch Request
	function receive(){

		$this['status']="Received";
		$this->app->employee
            ->addActivity("Jobcard no. '".$this['id']."' recieved successfully by '".$this['department']."' department ", $this->id/* Related Document ID*/, null/*Related Contact ID*/,null,null,"xepan_production_jobcarddetail&document_id=".$this->id."")
            ->notifyWhoCan('dispatch','Received',$this);
		$this->save();
		return true;
		
	}
	function dispatch(){
		$this->api->redirect('xepan_commerce_store_deliveryManagment',['transaction_id'=>$this->id]);
		$this->app->employee
            ->addActivity("Jobcard no .'".$this['id']."' successfully send to dispatched", $this->id/* Related Document ID*/, null /*Related Contact ID*/,null,null,"xepan_production_jobcarddetail&document_id=".$this->id."")
            ->notifyWhoCan('receivedByParty','Dispatch',$this);
	}

	function receivedByParty(){
		$this['status']='ReceivedByParty';
		$this->saveAndUnload();
	}

	// Model_DiscountVoucher

	//activate Voucher
	function activate(){
		$this['status']='Active';
		$this->app->employee
            ->addActivity("Voucher : '".$this['name']."' now active", null/* Related Document ID*/, $this->id /*Related Contact ID*/,null,null,"xepan_commerce_discountvoucher")
            ->notifyWhoCan('activate','InActive',$this);
		$this->save();
	}

	//deactivate Voucher
	function deactivate(){
		$this['status']='InActive';
		$this->app->employee
            ->addActivity("Voucher : '". $this['name'] ."' has been deactivated", null /*Related Document ID*/, $this->id /*Related Contact ID*/,null,null,"xepan_commerce_discountvoucher")
            ->notifyWhoCan('deactivate','Active',$this);
		return $this->save();
	}

	// Model_Customer

	//activate Customer
	function activate(){
		$this['status']='Active';
		$this->app->employee
            ->addActivity("Customer : '".$this['name']."' now active", null/* Related Document ID*/, $this->id /*Related Contact ID*/,null,null,"xepan_commerce_customerdetail&contact_id=".$this->id."")
            ->notifyWhoCan('deactivate','Active',$this);
		$this->save();
	}

	//deactivate Customer
	function deactivate(){
		$this['status']='InActive';
		$this->app->employee
            ->addActivity("Customer : '". $this['name'] ."' has been deactivated", null /*Related Document ID*/, $this->id /*Related Contact ID*/,null,null,"xepan_commerce_customerdetail&contact_id=".$this->id."")
            ->notifyWhoCan('activate','InActive',$this);
		return $this->save();
	}

	// Model_Item

	function publish(){
		$this['status']='Published';
		$this->app->employee
		->addActivity("Item : '".$this['name']."' now published", $this->id/* Related Document ID*/, null /*Related Contact ID*/,null,null,"xepan_commerce_itemdetail&document_id=".$this->id."")
		->notifyWhoCan('unpublish,duplicate','Published');
		$this->save();
	}

	function unpublish(){
		$this['status']='UnPublished';
		$this->app->employee
		->addActivity("Item : '".$this['name']."' has been unpublished", $this->id/* Related Document ID*/, null /*Related Contact ID*/,null,null,"xepan_commerce_itemdetail&document_id=".$this->id."")
		->notifyWhoCan('publish,duplicate','UnPublished');
		$this->save();
	}

	//Model_PurchaseInvoice
	function approve(){

        $this['status']='Due';
        $this->app->employee
        ->addActivity("Purchase Invoice No : '".$this['document_no']."' being due for '".$this['currency']." ".$this['net_amount']."' ", $this->id/* Related Document ID*/, $this['contact_id'] /*Related Contact ID*/,null,null,"xepan_commerce_purchaseinvoicedetail&document_id=".$this->id."")
        ->notifyWhoCan('paid','Due',$this);
        $this->updateTransaction();
        $this->save();
    }

    function redraft(){
        $this['status']='Draft';
        $this->app->employee
        ->addActivity("Purchase Invoice No : '".$this['document_no']."' redraft", $this->id/* Related Document ID*/, $this['contact_id'] /*Related Contact ID*/,null,null,"xepan_commerce_purchaseinvoicedetail&document_id=".$this->id."")
        ->notifyWhoCan('submit','Draft',$this);
        $this->save();
    }

    function cancel(){
        $this['status']='Canceled';
        $this->app->employee
            ->addActivity("Purchase Invoice No : '".$this['document_no']."' canceled & proceed for redraft ", $this->id /*Related Document ID*/, $this['contact_id'] /*Related Contact ID*/,null,null,"xepan_commerce_purchaseinvoicedetail&document_id=".$this->id."")
            ->notifyWhoCan('delete,redraft','Canceled');
        $this->deleteTransactions();
        $this->save();
    }

    function paid(){
        $this['status']='Paid';
        $this->app->employee
        ->addActivity("Amount : ' ".$this['net_amount']." ".$this['currency']." ' Paid , against Purchase Invoice No : '".$this['document_no']."'", $this->id/* Related Document ID*/, $this['contact_id'] /*Related Contact ID*/,null,null,"xepan_commerce_purchaseinvoicedetail&document_id=".$this->id."")
        ->notifyWhoCan('delete','Paid',$this);
        $this->save();
    }

    //Model_PurchaseOrder
    function submit(){
      $this['status']='Submitted';
      $this->app->employee
      ->addActivity("Purchase Order No : '".$this['document_no']."' has submitted", $this->id/* Related Document ID*/, $this['contact_id'] /*Related Contact ID*/,null,null,"xepan_commerce_purchaseorderdetail&document_id=".$this->id."")
      ->notifyWhoCan('reject,approve,createInvoice','Submitted');
      $this->save();
	  }

	  function redraft(){
	    $this['status']='Draft';
	    $this->app->employee
	    ->addActivity("Purchase Order No : '".$this['document_no']."' redraft", $this->id/* Related Document ID*/, $this['contact_id'] /*Related Contact ID*/,null,null,"xepan_commerce_purchaseorderdetail&document_id=".$this->id."")
	    ->notifyWhoCan('submit','Draft',$this);
	    $this->save();
	  }

	  function reject(){
	      $this['status']='Rejected';
	      $this->app->employee
	      ->addActivity("Purchase Order No : '".$this['document_no']."' rejected", $this->id/* Related Document ID*/, $this['contact_id'] /*Related Contact ID*/,null,null,"xepan_commerce_purchaseorderdetail&document_id=".$this->id."")
	      ->notifyWhoCan('submit','Rejected');
	      $this->save();
	  }

	  function approve(){
	      $this['status']='Approved';
	      $this->app->employee
	      ->addActivity("Purchase Order No : '".$this['document_no']."' approved, so it's invoice can be created", $this->id/* Related Document ID*/, $this['contact_id'] /*Related Contact ID*/,null,null,"xepan_commerce_purchaseorderdetail&document_id=".$this->id."")
	      ->notifyWhoCan('reject,markinprogress,createInvoice','Approved');
	      $this->save();
	  }

	  function markinprogress(){
	    $this['status']='InProgress';
	    $this->app->employee
	    ->addActivity("Purchase Order No : '".$this['document_no']."' proceed for dispatching", $this->id/* Related Document ID*/, $this['contact_id'] /*Related Contact ID*/,null,null,"xepan_commerce_purchaseorderdetail&document_id=".$this->id."")
	    ->notifyWhoCan('markhascomplete,sendToStock','InProgress');
	    $this->save();
	  }

	  function cancel(){
	    $this['status']='Canceled';
	    $this->app->employee
	    ->addActivity("Purchase Order No : '".$this['document_no']."' canceled", $this->id/* Related Document ID*/, $this['contact_id'] /*Related Contact ID*/,null,null,"xepan_commerce_purchaseorderdetail&document_id=".$this->id."")
	    ->notifyWhoCan('delete','Canceled');
	    $this->save();
	  }

	  function page_markhascomplete($page){
	    $this->app->employee
	    ->addActivity("Purchase Order No : '".$this['document_no']."' successfully Added to Warehouse", $this->id /*Related Document ID*/, $this['contact_id'] /*Related Contact ID*/,null,null,"xepan_commerce_purchaseorderdetail&document_id=".$this->id."")
	    ->notifyWhoCan('delete,send','Completed');
	    $this->save();
	    $this->app->page_action_result = $form->js(null,$form->js()->closest('.dialog')->dialog('close'))->univ()->successMessage('Item Send To Warehouse');    

	    }

	  }

	  	function page_sendToStock($page){

	        $this['status']='PartialComplete';
	        $this->app->employee
	          ->addActivity("Purchase Order No : '".$this['document_no']."' related products successfully send to stock", $this->id/* Related Document ID*/, $this['contact_id'] /*Related Contact ID*/)
	          ->notifyWhoCan('markhascomplete,send','PartialComplete');
	        $this->save();
	        $this->app->page_action_result = $form->js(null,$form->js()->closest('.dialog')->dialog('close'))->univ()->successMessage('Item Send To Store');
	        // $form->js()->univ()->successMessage('Item Send To Store')->closeDialog();
		}

	// Model_Quotation

	function submit(){
		$this['status']='Submitted';
		$this->app->employee
            ->addActivity("Quotation No : '".$this['document_no']."' has submitted", $this->id/* Related Document ID*/, $this['contact_id'] /*Related Contact ID*/,null,null,"xepan_commerce_quotationdetail&document_id=".$this->id."")
            ->notifyWhoCan('redesign,reject,approve','Submitted',$this);
		$this->save();
	}

	function redesign(){
		$this['status']='Redesign';
		$this->app->employee
		->addActivity("Quotation No : '".$this['document_no']."' proceed for redesign", $this->id/* Related Document ID*/, $this['contact_id'] /*Related Contact ID*/,null,null,"xepan_commerce_quotationdetail&document_id=".$this->id."")
		->notifyWhoCan('reject','Redesign',$this);
		$this->save();
	}

	function reject(){
		$this['status']='Rejected';
		$this->app->employee
		->addActivity("Quotation No : '".$this['document_no']."' rejected", $this->id/* Related Document ID*/, $this['contact_id'] /*Related Contact ID*/,null,null,"xepan_commerce_quotationdetail&document_id=".$this->id."")
		->notifyWhoCan('redesign','Rejected',$this);
		$this->save();
	}

	function approve(){
		$this['status']='Approved';
		$this->app->employee
		->addActivity("Quotation No : '".$this['document_no']."' approved", $this->id/* Related Document ID*/, $this['contact_id'] /*Related Contact ID*/,null,null,"xepan_commerce_quotationdetail&document_id=".$this->id."")
		->notifyWhoCan('redesign,reject,convert,send','Approved',$this);
		$this->save();
	}

	function page_send($page){
		$this->send_QSP($page,$this);
	}

	function convert(){
		$this['status']='Converted';
		$this->app->employee
		->addActivity("Quotation No :. '".$this['document_no']."' converted successfully to order", $this->id/* Related Document ID*/, $this['contact_id'] /*Related Contact ID*/,null,null,"xepan_commerce_quotationdetail&document_id=".$this->id."")
		->notifyWhoCan('send','Converted');
		$this->save();
	}

	//Model_SalesInvoice

	function redesign(){
		$this['status']='Redesign';
		$this->app->employee
		->addActivity("Sales Invoice No : '".$this['document_no']."' proceed for redesign", $this->id/* Related Document ID*/, $this['contact_id'] /*Related Contact ID*/,null,null,"xepan_commerce_salesinvoicedetail&document_id=".$this->id."")
		->notifyWhoCan('submit','Redesign',$this);
		$this->save();
	}

	function redraft(){
		$this['status']='Draft';
		$this->app->employee
		->addActivity("Sales Invoice No : '".$this['document_no']."' redraft", $this->id/* Related Document ID*/, $this['contact_id'] /*Related Contact ID*/,null,null,"xepan_commerce_salesinvoicedetail&document_id=".$this->id."")
		->notifyWhoCan('submit','Draft',$this);
		$this->save();
	}


	function approve(){
		$this['status']='Due';		
		$this->app->employee
		->addActivity("Sales Invoice No : '".$this['document_no']."' being due for '".$this['currency']." ".$this['net_amount']."' ", $this->id/* Related Document ID*/, $this['contact_id'] /*Related Contact ID*/,null,null,"xepan_commerce_salesinvoicedetail&document_id=".$this->id."")
		->notifyWhoCan('redesign,paid,send,cancel','Due',$this);
		$this->updateTransaction();
		$this->save();		
	}

	function cancel(){
		$this['status']='Canceled';
        $this->app->employee
            ->addActivity("Sales Invoice No : '".$this['document_no']."' canceled & proceed for redraft ", $this->id /*Related Document ID*/, $this['contact_id'] /*Related Contact ID*/,null,null,"xepan_commerce_salesinvoicedetail&document_id=".$this->id."")
            ->notifyWhoCan('delete,redraft','Canceled');
		$this->deleteTransactions();
		$this->save();
	}

	function submit(){
		$this['status']='Submitted';
		$this->app->employee
		->addActivity("Sales Invoice No : '".$this['document_no']."' has submitted", $this->id, $this['contact_id'] /*Related Contact ID*/,null,null,"xepan_commerce_salesinvoicedetail&document_id=".$this->id."")
		->notifyWhoCan('approve,reject','Submitted');
		$this->save();
	}

	function paid(){
		$this['status']='Paid';
		$this->app->employee
		->addActivity(" Amount : ' ".$this['net_amount']." ".$this['currency']." ' Recieved, against Sales Invoice No : '".$this['document_no']."'", $this->id/* Related Document ID*/, $this['contact_id'] /*Related Contact ID*/,null,null,"xepan_commerce_salesinvoicedetail&document_id=".$this->id."")
		->notifyWhoCan('send,cancel','Paid');
		$this->save();
	}

	//Model_Supplier

	//activate Supplier
	function activate(){
		$this['status']='Active';
		$this->app->employee
            ->addActivity("Supplier : '".$this['name']."' now active", null/* Related Document ID*/, $this->id /*Related Contact ID*/,null,null,"xepan_commerce_supplierdetail&contact_id=".$this->id."")
            ->notifyWhoCan('activate','InActive',$this);
		$this->save();
	}

	//deactivate Supplier
	function deactivate(){
		$this['status']='InActive';
		$this->app->employee
            ->addActivity("Supplier : '". $this['name'] ."' has been deactivated", null /*Related Document ID*/, $this->id /*Related Contact ID*/,null,null,"xepan_commerce_supplierdetail&contact_id=".$this->id."")
            ->notifyWhoCan('deactivate','Active',$this);
		$this->save();
	}

	// Model_RoundAmountStandard
	$round_amount_standard->app->employee
    ->addActivity("Round Amount Standard : '".$round_amount_standard['round_amount_standard']."' successfully updated for rounding amount in any voucher or bill or invoice", null/* Related Document ID*/, null /*Related Contact ID*/,null,null,"xepan_commerce_amountstandard")
	->notifyWhoCan(' ',' ',$round_amount_standard);
	
	// For Layouts
	$quotation_m->app->employee
			    ->addActivity("Quotation Printing Layout Updated", null/* Related Document ID*/, null /*Related Contact ID*/,null,null,"xepan_commerce_layouts")
				->notifyWhoCan(' ',' ',$quotation_m);

/**
Marketing Application
*/
/**
Account Application
*/
/**
Production Application
*/
/**
CRM Application
*/

	//mOdel_SupportTicket
	function submit(){
		$this['status'] = "Pending";
		$this->app->employee
			->addActivity(" Supportticket '".$this->id."'  has Submitted to ".$this['to_id']."", $this->id, $this['to_id'],null,null,"xepan_crm_ticketdetails&ticket_id=".$this->id."")
			->notifyWhoCan('reject','assign','closed','comment','Pending');
		$this->save();
	}
	function reject(){
		$this['status']='Rejected';
		$this->app->employee
			->addActivity(" Support Ticket No : '[#".$this->id."]' rejected", $this->id, $this['from_id'],null,null,"xepan_crm_ticketdetails&ticket_id=".$this->id."")
			->notifyWhoCan(' ','Rejected');
		$this->saveAndUnload();
	}

	function open(){
		$this['status']='Pending';
		$this->app->employee
			->addActivity(" Support Ticket No : '[#".$this->id."]' reopened", $this->id, $this['from_id'],null,null,"xepan_crm_ticketdetails&ticket_id=".$this->id."")
			->notifyWhoCan('reject','assign','closed','comment','Pending');
		$this->save();
	}
/**
Communication Application
*/

	//For update admin email content
	// For Reset Password
	$config_m->app->employee
			    ->addActivity("'Reset Password Email' Content's Layout Updated For ERP Users", null/* Related Document ID*/, null /*Related Contact ID*/,null,null,"xepan_commerce_layouts")
				->notifyWhoCan(' ',' ',$config_m);

	// For Update Password
	$config_m->app->employee
			    ->addActivity("'Update Password Email' Content's Layout Updated For ERP Users", null/* Related Document ID*/, null /*Related Contact ID*/,null,null,"xepan_commerce_layouts")
				->notifyWhoCan(' ',' ',$config_m);

	// For Frontend

	//For Config
	$frontend_config_m->app->employee
			    ->addActivity("'In Frontend User Configuration' User Registration Type Updated as '".$type."' For Website Users", null/* Related Document ID*/, null /*Related Contact ID*/,null,null,"xepan_communication_general_emailcontent_usertool")
				->notifyWhoCan(' ',' ',$frontend_config_m);
	//For Reset
	$frontend_config_m->app->employee
			    ->addActivity("'Reset Password Email' Content's Layout Updated For Website Users", null/* Related Document ID*/, null /*Related Contact ID*/,null,null,"xepan_communication_general_emailcontent_usertool")
				->notifyWhoCan(' ',' ',$frontend_config_m);
	//For Registration
	$frontend_config_m->app->employee
			    ->addActivity("'New Registration Email' Content's Layout Updated For Website Users", null/* Related Document ID*/, null /*Related Contact ID*/,null,null,"xepan_communication_general_emailcontent_usertool")
				->notifyWhoCan(' ',' ',$frontend_config_m);
	//For Verification
	$frontend_config_m->app->employee
			    ->addActivity("'Verification Email' Content's Layout Updated For Website Users", null/* Related Document ID*/, null /*Related Contact ID*/,null,null,"xepan_communication_general_emailcontent_usertool")
				->notifyWhoCan(' ',' ',$frontend_config_m);
	//For Update
	$frontend_config_m->app->employee
			    ->addActivity("'Update Password Email' Content's Layout Updated For Website Users", null/* Related Document ID*/, null /*Related Contact ID*/,null,null,"xepan_communication_general_emailcontent_usertool")
				->notifyWhoCan(' ',' ',$frontend_config_m);

	//For Subscription
	$frontend_config_m->app->employee
			    ->addActivity("'Subscription Email' Content's Layout Updated For Website Users", null/* Related Document ID*/, null /*Related Contact ID*/,null,null,"xepan_communication_general_emailcontent_usertool")
				->notifyWhoCan(' ',' ',$frontend_config_m);

	// Time Zone
	$misc_m->app->employee
			    ->addActivity("'Time Zone' Updated as '".$form['time_zone']."'", null/* Related Document ID*/, null /*Related Contact ID*/,null,null,"xepan_communication_general_emailcontent_usertool")
				->notifyWhoCan(' ',' ',$misc_m);
	// Company Information
	$company_m->app->employee
			    ->addActivity("Company Information Updated", null/* Related Document ID*/, null /*Related Contact ID*/,null,null,"xepan_communication_general_emailcontent_usertool")
				->notifyWhoCan(' ',' ',$company_m);

/**
CMS Application
*/
/**
BLOG Application
*/

	// Model_BlogPost
	
	//publish Blog Post
	function publish(){
		$this['status']='Published';
		$this['created_at'] = $this->app->now;
		$this->app->employee
            ->addActivity("Blog Post '".$this['title']."' has been published, now it can be view on web", $this->id/* Related Document ID*/, $this->id /*Related Contact ID*/,null,null,"xepan_blog_comment&blog_id=".$this->id."")
            ->notifyWhoCan('unPublish','Published',$this);
		$this->save();
	}

	//unPublish Blog Post
	function unpublish(){
		$this['status']='UnPublished';
		$this->app->employee
            ->addActivity("Blog Post '". $this['title'] ."' has been unpublished, now it not available for show on web", $this->id /*Related Document ID*/, $this->id /*Related Contact ID*/,null,null,"xepan_blog_comment&blog_id=".$this->id."")
            ->notifyWhoCan('publish','UnPublished',$this);
		return $this->save();
	}

	// Model BlogPost Category

	//activate BlogPostCategory
	function activate(){
		$this['status']='Active';
		$this->app->employee
            ->addActivity("Blog Post Category : '".$this['name']."' now active", null/* Related Document ID*/, $this->id /*Related Contact ID*/)
            ->notifyWhoCan('deactivate','Active',$this);
		$this->save();
	}

	//deactivate BlogPostCategory
	function deactivate(){
		$this['status']='InActive';
		$this->app->employee
            ->addActivity("Blog Post Category '". $this['name'] ."' has been deactivated", null /*Related Document ID*/, $this->id /*Related Contact ID*/)
            ->notifyWhoCan('activate','InActive',$this);
		$this->save();
	}
}
