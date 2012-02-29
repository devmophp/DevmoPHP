<?php
namespace devmo\dtos;

abstract class Dto extends \devmo\libs\Box {
	protected $id;
	protected $created;
	public function setId ($id) {
		return ($this->id = $id);
	}
	public function getId () {
		return $this->id;
	}
	public function setCreated ($created) {
		$this->created = $created;
	}
	public function getCreated () {
		return $this->created;
	}
}
