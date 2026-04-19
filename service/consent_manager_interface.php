<?php
/**
 *
 * Consent Manager extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\consentmanager\service;

interface consent_manager_interface
{
	public function register($id, array $definition);

	public function get_frontend_template_data($log_url, $log_hash);

	public function get_acp_template_data();

	public function save_acp_settings(array $settings, array &$errors = []);

	public function reset_consent_version();

	public function validate_log_payload(array $payload);

	public function build_frontend_payload($log_url, $log_hash);

	public function get_categories();

	public function get_services();

	public function normalize_integrations($input, array &$errors = []);

	public function normalize_categories(array $categories);

	public function get_storage_key();

	public function get_cookie_name();

	public function get_version();

	public function is_category_enabled($category);
}
