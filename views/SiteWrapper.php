<html>
  <head>
    <title>Powered by Devmo</title>
    <style type="text/css">
      body {
        background-color: white;
      }
      div,td {
        font: normal 12px arial;
      }
      table { 
        border: 1px solid #cccccc;
        border-collapse: collapse;
      }
      div,table {
        margin: 0 auto;
      }
      input {
        border: 1px solid #555555;
        background-color: #dedede;
      }
      input.submit {
        background-color: #ffe2ac;
        border: 1px solid orange;
      }
      div.wrap {
        margin: 0 auto;
        padding: 25px 0px;
        width: 700px;
        height: 400px;
      }
      div.body {
        width: 100%;
        height: 90%;
        border: 1px solid #cccccc;
        background-color: transparent;
        overflow: auto;
      }
      div.poweredby {
        height:10%;
        border-top:1px solid navy;
        margin-top:1px;
        text-align:right;
      }
      span.poweredbyText {
        font: normal 9px arial;
        color: #555555;
      }
      span.poweredbyDevmo {
        font:bold 12px arial;
      }
      span.poweredbyDev {
        color: #000055;
      }
      span.poweredbyM {
        color: #888888;
      }
      span.poweredbyO {
        color: #feb300;
      }
    </style>
  </head>
  <body>
    <div class="wrap">
      <div class="body">
        <?php echo $this->body ?>
      </div>
      <div class="poweredby">
        <span class="poweredbyText">powered by</span>
        <span class="poweredbyDevmo poweredbyDev">Dev</span><span class="poweredbyDevmo poweredbyM">m</span><span class="poweredbyDevmo poweredbyO">o</span>
      </div>
    </div>
    <div style="width:100%;height:100%;background-color:#ffffff"></div>
  </body>
</html>
