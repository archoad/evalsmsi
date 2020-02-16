<?php

include("functions.php");
header('X-Content-Type-Options: "nosniff"');
global $progVersion;
$log = array();
$log[] = array('program' => 'evalsmsi', 'version' => $progVersion);
$log[] = array('function' => 'csp_report');
if ($data = file_get_contents('php://input')) {
	if ($data = json_decode($data, true)) {
		$log[] = json_encode($data, JSON_UNESCAPED_SLASHES);
		openlog("evalsmsi", LOG_PID, LOG_SYSLOG);
		syslog(LOG_INFO, json_encode($log));
		closelog();
	}
}

?>
