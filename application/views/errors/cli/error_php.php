<?php
defined('BASEPATH') OR exit('No direct script access allowed');

echo "\nA PHP Error was encountered\n\n"
	."Severity: ".$severity."\n"
	."Message:  ".$message."\n"
	."Filename: ".$filepath."\n"
	."Line Number: ".$line;

if (defined('SHOW_DEBUG_BACKTRACE') && SHOW_DEBUG_BACKTRACE === TRUE)
{
	echo "\nBacktrace:\n";
	foreach (debug_backtrace() as $error)
	{
		if (isset($error['file']) && strpos($error['file'], realpath(BASEPATH)) !== 0)
		{
			echo "\tFile: ".$error['file']."\n"
				."\tLine: ".$error['line']."\n"
				."\tFunction: ".$error['function']."\n\n";
		}
	}
}
