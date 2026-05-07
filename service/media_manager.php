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

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Helpers\TemplateLoader;

class media_manager
{
	public const MEDIA_CATEGORY = 'media';
	public const MEDIA_ALLOWED_PARAMETER = 'S_CONSENTMANAGER_MEDIA_ALLOWED';
	public const XSL_NAMESPACE = 'http://www.w3.org/1999/XSL/Transform';

	/** @var consent_manager_interface */
	protected $consent_manager;

	/**
	 * Constructor.
	 *
	 * @param consent_manager_interface $consent_manager Consent manager service
	 */
	public function __construct(consent_manager_interface $consent_manager)
	{
		$this->consent_manager = $consent_manager;
	}

	/**
	 * Transform s9e-rendered iframe output into consent-aware placeholders.
	 *
	 * @param Configurator $configurator Text formatter configurator
	 *
	 * @return void
	 */
	public function configure_iframe_embeds(Configurator $configurator)
	{
		if (!$this->consent_manager->is_category_enabled(self::MEDIA_CATEGORY))
		{
			return;
		}

		foreach ($configurator->tags as $tag)
		{
			if (empty($tag->template))
			{
				continue;
			}

			$template = $this->build_iframe_placeholder_template($tag->template);
			if ($template === $tag->template)
			{
				continue;
			}

			$tag->template = $template;
			$configurator->templateNormalizer->normalizeTag($tag);
			$configurator->templateChecker->checkTag($tag);
		}
	}

	/**
	 * Pass the current request's media consent state into the s9e renderer.
	 *
	 * @param \phpbb\textformatter\s9e\renderer $renderer phpBB renderer wrapper
	 *
	 * @return void
	 */
	public function configure_iframe_renderer($renderer)
	{
		$renderer->get_renderer()->setParameter(
			self::MEDIA_ALLOWED_PARAMETER,
			$this->consent_manager->has_server_consent(self::MEDIA_CATEGORY) ? '1' : ''
		);
	}

	/**
	 * Rewrite an s9e template so iframe src attributes are deferred until consent is granted.
	 *
	 * @param string $template Original s9e template
	 *
	 * @return string
	 */
	protected function build_iframe_placeholder_template($template)
	{
		$dom = TemplateLoader::load($template);
		$xpath = new \DOMXPath($dom);
		$xpath->registerNamespace('xsl', self::XSL_NAMESPACE);

		$iframes = $xpath->query('//*[local-name() = "iframe" and namespace-uri() != "' . self::XSL_NAMESPACE . '"]');
		if (!$iframes || !$iframes->length)
		{
			return $template;
		}

		$original_template = $this->save_template($dom, true);

		foreach ($iframes as $iframe)
		{
			if ($iframe->hasAttribute('src'))
			{
				$iframe->setAttribute('data-consent-src', $iframe->getAttribute('src'));
				$iframe->removeAttribute('src');
			}

			if ($iframe->hasAttribute('onload'))
			{
				$iframe->setAttribute('data-consent-onload', $iframe->getAttribute('onload'));
				$iframe->removeAttribute('onload');
			}

			foreach ($xpath->query('./xsl:attribute[@name = "src" or @name = "onload"]', $iframe) as $dynamic_attribute)
			{
				$dynamic_attribute->setAttribute('name', 'data-consent-' . $dynamic_attribute->getAttribute('name'));
			}

			$iframe->setAttribute('data-consent-media-frame', '1');
		}

		$media_roots = $xpath->query(
			'//*[namespace-uri() != "' . self::XSL_NAMESPACE . '"]'
			. '[not(ancestor::*[namespace-uri() != "' . self::XSL_NAMESPACE . '"])]'
			. '[self::iframe or descendant::*[local-name() = "iframe" and namespace-uri() != "' . self::XSL_NAMESPACE . '"]]'
		);
		if (!$media_roots || !$media_roots->length)
		{
			return $template;
		}

		$nodes = [];
		foreach ($media_roots as $media_root)
		{
			$nodes[] = $media_root;
		}

		foreach ($nodes as $media_root)
		{
			$container = $dom->createElement('span');
			$container->setAttribute('class', 'consent-manager-media-embed');
			$container->setAttribute('data-consent-media-container', '1');
			$container->setAttribute('data-consent-category', self::MEDIA_CATEGORY);

			$placeholder = $dom->createDocumentFragment();
			$placeholder->appendXML($this->get_media_placeholder_markup());

			$this->append_class($media_root, 'consent-manager-media-content');
			$media_root->setAttribute('data-consent-media-content', '1');
			$media_root->setAttribute('hidden', 'hidden');

			$parent = $media_root->parentNode;
			$parent->replaceChild($container, $media_root);
			$container->appendChild($placeholder);
			$container->appendChild($media_root);
		}

		$blocked_template = $this->save_template($dom, true);

		return '<xsl:choose>'
			. '<xsl:when test="$' . self::MEDIA_ALLOWED_PARAMETER . '">' . $original_template . '</xsl:when>'
			. '<xsl:otherwise>' . $blocked_template . '</xsl:otherwise>'
			. '</xsl:choose>';
	}

	/**
	 * Save a template DOM, optionally stripping unsupported internal s9e attributes first.
	 *
	 * @param \DOMDocument $dom Template DOM
	 * @param bool         $strip_internal_attributes Whether to strip s9e internal attributes
	 *
	 * @return string
	 */
	protected function save_template(\DOMDocument $dom, $strip_internal_attributes = false)
	{
		$template_dom = $strip_internal_attributes ? clone $dom : $dom;

		if ($strip_internal_attributes)
		{
			$this->strip_internal_s9e_attributes($template_dom);
		}

		return TemplateLoader::save($template_dom);
	}

	/**
	 * Remove s9e internal data attributes that are not valid in rewritten templates.
	 *
	 * @param \DOMDocument $dom Template DOM
	 *
	 * @return void
	 */
	protected function strip_internal_s9e_attributes(\DOMDocument $dom)
	{
		$xpath = new \DOMXPath($dom);

		foreach ($xpath->query('//@*[starts-with(name(), "data-s9e-")]') as $attribute)
		{
			$attribute->ownerElement->removeAttributeNode($attribute);
		}
	}

	/**
	 * Return the XSL markup used for blocked media placeholders.
	 *
	 * @return string
	 */
	protected function get_media_placeholder_markup()
	{
		return '<span class="consent-manager-media-placeholder" data-consent-media-placeholder="1">'
			. '<span class="consent-manager-media-placeholder-copy"></span>'
			. '</span>';
	}

	/**
	 * Append a CSS class to a DOM element without duplicating existing values.
	 *
	 * @param \DOMElement $element DOM element
	 * @param string      $class   Class name to append
	 *
	 * @return void
	 */
	protected function append_class(\DOMElement $element, $class)
	{
		$classes = preg_split('/\s+/', trim($element->getAttribute('class')));
		if (!$classes || $classes === [''])
		{
			$classes = [];
		}

		if (!in_array($class, $classes, true))
		{
			$classes[] = $class;
		}

		$element->setAttribute('class', trim(implode(' ', $classes)));
	}
}
