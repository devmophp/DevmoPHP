<?php
/**
 * Main object used to create, format,
 * and display a page view.
 *
 * @category Framework
 * @author Dan Wager
 * @copyright Copyright (c) 2007 Devmo
 * @version 1.0
 */
namespace Devmo;

class View extends \Devmo\Box {
	private $parent;	//	ref to parent view object
	private $template;	//	str	template file


	/**
	 * Initializes class variables
	 *
	 * @param none
	 * @return self
	 */
	public function __construct () {
		$this->parent = null;
		$this->template = null;
	}


  public function __set ($name, $value) {
		if ($value===$this)
			throw new DevmoException('Token Value Is Circular Reference');
  	parent::__set($name,$value);
		if (is_object($value) && $value instanceof View)
			$value->parent = $this;
  }


	/**
	 * Executes the php files in own buffer space
	 *
	 * @param none
	 * @return executed output
	 */
	public function __toString () {
		if (!$this->getTemplate())
			throw new Error("Missing or Invalid Output File:".$this->getTemplate());
		//	create variables
		/*
		if ($this->getTokens())
			extract($this->getTokens(),EXTR_SKIP);
		*/
		//	execute view code and capture output
		ob_start();
		include($this->getTemplate());
		$output = ob_get_contents();
		ob_end_clean();
		//	return executed code as string
		return $output;
	}




	/**
	 * Used to find the root view to build all children at once
	 *
	 * @param none
	 * @return View
	 */
	public function getRoot () {
		return $this->parent
			? $this->parent->getRoot()
			: $this;
	}




	/**
	 * Sets template path and file
	 *
	 * @param path to temlate file
	 * @return void
	 */
	public function setTemplate ($template) {
		//	error checks
		if (!$template)
			throw new Error('Missing Template');
		//	set template
		$this->template = $template;
	}




	/**
	 * Returns template path and file
	 *
	 * @param
	 * @return
	 */
	public function getTemplate () {
		return $this->template;
	}




	/**
	 * Sets single view token
	 *
	 * @param token name, token value
	 * @return void
	 */
	public function setToken ($name, $value) {
		$this->__set($name,$value);
	}





	/**
	 * Returns the token's value
	 *
	 * @param token name
	 * @return token value
	 */
	public function getToken ($name) {
		return $this->__get($name);
	}




	/**
	 * Replaces view's tokens with the given
	 * associative array
	 *
	 * @param associative array
	 * @return void
	 */
	public function setTokens ($tokens) {
		if (is_array($tokens) || is_object($tokens)) {
			foreach ($tokens as $k=>$v) {
				$this->__set($k,$v);
			}
		}
	}




	/**
	 * Returns the associative array of tokens
	 *
	 * @param none
	 * @return associative array of tokens
	 */
	public function getTokens () {
		return $this->data;
	}

} //	EOC
