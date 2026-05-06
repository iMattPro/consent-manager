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

use phpbb\controller\helper;
use phpbb\language\language;
use phpbb\template\template;

class frontend_manager
{
	/** @var helper */
	protected $helper;

	/** @var language */
	protected $language;

	/** @var consent_manager_interface */
	protected $consent_manager;

	/** @var template */
	protected $template;

	/**
	 * Constructor.
	 *
	 * @param helper                    $helper Controller helper
	 * @param language                  $language Language service
	 * @param consent_manager_interface $consent_manager Consent manager service
	 * @param template                  $template Template service
	 */
	public function __construct(helper $helper, language $language, consent_manager_interface $consent_manager, template $template)
	{
		$this->helper = $helper;
		$this->language = $language;
		$this->consent_manager = $consent_manager;
		$this->template = $template;
	}

	/**
	 * Register language files needed during user setup.
	 *
	 * @param array $lang_set_ext Existing language extension registrations
	 *
	 * @return array
	 */
	public function get_setup_language_extensions(array $lang_set_ext)
	{
		$lang_set_ext[] = [
			'ext_name' => 'phpbb/consentmanager',
			'lang_set' => 'common',
		];

		return $lang_set_ext;
	}

	/**
	 * Inject consent manager template data on board pages.
	 *
	 * @return void
	 */
	public function inject_frontend()
	{
		if ($this->is_acp_or_installer() || !$this->consent_manager->has_optional_categories())
		{
			return;
		}

		$this->language->add_lang('common', 'phpbb/consentmanager');
		$this->template->assign_vars($this->consent_manager->get_frontend_template_data(
			$this->helper->route('phpbb_consentmanager_log_controller'),
			generate_link_hash('phpbb.consentmanager.log')
		));

		foreach ($this->consent_manager->get_frontend_category_data() as $category)
		{
			$this->template->assign_block_vars('CONSENTMANAGER_CATEGORIES', $category);

			foreach ($category['services'] as $service)
			{
				$this->template->assign_block_vars('CONSENTMANAGER_CATEGORIES.CONSENTMANAGER_SERVICES', $service);
			}
		}
	}

	/**
	 * Determine whether the current request is running in the ACP or installer.
	 *
	 * @return bool
	 */
	protected function is_acp_or_installer()
	{
		return defined('ADMIN_START') || defined('IN_INSTALL');
	}
}
