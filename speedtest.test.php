<?php
	require_once 'speedtest.php';

	$speedtest = new \RZFuhrmann\Speedtest();
	$speedtest->setOpt('debug', true);
	$speedtest->setOpt('source_address', '192.168.1.45');
	$res = $speedtest->test();
	//echo $speedtest->getResult();
	var_dump($res);
?>