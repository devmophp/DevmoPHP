<?php
header('HTTP/1.0 ' . ($this->code ?: 302) . ' ' . ($this->message ?: ''));
foreach($this->headers ?: array() as $header) {
	header($header);
}
echo $this->content;
