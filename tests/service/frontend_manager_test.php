<?php
/**
 *
 * Consent Manager extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\consentmanager\tests\service;

class frontend_manager_test extends \phpbb_test_case
{
	/** @var \phpbb\language\language */
	protected $language;

	protected function setUp(): void
	{
		parent::setUp();

		global $user;

		$this->language = $this->createMock('\phpbb\language\language');

		$user = new \phpbb\user($this->language, '\phpbb\datetime');
		$user->data = [
			'user_id' => ANONYMOUS,
			'user_form_salt' => 'frontend-manager-salt',
		];
	}

	public function test_get_setup_language_extensions_registers_common_language_file()
	{
		$manager = $this->get_manager(
			$this->createMock('\phpbb\controller\helper'),
			$this->createMock('\phpbb\consentmanager\service\consent_manager_interface'),
			$this->createMock('\phpbb\template\template')
		);

		self::assertSame([
			[
				'ext_name' => 'phpbb/consentmanager',
				'lang_set' => 'common',
			],
		], $manager->get_setup_language_extensions([]));
	}

	public function inject_frontend_assigns_template_payload_data()
	{
		return [
			'front end'  => [true],
			'non front end' => [false],
		];
	}

	/**
	 * @dataProvider inject_frontend_assigns_template_payload_data
	 */
	public function test_inject_frontend_assigns_template_payload($invoke)
	{
		$helper = $this->createMock('\phpbb\controller\helper');
		$helper->expects($invoke ? self::once() : self::never())
			->method('route')
			->with('phpbb_consentmanager_log_controller')
			->willReturn('/app.php/consent/log');

		$this->language->expects($invoke ? self::once() : self::never())
			->method('add_lang')
			->with('common', 'phpbb/consentmanager');

		$consent_manager = $this->createMock('\phpbb\consentmanager\service\consent_manager_interface');
		$consent_manager->expects($invoke ? self::once() : self::never())
			->method('has_optional_categories')
			->willReturn(true);
		$consent_manager->expects($invoke ? self::once() : self::never())
			->method('get_frontend_template_data')
			->with('/app.php/consent/log', generate_link_hash('phpbb.consentmanager.log'))
			->willReturn([
				'S_CONSENTMANAGER_ENABLED' => true,
				'CONSENTMANAGER_PAYLOAD' => '{"version":1}',
			]);
		$consent_manager->expects($invoke ? self::once() : self::never())
			->method('get_frontend_category_data')
			->willReturn([
				[
					'ID'          => 'necessary',
					'LABEL'       => 'Necessary',
					'DESCRIPTION' => 'Required cookies.',
					'REQUIRED'    => true,
					'services'    => [
						[
							'LABEL' => 'Cookie baker',
							'DESCRIPTION' => 'Delicious cookies',
						],
					],
				],
			]);

		$template = $this->createMock('\phpbb\template\template');
		$template->expects($invoke ? self::once() : self::never())
			->method('assign_vars')
			->with([
				'S_CONSENTMANAGER_ENABLED' => true,
				'CONSENTMANAGER_PAYLOAD' => '{"version":1}',
			]);
		$template->expects($invoke ? self::exactly(2) : self::never())
			->method('assign_block_vars')
			->withConsecutive(
				['CONSENTMANAGER_CATEGORIES', [
					'ID'          => 'necessary',
					'LABEL'       => 'Necessary',
					'DESCRIPTION' => 'Required cookies.',
					'REQUIRED'    => true,
					'services'    => [
						[
							'LABEL' => 'Cookie baker',
							'DESCRIPTION' => 'Delicious cookies',
						],
					],
				]],
				['CONSENTMANAGER_CATEGORIES.CONSENTMANAGER_SERVICES', [
					'LABEL' => 'Cookie baker',
					'DESCRIPTION' => 'Delicious cookies',
				]]
			);

		$manager = new class($helper, $this->language, $consent_manager, $template, $invoke) extends \phpbb\consentmanager\service\frontend_manager {
			/** @var bool */
			protected $is_frontend_context;

			public function __construct($helper, $language, $consent_manager, $template, $is_frontend_context)
			{
				parent::__construct($helper, $language, $consent_manager, $template);
				$this->is_frontend_context = $is_frontend_context;
			}

			protected function is_acp_or_installer()
			{
				return !$this->is_frontend_context;
			}
		};

		$manager->inject_frontend();
	}

	public function test_inject_frontend_skips_category_blocks_when_frontend_disabled()
	{
		$helper = $this->createMock('\phpbb\controller\helper');
		$helper->expects(self::never())
			->method('route');

		$this->language->expects(self::never())
			->method('add_lang');

		$consent_manager = $this->createMock('\phpbb\consentmanager\service\consent_manager_interface');
		$consent_manager->expects(self::once())
			->method('has_optional_categories')
			->willReturn(false);
		$consent_manager->expects(self::never())
			->method('get_frontend_template_data');
		$consent_manager->expects(self::never())
			->method('get_frontend_category_data');

		$template = $this->createMock('\phpbb\template\template');
		$template->expects(self::never())
			->method('assign_vars');
		$template->expects(self::never())
			->method('assign_block_vars');

		$manager = $this->get_manager($helper, $consent_manager, $template);
		$manager->inject_frontend();
	}

	protected function get_manager($helper, $consent_manager, $template)
	{
		return new \phpbb\consentmanager\service\frontend_manager(
			$helper,
			$this->language,
			$consent_manager,
			$template
		);
	}
}
