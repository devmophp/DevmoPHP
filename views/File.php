<?php
header('HTTP/1.0 '.($this->code ?: '200').($this->message ? " {$this->message}" : ''));
foreach($this->headers ?: array() as $header) {
	header($header);
}
readfile($this->file);
