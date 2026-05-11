<?php
defined('BASEPATH') OR exit('No direct script access allowed');

echo "\nAn uncaught Exception was encountered\n"
	."Type:        ".get_class($exception)."\n"
	."Message:     ".$message."\n"
	."Filename:    ".$exception->getFile()."\n"
	."Line Number: ".$exception->getLine()."\n";

if (defined('SHOW_DEBUG_BACKTRACE') && SHOW_DEBUG_BACKTRACE === TRUE)
{
	echo "\nBacktrace:\n";
	foreach ($exception->getTrace() as $error)
	{
		if (isset($error['file']) && strpos($error['file'], realpath(BASEPATH)) !== 0)
		{
			echo "\tFile: ".$error['file']."\n"
				."\tLine: ".$error['line']."\n"
				."\tFunction: ".$error['function']."\n\n";
		}
	}
}
