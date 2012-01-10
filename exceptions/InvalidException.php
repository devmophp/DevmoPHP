<?php
namespace devmo\exceptions;

class InvalidException extends \devmo\exceptions\Exception {
  public function __construct ($what,$value) {
    parent::__construct(($value ? "Invalid Value Found For {$what}" : "Missing Value For {$what}"));
  }
}
