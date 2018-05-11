<?php
	require_once 'speedtest.php';

	$speedtest = new \RZFuhrmann\Speedtest();
	$res = $speedtest->test();
	var_dump($res);
?>