<style>
h1,h2 {
	font: bold 15px arial;
	color: #660000;
}
h2 {
	font-size: 12px;
}
div, p, li, td, pre, label {
	font: normal 12px arial;
	color: #660000;
}
label {
	font-size: 11px;
	font-weight: bold;
	padding-right: 5px;
}
div,pre {
	padding: 15px;
}
div.header {
	white-space: nowrap;
	text-align: center;
}
div.header span {
	font: bold 14px arial;
}
div.header span span {
	font: normal 11px arial;
}
</style>
<div class="header"><span>so, um, ya... you have problems<br /><span>but knowing is half the battle</span></span></div>
<div><?=$this->body?></div>
<pre style="font-size: 10px;"><?=$this->trace?></pre>
