<?php
/**
 * @package Omeka
 */
require_once 'Zend/Controller/Action.php';
abstract class Kea_Controller_Action extends Zend_Controller_Action
{
	/**
	 * @var protected methods array
	 */
	protected $_protected = array('delete');
	
	/**
	 * @var Kea_View
	 */
	protected $_view;
	
	/**
	 * Doctrine_Table associated with the controller (initialized optionally within the init() method)
	 *
	 * @var Doctrine_Table
	 **/
	protected $_table;
	
	/**
	 * Allows for builtin CRUD scaffolding in the controllers (must be initialized within the init() method)
	 *
	 * @var string
	 **/
	protected $_modelClass;
	
	///// BASIC CRUD INTERFACE /////
	
	public function indexAction()
	{
		$plural = strtolower($this->_modelClass).'s';
		$this->_forward($plural, 'browse');
	}
	
	/**
	 * Builtin browse currently includes all records, must be overloaded to include pagination (as in ItemsController)
	 *
	 * @return void
	 **/
	public function browseAction()
	{
		if(empty($this->_modelClass)) throw new Exception( 'Scaffolding class has not been specified' );
		
		$pluralName = strtolower($this->_modelClass).'s';
		$viewPage = $pluralName.DIRECTORY_SEPARATOR.'browse.php';
		$$pluralName = $this->_table->findAll();
		$this->render($viewPage, compact($pluralName));
	}
	
	public function showAction()
	{
		$varName = strtolower($this->_modelClass);
		
		//duplicated from above
		$pluralName = $varName.'s';
		$viewPage = $pluralName.DIRECTORY_SEPARATOR.'show.php';
		
		try{
			$$varName = $this->findById();
		}catch(Exception $e) {
			echo $e->getMessage();exit;
		}
		
		$this->render($viewPage, compact($varName));
	}
	
	public function addAction()
	{
		//Maybe this recurring bit should be abstracted out
		$varName = strtolower($this->_modelClass);
		$class = $this->_modelClass;
		$pluralName = $varName.'s';
		
		$$varName = new $class();
		
		if($this->commitForm($$varName))
		{
			$this->_redirect($pluralName.'/browse/');
		}else {
			$this->render($pluralName.'/add.php', compact($varName));			
		}

	}
	
	public function editAction()
	{
		$varName = strtolower($this->_modelClass);
		$pluralName = $varName.'s';
		
		try{
			$$varName = $this->findById();
		}catch(Exception $e) {
			echo $e->getMessage();exit;
		}
		
		if($this->commitForm($$varName))
		{
			$this->_redirect($pluralName.'/show/'.$$varName->id);
		}else{
			$this->render($pluralName.'/edit.php', compact($varName));
		}		
	}
	
	public function deleteAction()
	{
		$browseURL = strtolower($this->_modelClass).'s/browse/';
		
		$record = $this->findById();
		$record->delete();
		$this->_redirect($browseURL);
	}
	
	protected function commitForm($record)
	{
		if(!empty($_POST))
		{
			$record->setArray($_POST);
			try {
				$record->save();
				return true;
			}
			catch(Doctrine_Validator_Exception $e) {
				return false;
			}	
		}
		return false;
	}
	
	///// END BASIC CRUD INTERFACE /////
	
	/**
	 * Most convenient usage would be something like: $this->render("show.php", compact("items", "total", "foo", "bar"));
	 *
	 * @param string The page, including .php extension
	 * @param array The variables to be included on that page, where key = name and value = contents.  see compact()
	 * @return void
	 * @author Kris Kelly
	 **/
	public function render($page, array $vars)
	{
		$this->_view->assign($vars);
		$this->getResponse()->appendBody($this->_view->render($page));
	}
	
	public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array())
	{
		parent::__construct($request,$response,$invokeArgs);
		$this->_view = new Kea_View();
	}
	
	/**
	 * Find a particular record given its unique ID # and (optionally) its class name.  Essentially a convenience method
	 * $this->_table must be initialized in the init() method if the particular model is to be chosen automagically
	 *
	 * @return Kea_Record
	 **/
	public function findById($id=null, $table=null)
	{
		$id = (!$id) ? $this->getRequest()->getParam('id') : $id;
		
		if(!$id) throw new Exception( get_class($this).': No ID passed to this request' );
		
		if(!$table) {
			$record = $this->_table->find($id);
		}else {
			$record = Doctrine_Manager::getInstance()->getTable($table)->find($id);
		}
		
		if(!$record) {
			throw new Exception( get_class($this).": No record with ID # $id exists" );
		}
		
		return $record;
	}
}
?>