<style>
h1,h2 {
	font: bold 15px arial;
	color: #660000;
}
h2 {
	font-size: 13px;
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
</style>
<div style="width:50%;margin:50px auto 0px auto;text-align:center">
  <h1>Problem!</h1>
  <p><?php echo $this->body ?></p>
</div>
<div style="padding:15px;">
 	<pre style="font-size: 10px;">
 		<?=$this->trace?>
 	</pre>
</div>
