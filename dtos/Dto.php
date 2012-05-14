<?php
namespace devmo\dtos;
abstract class Dto extends \devmo\libs\Box {
	protected $id;
	public function __construct ($record=null) {
		if (!(is_object($record) || is_array($record)))
			throw new \devmo\exceptions\Exception('record is not iterable');
		foreach ($this as $k=>$v)
			$this->{$k} = \Devmo::getValue($k,$record);
	}
	public function setId ($id) {
		return ($this->id = $id);
	}
	public function getId () {
		return $this->id;
	}
}
