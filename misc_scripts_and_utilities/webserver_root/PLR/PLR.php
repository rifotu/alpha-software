<?php
include("../libraries/func.php");

// This reads all the register values into one big array via a shell script
$registers = GetRegisters();

$padding = 10;
$height = 512;
$width = 768;

if (isset($_POST["form1"])) {
	if ($_POST["form1"] == "Apply") {
		// Exposure Time 1
		$regs = CalcExposureRegisters($_POST["exptime1"], $registers[82], $registers[85], 12, 250000000);
		SetRegisterValue(71, $regs[0]);
		$registers[71] = strtoupper(dechex($regs[0]));
		SetRegisterValue(72, $regs[1]);
		$registers[72] = strtoupper(dechex($regs[1]));
		
		// Exposure Time 2
		$regs = CalcExposureRegisters($_POST["exptime2"], $registers[82], $registers[85], 12, 250000000);
		SetRegisterValue(75, $regs[0]);
		$registers[75] = strtoupper(dechex($regs[0]));
		SetRegisterValue(76, $regs[1]);
		$registers[76] = strtoupper(dechex($regs[1]));
	
		// Exposure Time 3
		$regs = CalcExposureRegisters($_POST["exptime3"], $registers[82], $registers[85], 12, 250000000);
		SetRegisterValue(77, $regs[0]);
		$registers[77] = strtoupper(dechex($regs[0]));
		SetRegisterValue(78, $regs[1]);
		$registers[78] = strtoupper(dechex($regs[1]));
		
		// Number of Slopes
		SetRegisterValue(79, $_POST["slopes"]);
		$registers[79] = strtoupper(dechex($_POST["slopes"]));
		
		// Vtfl3 & Vtfl2
		$Vtfl3en = $_POST["VTFL3en"];
		$Vtfl2en = $_POST["VTFL2en"];
		$Vtfl3 = $_POST["VTFL3"];
		$Vtfl2 = $_POST["VTFL2"];
		$tmpreg =  $Vtfl3en*pow(2, 13) + $Vtfl3*pow(2, 7) + $Vtfl2en*pow(2, 6) + $Vtfl2;
		SetRegisterValue(106, $tmpreg);
		$registers[106] = strtoupper(dechex($tmpreg));
	}
}

$exposure1_ns = CalcExposureTime(hexdec($registers[72])*65536+hexdec($registers[71]), $registers[82], $registers[85], 12, 250000000);
$exposure2_ns = CalcExposureTime(hexdec($registers[76])*65536+hexdec($registers[75]), $registers[82], $registers[85], 12, 250000000);
$exposure3_ns = CalcExposureTime(hexdec($registers[78])*65536+hexdec($registers[77]), $registers[82], $registers[85], 12, 250000000);
$PLR_exp2 = $exposure2_ns/$exposure1_ns; //range 0..1 fraction of exposure time 1
$PLR_exp3 = $exposure3_ns/$exposure1_ns; //range 0..1 fraction of exposure time 1
$hdrvoltage2enabled = ExtractBits($registers[106], 6);
$hdrvoltage3enabled = ExtractBits($registers[106], 13);
$PLR_vtfl2 = ExtractBits($registers[106], 0, 6); //range 0..63
$PLR_vtfl3 = ExtractBits($registers[106], 7, 6); //range 0..63
$slopes = hexdec($registers[79]);

?>

<!DOCTYPE HTML>
<html>
  <head>
    <style>
      body {
        padding: 10px;
      }
	  .val-input {
		  display: inline-block;
		  width: 60px;
	  }
	  .val-label {
		  display: inline-block;
		  float: left;
		  width: 180px;
	  }
    </style>
    <title>apertus&deg; Axiom Alpha PLR Settings</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap -->
    <link href="../libraries/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<script src="../libraries/jquery-2.0.3.min.js"></script>
  </head>
  <body>
    <p><a class="btn btn-primary" href="/index.php">Back</a></p>
    <h1 style="margin-top: 0px; padding-top:10px">apertus&deg; Axiom Alpha PLR Curve</h1>
    <div style="float:left; padding-right:10px" id="container1"></div>
	
	<div style="float:left; padding-right:10px" id="settings">
		Settings:</br>
		<?php 
		echo "<form method=\"POST\">";
		echo "<p><div class=\"val-label\">Number of Slopes:</div>";
		echo "<input class=\"val-input\" type=\"text\" id=\"slopes\" name=\"slopes\" size=\"6\" value=\"".$slopes."\"></p>"; 
		echo "<p><div class=\"val-label\">Exposure Time 1:</div>";
		echo "<input class=\"val-input\" type=\"text\" id=\"exptime1\" name=\"exptime1\" size=\"6\" value=\"".round($exposure1_ns, 3)."\"> ms</p>"; 
		echo "<p><div class=\"val-label\">Exposure Time 2:</div>";
		echo "<input class=\"val-input\" type=\"text\" id=\"exptime2\" name=\"exptime2\" size=\"6\" value=\"".round($exposure2_ns, 3)."\"> ms</p>"; 
		echo "<p><div class=\"val-label\">Exposure Time 3:</div>";
		echo "<input class=\"val-input\" type=\"text\" id=\"exptime3\" name=\"exptime3\" size=\"6\" value=\"".round($exposure3_ns, 3)."\"> ms</p>"; 
		echo "<p><div class=\"val-label\">VTFL2 enabled:</div>";
		echo "<input class=\"val-input\" type=\"text\" id=\"VTFL2en\" name=\"VTFL2en\" size=\"6\" value=\"".$hdrvoltage2enabled."\"></p>"; 
		echo "<p><div class=\"val-label\">VTFL2:</div>";
		echo "<input class=\"val-input\" type=\"text\" id=\"VTFL2\" name=\"VTFL2\" size=\"6\" value=\"".$PLR_vtfl2."\"></p>";
		echo "<p><div class=\"val-label\">VTFL3 enabled:</div>";
		echo "<input class=\"val-input\" type=\"text\" id=\"VTFL3en\" name=\"VTFL3en\" size=\"6\" value=\"".$hdrvoltage3enabled."\"></p>"; 
		echo "<p><div class=\"val-label\">VTFL3:</div>";
		echo "<input class=\"val-input\" type=\"text\" id=\"VTFL3\" name=\"VTFL3\" size=\"6\" value=\"".$PLR_vtfl3."\"></p>"; 
		echo "<input class=\"btn btn-primary\" type=\"submit\" name=\"form1\" value=\"Apply\"></form>";
		?>
	</div>
	
    <script src="kinetic-v5.0.1.min.js"></script>
    <script defer="defer">
	
      var stage = new Kinetic.Stage({
        container: 'container1',
        width: <? echo $width+2*$padding; ?>,
        height: <? echo $height+2*$padding; ?>,
      });

      var layer = new Kinetic.Layer();

	  //black background
	  var rect = new Kinetic.Rect({
        x: 0,
        y: 0,
        width: <? echo $width+2*$padding; ?>,
        height: <? echo $height+2*$padding; ?>,
        fill: 'black'
      });
      layer.add(rect);
	  
	  //10% background fill
	  var rect2 = new Kinetic.Rect({
        x: <? echo $padding; ?>,
        y: <? echo $padding; ?>,
        width: <? echo $width*0.1; ?>,
        height: <? echo $height; ?>,
        fill: '#080808'
      });
      layer.add(rect2);
	  
	  //50% background fill
	  var rect3 = new Kinetic.Rect({
        x: <? echo $padding+$width*0.1; ?>,
        y: <? echo $padding; ?>,
        width: <? echo $width*0.4; ?>,
        height: <? echo $height; ?>,
        fill: '#101010'
      });
      layer.add(rect3);
	  
	  //90% background fill
	  var rect4 = new Kinetic.Rect({
        x: <? echo $padding+$width*0.5; ?>,
        y: <? echo $padding; ?>,
        width: <? echo $width*0.4; ?>,
        height: <? echo $height; ?>,
        fill: '#181818'
      });
      layer.add(rect4);
	  
	  //100% background fill
	  var rect5 = new Kinetic.Rect({
        x: <? echo $padding+$width*0.9; ?>,
        y: <? echo $padding; ?>,
        width: <? echo $width*0.1; ?>,
        height: <? echo $height; ?>,
        fill: '#202020'
      });
      layer.add(rect5);
	  
	  //horizontal 0% line
	  var lutaxis1Line = new Kinetic.Line({
		points: [<? echo $padding; ?>,<? echo $height+$padding; ?>, <? echo $width+$padding; ?>, <? echo $height+$padding; ?>],
        stroke: '#999',
        strokeWidth: 1,
        lineCap: 'round',
        lineJoin: 'round'
      });
      layer.add(lutaxis1Line);
	  
	  //horizontal 100% line
	  var lutaxis3Line = new Kinetic.Line({
		points: [<? echo $padding; ?>,<? echo $padding; ?>, <? echo $width+$padding; ?>, <? echo $padding; ?>],
        stroke: '#555',
        strokeWidth: 1,
        lineCap: 'round',
        lineJoin: 'round'
      });
      layer.add(lutaxis3Line);
	  
	  //vertical 0% line
	  var lutaxis2Line = new Kinetic.Line({
		points: [<? echo $padding; ?>,<? echo $height+$padding; ?>, <? echo $padding; ?>, <? echo $padding; ?>],
        stroke: '#999',
        strokeWidth: 1,
        lineCap: 'round',
        lineJoin: 'round'
      });
      layer.add(lutaxis2Line);
	  
	  //horizontal 50% line
	  var lutindicatorLine03 = new Kinetic.Line({
		points: [<? echo $padding; ?>, <? echo 0.5*$height+$padding; ?>, <? echo $padding+$width; ?>, <? echo 0.5*$height+$padding; ?>],
        stroke: '#555',
        strokeWidth: 1,
        lineCap: 'round',
        lineJoin: 'round'
      });
      layer.add(lutindicatorLine03);
	  
	  //horizontal 10% line
	  var lutindicatorLine04 = new Kinetic.Line({
		points: [<? echo $padding; ?>, <? echo 0.1*$height+$padding; ?>, <? echo $padding+$width; ?>, <? echo 0.1*$height+$padding; ?>],
        stroke: '#555',
        strokeWidth: 1,
        lineCap: 'round',
        lineJoin: 'round'
      });
      layer.add(lutindicatorLine04);
	  
	  //horizontal 90% line
	  var lutindicatorLine05 = new Kinetic.Line({
		points: [<? echo $padding; ?>, <? echo 0.9*$height+$padding; ?>, <? echo $padding+$width; ?>, <? echo 0.9*$height+$padding; ?>],
        stroke: '#555',
        strokeWidth: 1,
        lineCap: 'round',
        lineJoin: 'round'
      });
      layer.add(lutindicatorLine05);
	  
	  //horizontal 50% label
	  var lutindicatorText04 = new Kinetic.Text({
        x: <? echo $padding+2; ?>,
        y: <? echo (($height)*0.5+$padding)+3; ?>,
        text: '50%',
        fontSize: 6,
        fontFamily: 'Arial',
        fill: '#777'
      });
	  layer.add(lutindicatorText04);
	  
	  //horizontal 10% label
	  var lutindicatorText05 = new Kinetic.Text({
        x: <? echo $padding+2; ?>,
        y: <? echo (($height)*0.9+$padding)+3; ?>,
        text: '10%',
        fontSize: 6,
        fontFamily: 'Arial',
        fill: '#777'
      });
	  layer.add(lutindicatorText05);
	  
	  //horizontal 90% label
	  var lutindicatorText06 = new Kinetic.Text({
        x: <? echo $padding+2; ?>,
        y: <? echo (($height)*0.1+$padding)+3; ?>,
        text: '90%',
        fontSize: 6,
        fontFamily: 'Arial',
        fill: '#777'
      });
	  layer.add(lutindicatorText06);
	  
	  //output label
	  var lutindicatorText07 = new Kinetic.Text({
        x: <? echo 2; ?>,
        y: <? echo (($height)*0.5+$padding)+16; ?>,
        text: 'Digital Value',
        fontSize: 8,
        fontFamily: 'Arial',
		rotation: 270,
        fill: '#777'
      });
	  layer.add(lutindicatorText07);
	  
	  //input label
	  var lutindicatorText08 = new Kinetic.Text({
        x: <? echo (($width)*0.5+$padding)-12; ?>,
        y: <? echo $height+2*$padding-9; ?>,
        text: 'LIGHT',
        fontSize: 8,
        fontFamily: 'Arial',
        fill: '#777'
      });
	  layer.add(lutindicatorText08);
	  
	  var PLRLine = new Kinetic.Line({
		<?php 
		//echo "points: [ ".$padding.", ".$height+$padding.", ".$width/3+$padding.", ".$padding+$height-$PLR_vtfl2*$height." ],";
		echo "points: [ ";
		echo $padding.", ".($height+$padding).", ";
		echo ($width-($width*$PLR_exp2) + $padding).", ".($padding+$height-(($PLR_vtfl2/63)*$height)).", ";
		echo ($width-($width*$PLR_exp3) + $padding).", ".($padding+$height-(($PLR_vtfl3/63)*$height)).", ";
		echo ($width+$padding).", ".($padding)." ],\n";
		?>
        stroke: '#FF0000',
        strokeWidth: 2,
        lineCap: 'round',
        lineJoin: 'round'
      });
      layer.add(PLRLine);
	  
	  
	  //kneepoint1
	  var kneepoint1 = new Kinetic.Circle({
	    x: <? echo ($width-($width*$PLR_exp2) + $padding); ?>,
        y: <? echo ($padding+$height-(($PLR_vtfl2/63)*$height)); ?>,
	    radius: 4,
	    stroke: 'red',
		fill: '#222',
	    strokeWidth: 2
	  });
	  layer.add(kneepoint1);
	  
	  //kneepoint2
	  var kneepoint2 = new Kinetic.Circle({
	    x: <? echo ($width-($width*$PLR_exp3) + $padding); ?>,
        y: <? echo ($padding+$height-(($PLR_vtfl3/63)*$height)); ?>,
	    radius: 4,
	    stroke: 'red',
		fill: '#222',
	    strokeWidth: 2
	  });
	  layer.add(kneepoint2);
	  
      stage.add(layer);
    </script>
  </body>
</html>