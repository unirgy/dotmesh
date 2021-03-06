<?php
include_once "../../../Highchart.php";

$chart = new Highchart(Highchart::HIGHSTOCK);

$chart->chart->renderTo = "container";
$chart->chart->alignTicks = false;
$chart->rangeSelector->selected = 1;
$chart->title->text = "AAPL Stock Volume";

$chart->series[] = array('type' => "column",
                         'name' => "AAPL Stock Price",
                         'data' => new HighchartJsExpr("data"),
                         'dataGrouping' => array('units' => array(array("week", array(1)),
                                                                  array("month", array(1, 2, 3, 4, 6)))));
?>

<html>
  <head>
    <title>Column</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <?php
      foreach ($chart->getScripts() as $script) {
         echo '<script type="text/javascript" src="' . $script . '"></script>';
      }
    ?>
  </head>
  <body>
    <div id="container"></div>
    <script type="text/javascript">
        $.getJSON('http://www.highcharts.com/samples/data/jsonp.php?filename=aapl-v.json&callback=?', function(data) {
            <?php echo $chart->render("chart"); ?>;
        });
    </script>
  </body>
</html>