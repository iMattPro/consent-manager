<?php
/**
 *
 * Consent Manager extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\consentmanager\tests\event;

class listener_test extends \phpbb_test_case
{
	public function test_get_subscribed_events()
	{
		self::assertSame([
			'core.user_setup' => 'load_language_on_setup',
			'core.text_formatter_s9e_configure_after' => [['configure_iframe_embeds', -10]],
			'core.text_formatter_s9e_renderer_setup' => 'configure_iframe_renderer',
			'core.page_header_after' => 'inject_frontend',
		], \phpbb\consentmanager\event\listener::getSubscribedEvents());
	}

	public function test_load_language_on_setup_delegates_to_frontend_manager()
	{
		$frontend_manager = $this->createMock('\phpbb\consentmanager\service\frontend_manager');
		$frontend_manager->expects(self::once())
			->method('get_setup_language_extensions')
			->with([['ext_name' => 'phpbb/example', 'lang_set' => 'common']])
			->willReturn([['ext_name' => 'phpbb/consentmanager', 'lang_set' => 'common']]);

		$listener = new \phpbb\consentmanager\event\listener(
			$frontend_manager,
			$this->createMock('\phpbb\consentmanager\service\media_manager')
		);

		$event = new \phpbb\event\data([
			'lang_set_ext' => [['ext_name' => 'phpbb/example', 'lang_set' => 'common']],
		]);

		$listener->load_language_on_setup($event);

		self::assertSame([['ext_name' => 'phpbb/consentmanager', 'lang_set' => 'common']], $event['lang_set_ext']);
	}

	public function test_configure_iframe_embeds_delegates_to_media_manager()
	{
		$configurator = new \s9e\TextFormatter\Configurator();

		$media_manager = $this->createMock('\phpbb\consentmanager\service\media_manager');
		$media_manager->expects(self::once())
			->method('configure_iframe_embeds')
			->with($configurator);

		$listener = new \phpbb\consentmanager\event\listener(
			$this->createMock('\phpbb\consentmanager\service\frontend_manager'),
			$media_manager
		);

		$listener->configure_iframe_embeds(new \phpbb\event\data([
			'configurator' => $configurator,
		]));
	}

	public function test_configure_iframe_renderer_delegates_to_media_manager()
	{
		$renderer = $this->getMockBuilder('\phpbb\textformatter\s9e\renderer')
			->disableOriginalConstructor()
			->getMock();

		$media_manager = $this->createMock('\phpbb\consentmanager\service\media_manager');
		$media_manager->expects(self::once())
			->method('configure_iframe_renderer')
			->with($renderer);

		$listener = new \phpbb\consentmanager\event\listener(
			$this->createMock('\phpbb\consentmanager\service\frontend_manager'),
			$media_manager
		);

		$listener->configure_iframe_renderer(new \phpbb\event\data([
			'renderer' => $renderer,
		]));
	}

	public function test_inject_frontend_delegates_to_frontend_manager()
	{
		$frontend_manager = $this->createMock('\phpbb\consentmanager\service\frontend_manager');
		$frontend_manager->expects(self::once())
			->method('inject_frontend');

		$listener = new \phpbb\consentmanager\event\listener(
			$frontend_manager,
			$this->createMock('\phpbb\consentmanager\service\media_manager')
		);

		$listener->inject_frontend();
	}
}
