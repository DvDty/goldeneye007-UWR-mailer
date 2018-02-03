<?php

/**
 * @param Record[] $records
 */
function notification(array $records): void
{
	$TOs = [
		'dvdtygc@gmail.com',
	];

	$from = 'golden-eye@aluminabuild.com';

	$headers = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'From: ' . $from . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

	$subject = 'Untied world records update!';
	$message = '<h1>The following updates have been made in the elite rank list:</h1><br><br>';

	foreach ($records as $record) {
		$message .= $record->getTitle() . "<br>";
	}

	foreach ($TOs as $to) {
		mail($to, $subject, $message, $headers);
	}
}
