<?php
/**
 *
 * Consent Manager extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\consentmanager\event;

use phpbb\consentmanager\service\frontend_manager;
use phpbb\consentmanager\service\media_manager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	/** @var frontend_manager */
	protected $frontend_manager;

	/** @var media_manager */
	protected $media_manager;

	/**
	 * Constructor.
	 *
	 * @param frontend_manager $frontend_manager Frontend manager
	 * @param media_manager    $media_manager Media manager
	 */
	public function __construct(frontend_manager $frontend_manager, media_manager $media_manager)
	{
		$this->frontend_manager = $frontend_manager;
		$this->media_manager = $media_manager;
	}

	/**
	 * Return the subscribed phpBB events.
	 *
	 * @return array
	 */
	public static function getSubscribedEvents()
	{
		return [
			'core.user_setup' => 'load_language_on_setup',
			'core.text_formatter_s9e_configure_after' => [['configure_iframe_embeds', -10]],
			'core.text_formatter_s9e_renderer_setup' => 'configure_iframe_renderer',
			'core.page_header_after' => 'inject_frontend',
		];
	}

	/**
	 * Load common language strings early enough for s9e-rendered placeholders.
	 *
	 * @param \phpbb\event\data $event Event data
	 *
	 * @return void
	 */
	public function load_language_on_setup($event)
	{
		$event['lang_set_ext'] = $this->frontend_manager->get_setup_language_extensions($event['lang_set_ext']);
	}

	/**
	 * Transform s9e-rendered iframe output into consent-aware placeholders.
	 *
	 * @param \phpbb\event\data $event Event data
	 *
	 * @return void
	 */
	public function configure_iframe_embeds($event)
	{
		$this->media_manager->configure_iframe_embeds($event['configurator']);
	}

	/**
	 * Pass the current request's media consent state into the s9e renderer.
	 *
	 * @param \phpbb\event\data $event Event data
	 *
	 * @return void
	 */
	public function configure_iframe_renderer($event)
	{
		$this->media_manager->configure_iframe_renderer($event['renderer']);
	}

	/**
	 * Inject consent manager frontend data on board pages.
	 *
	 * @return void
	 */
	public function inject_frontend()
	{
		$this->frontend_manager->inject_frontend();
	}
}
