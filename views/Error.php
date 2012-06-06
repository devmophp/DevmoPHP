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
.trace {
	font: normal 10px arial;
	color: #550000;
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
<div class="header">
	<span>
		you have problems
		<br />
		<span>(but knowing helps ;)</span>
	</span>
</div>
<div>
	<?=$this->body?>
</div>
<pre class="trace"><?=$this->trace?></pre>
