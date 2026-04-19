<?php
/**
 *
 * Consent Manager extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\consentmanager\controller;

use phpbb\consentmanager\service\consent_manager_interface;
use phpbb\consentmanager\service\log_manager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class log_controller
{
	/** @var log_manager */
	protected $log_manager;

	/** @var consent_manager_interface */
	protected $consent_manager;

	public function __construct(log_manager $log_manager, consent_manager_interface $consent_manager)
	{
		$this->log_manager = $log_manager;
		$this->consent_manager = $consent_manager;
	}

	public function log(Request $request)
	{
		$payload = json_decode($request->getContent(), true);

		if (!is_array($payload))
		{
			return new JsonResponse([
				'success' => false,
				'error' => 'invalid_payload',
			], Response::HTTP_BAD_REQUEST);
		}

		$submission = $this->consent_manager->validate_log_payload($payload);
		if (empty($submission['success']))
		{
			return new JsonResponse([
				'success' => false,
				'error' => $submission['error'],
			], $this->get_error_status_code($submission['error']));
		}

		$this->log_manager->log_consent($submission['categories'], $submission['version']);

		return new JsonResponse([
			'success' => true,
			'categories' => $submission['categories'],
			'version' => $submission['version'],
		]);
	}

	protected function get_error_status_code($error)
	{
		switch ($error)
		{
			case 'invalid_hash':
				return Response::HTTP_FORBIDDEN;

			case 'version_mismatch':
				return Response::HTTP_CONFLICT;

			default:
				return Response::HTTP_BAD_REQUEST;
		}
	}
}
