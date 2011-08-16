<?php
//  define framework constants
define('DEVMO_DIR','../../Devmo');
//  load and initialize applications
require(DEVMO_DIR."/Devmo.php");
Devmo::setDebug(true);
//	do it!
Devmo::run();
