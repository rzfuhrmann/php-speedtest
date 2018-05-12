<?php
	require_once 'speedtest.php';

	$speedtest = new \RZFuhrmann\Speedtest();
	$speedtest->setOpt('debug', true);

	// testing LAN
	$speedtest->setOpt('source_address', '192.168.1.45');
	$res = $speedtest->test();
	var_dump($res);

	// testing WAN1
	//$speedtest->setOpt('source_address', '192.168.0.45');
	//$res = $speedtest->test();
	//var_dump($res);

	// testing WAN2
	$speedtest->setOpt('source_address', '192.168.3.45');
	$res = $speedtest->test();
	var_dump($res);

	// testing WAN3
	//$speedtest->setOpt('source_address', '192.168.5.45');
	//$res = $speedtest->test();
	//var_dump($res);
?>