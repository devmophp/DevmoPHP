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
class View {
	private $built;
	private $parent;	//	ref to parent view object
	private $template;	//	str	template file
	private $tokens;	//	arr	variable names and values


	/**
	 * Initializes class variables
	 *
	 * @param none
	 * @return self
	 */
	public function __construct () {
		$this->built = false;
		$this->parent = null;
		$this->template = null;
		$this->tokens = array();
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
		//	error checks
		if (!$name) 
			throw new Error('Token Name Missing');
		if ($value===$this)
			throw new Error('Token Value Is Circular Reference');
		//	set parent
		if (is_object($value) && $value instanceof View)
			$value->parent = $this;
		//	finally do it!
		$this->tokens[$name] = $value;
	}





	/**
	 * Adds value to array token.
	 * This is primarily used to add to views tokens 
	 * created by another controller
	 *
	 * @param token name, token value
	 * @return void
	 */
	public function addToken ($name, $keyOrValue, $hashValue=null) {
		//	error checks
		if (!$name) 
			throw new Error('Token Name Missing');
		if ($keyOrValue==$this)
			throw new Error('Token Value Is Circular Reference');
		if (!is_array(Util::getValue($name,$this->tokens)))
			throw new Error('Token Value Is Not An Array');
	// set data
	if ($keyOrValue && $hashValue)
		$this->tokens[$name][$keyOrValue] = $hashValue;
	else 
		$this->tokens[$name][] = $keyOrValue;
	}





	/**
	 * Returns the token's value
	 *
	 * @param token name
	 * @return token value
	 */
	public function getToken ($name) {
		//	error checks
		if (!$name) 
			throw new Error('Token Name Missing');
		//	return value if not setting
		return Util::getValue($name,$this->tokens);
	}




	/**
	 * Replaces view's tokens with the given
	 * associative array
	 *
	 * @param associative array
	 * @return void
	 */
	public function setTokens ($tokens) {
		$this->tokens = is_array($tokens) ? $tokens : array();
	}




	/**
	 * Returns the associative array of tokens
	 *
	 * @param none
	 * @return associative array of tokens
	 */
	public function getTokens () {
		return $this->tokens;
	}

} //	EOC
?>
