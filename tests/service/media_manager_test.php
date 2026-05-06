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

class media_manager_test extends \phpbb_test_case
{
	public function test_configure_iframe_embeds_skips_when_media_category_is_disabled()
	{
		$consent_manager = $this->createMock('\phpbb\consentmanager\service\consent_manager_interface');
		$consent_manager->expects(self::once())
			->method('is_category_enabled')
			->with('media')
			->willReturn(false);

		$manager = new \phpbb\consentmanager\service\media_manager($consent_manager);

		$configurator = new \s9e\TextFormatter\Configurator();
		$tag = new \s9e\TextFormatter\Configurator\Items\Tag([
			'template' => '<div class="custom-embed"><iframe src="https://video.example.com/embed/123"></iframe></div>',
		]);
		$configurator->tags->add('CUSTOM', $tag);
		$template = $configurator->tags['CUSTOM']->template;

		$manager->configure_iframe_embeds($configurator);

		self::assertSame($template, $configurator->tags['CUSTOM']->template);
	}

	public function test_configure_iframe_embeds_rewrites_xsl_generated_iframe_attributes()
	{
		$consent_manager = $this->createMock('\phpbb\consentmanager\service\consent_manager_interface');
		$consent_manager->expects(self::once())
			->method('is_category_enabled')
			->with('media')
			->willReturn(true);

		$manager = new \phpbb\consentmanager\service\media_manager($consent_manager);

		$configurator = new \s9e\TextFormatter\Configurator();
		$tag = new \s9e\TextFormatter\Configurator\Items\Tag([
			'template' => '<iframe src="https://video.example.com/embed/123">'
				. '<xsl:attribute xmlns:xsl="http://www.w3.org/1999/XSL/Transform" name="onload">boot()</xsl:attribute>'
				. '</iframe>',
		]);
		$configurator->tags->add('CUSTOM_DYNAMIC', $tag);

		$manager->configure_iframe_embeds($configurator);

		$template = $configurator->tags['CUSTOM_DYNAMIC']->template;

		self::assertStringContainsString('$S_CONSENTMANAGER_MEDIA_ALLOWED', $template);
		self::assertStringContainsString('data-consent-media-container="1"', $template);
		self::assertStringContainsString('data-consent-src="https://video.example.com/embed/123"', $template);
		self::assertStringContainsString('data-consent-onload="boot()"', $template);
		self::assertStringContainsString('$L_CONSENTMANAGER_MEDIA_PLACEHOLDER', $template);
		self::assertStringContainsString('data-consent-open-settings="1"', $template);
		self::assertStringContainsString('<iframe src="https://video.example.com/embed/123"', $template);
		self::assertStringContainsString('<iframe src="https://video.example.com/embed/123" onload="boot()"', $template);
	}

	public function test_configure_iframe_embeds_rewrites_mediaembed_iframes()
	{
		$consent_manager = $this->createMock('\phpbb\consentmanager\service\consent_manager_interface');
		$consent_manager->expects(self::once())
			->method('is_category_enabled')
			->with('media')
			->willReturn(true);

		$manager = new \phpbb\consentmanager\service\media_manager($consent_manager);

		$configurator = new \s9e\TextFormatter\Configurator();
		$configurator->plugins->load('MediaEmbed');
		$configurator->MediaEmbed->add('youtube');

		$manager->configure_iframe_embeds($configurator);

		$template = $configurator->tags['YOUTUBE']->template;

		self::assertStringContainsString('$S_CONSENTMANAGER_MEDIA_ALLOWED', $template);
		self::assertStringContainsString('name="data-consent-media-container"', $template);
		self::assertStringContainsString('name="data-consent-src"', $template);
		self::assertStringContainsString('$L_CONSENTMANAGER_MEDIA_PLACEHOLDER', $template);
		self::assertStringContainsString('data-consent-open-settings="1"', $template);
		self::assertStringContainsString('name="src"', $template);
	}

	public function test_configure_iframe_embeds_rewrites_custom_s9e_iframes()
	{
		$consent_manager = $this->createMock('\phpbb\consentmanager\service\consent_manager_interface');
		$consent_manager->expects(self::once())
			->method('is_category_enabled')
			->with('media')
			->willReturn(true);

		$manager = new \phpbb\consentmanager\service\media_manager($consent_manager);

		$configurator = new \s9e\TextFormatter\Configurator();
		$tag = new \s9e\TextFormatter\Configurator\Items\Tag([
			'template' => '<div class="custom-embed"><iframe src="https://video.example.com/embed/123"></iframe></div>',
		]);
		$configurator->tags->add('CUSTOM', $tag);

		$manager->configure_iframe_embeds($configurator);

		$template = $configurator->tags['CUSTOM']->template;

		self::assertStringContainsString('$S_CONSENTMANAGER_MEDIA_ALLOWED', $template);
		self::assertStringContainsString('data-consent-media-container="1"', $template);
		self::assertStringContainsString('data-consent-src="https://video.example.com/embed/123"', $template);
		self::assertStringContainsString('class="custom-embed consent-manager-media-content"', $template);
		self::assertStringContainsString('<iframe src="https://video.example.com/embed/123"', $template);
	}

	public function test_configure_iframe_renderer_sets_media_allowed_parameter()
	{
		$inner_renderer = $this->createMock('\s9e\TextFormatter\Renderer');
		$inner_renderer->expects(self::once())
			->method('setParameter')
			->with('S_CONSENTMANAGER_MEDIA_ALLOWED', '1');

		$renderer = $this->getMockBuilder('\phpbb\textformatter\s9e\renderer')
			->disableOriginalConstructor()
			->setMethods(['get_renderer'])
			->getMock();
		$renderer->expects(self::once())
			->method('get_renderer')
			->willReturn($inner_renderer);

		$consent_manager = $this->createMock('\phpbb\consentmanager\service\consent_manager_interface');
		$consent_manager->expects(self::once())
			->method('has_server_consent')
			->with('media')
			->willReturn(true);

		$manager = new \phpbb\consentmanager\service\media_manager($consent_manager);
		$manager->configure_iframe_renderer($renderer);
	}
}
