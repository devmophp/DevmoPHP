<?php
namespace devmo\dtos;

abstract class Dto extends \devmo\libs\Box {
	protected $id;

	public function setId ($id) {
		return ($this->id = $id);
	}

	public function getId () {
		return $this->id;
	}
}
