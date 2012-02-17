<?php
namespace devmo\dtos;

abstract class Dto {
	protected $id;

	public function setId ($id) {
		return ($this->id = $id);
	}

	public function getId () {
		return $this->id;
	}
}
