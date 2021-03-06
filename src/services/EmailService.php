<?php

namespace Notifier\Services;

use Notifier\Models\RssRecord;
use PhpParser\Node\Stmt\TraitUseAdaptation\Alias;

class EmailService extends Service
{

	private const HEADERS = [
		'MIME-Version: 1.0',
		'Content-Type: text/html; charset=ISO-8859-1',
		'From: TheEliteNotifier@aluminadream.com',
	];

	private const SUPPORTED_VIDEO_SERVICES = [
		'youtube' => 'https://www.youtube.com/watch?v=',
	];

	private $receivers = [];


	public function __construct()
	{
		$this->setReceivers();
	}


	private function setReceivers()
	{
		$receivers = $this->getConfig('receivers');
		$this->receivers = explode(',', $receivers);
	}


	public function sendRecordUpdates(array $records = []): void
	{
		foreach ($records as $types) {
			/** @var RssRecord $record */
			foreach ($types as $record) {
				$title = $this->createTitle($record->title);
				$message = $this->createMessage($record);

				foreach ($this->receivers as $receiver) {
					$this->send($receiver, $title, $message);
				}
			}
		}
	}


	private function createTitle(string $title = ''): string
	{
		return 'TheEliteNotifier - ' . $title;
	}


	/**
	 * @var RssRecord $record
	 *
	 * @return string
	 */
	private function createMessage($record = null): string
	{
		if (null === $record) {
			return '';
		}

		return $this->getTemplate([
			'image'      => $this->getStageImageUrl($record->stage, $record->gameInitials),
			'name'       => $record->playerName,
			'nickname'   => $record->playerAlias,
			'stage'      => $record->stage,
			'time'       => $record->timeHms,
			'system'     => $record->system,
			'comment'    => $this->formatComment($record->comment),
			'link'       => $this->createVideoLink($record->videoType, $record->videoId),
			'difficulty' => $record->difficulty,
			'verb'       => $record->untiedWorldRecord === 'true' ? 'untied' : 'achieved',
		]);
	}


	private function getTemplate(array $params = []): string
	{
		$templatePath = '/../resources/templates/record.html';

		$html = file_get_contents(__DIR__ . $templatePath);

		if (!$html) {
			$this->sendException($templatePath . ' was not found.');
			return '';
		}

		foreach ($params as $key => $value) {
			if (!is_string($value)) {
				$value = '';
			}

			$html = str_replace('{{ ' . $key . ' }}', $value, $html);
		}

		if (!$this->isProd()) {
			echo $html;
			return '';
		}

		return $html;
	}


	private function getStageImageUrl(string $stage = 'Dam', string $game = 'ge', string $extension = 'jpg'): string
	{
		$stage = preg_replace('/\s/', '', $stage);

		return $this->getBaseUrl() . 'src/resources/images/stages/' . $game . '/' . $stage . '.' . $extension;
	}


	private function formatComment($comment): string
	{
		if (!is_string($comment)) {
			return '';
		}

		return '"' . $comment . '"';
	}


	private function createVideoLink(string $videoType = '', $videoId = ''): string
	{
		if (!array_key_exists($videoType, self::SUPPORTED_VIDEO_SERVICES)) {
			return $videoType;
		}

		return self::SUPPORTED_VIDEO_SERVICES[$videoType] . $videoId;
	}


	private function send(string $receiver = '', string $title = '', string $message = '', array $headers = []): void
	{
		if (!$headers) {
			$headers = self::HEADERS;
		}

		if ($this->isProd()) {
			mail($receiver, $title, $message, implode("\r\n", $headers));
		}
	}


	public function sendException(string $message = ''): void
	{
		$this->send($this->receivers[0], $this->createTitle('Something is not working correctly'), $message);
	}
}
