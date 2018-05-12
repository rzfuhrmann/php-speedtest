<?php
	require_once 'speedtest.php';

	$speedtest = new \RZFuhrmann\Speedtest();
	$speedtest->setOpt('source_address', '192.168.1.45');
	$res = $speedtest->test();
	var_dump($res);
?>