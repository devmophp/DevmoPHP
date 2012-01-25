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
      }
      div.body {
        width: 100%;
        border: 1px solid #cccccc;
        background-color: transparent;
        overflow: auto;
      }
    </style>
  </head>
  <body>
    <div class="wrap">
      <div class="body">
        <?=$this->body ?>
      </div>
      <?=$this->poweredby?>
    </div>
  </body>
</html>
