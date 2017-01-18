<?php

namespace Charcoal\Cms;

use \DateTime;
use \DateTimeInterface;
use \Exception;
use \InvalidArgumentException;

use \Psr\Http\Message\RequestInterface;
use \Psr\Http\Message\ResponseInterface;

// Module `charcoal-base` dependencies
use \Charcoal\Object\Content;
use \Charcoal\Object\CategorizableInterface;
use \Charcoal\Object\CategorizableTrait;
use \Charcoal\Object\PublishableInterface;
use \Charcoal\Object\PublishableTrait;
use \Charcoal\Object\RoutableInterface;
use \Charcoal\Object\RoutableTrait;

// Module `charcoal-translation` depdencies
use \Charcoal\Translation\TranslationString;

// Intra-module (`charcoal-cms`) dependencies
use \Charcoal\Cms\MetatagInterface;
use \Charcoal\Cms\SearchableInterface;
use \Charcoal\Cms\TemplateableInterface;

/**
 *
 */
abstract class AbstractEvent extends Content implements
    CategorizableInterface,
    EventInterface,
    MetatagInterface,
    PublishableInterface,
    RoutableInterface,
    SearchableInterface,
    TemplateableInterface
{
    use CategorizableTrait;
    use PublishableTrait;
    use MetatagTrait;
    use RoutableTrait;
    use SearchableTrait;
    use TemplateableTrait;

    /**
     * @var TranslationString $title
     */
    private $title;

    /**
     * @var TranslationString $subtitle
     */
    private $subtitle;

    /**
     * @var TranslationString $summary
     */
    private $summary;

    /**
     * @var TranslationString $content
     */
    private $content;

    /**
     * @var TranslationString $image
     */
    private $image;

    /**
     * @var DateTime $startDate
     */
    private $startDate;

    /**
     * @var DateTime $startDate
     */
    private $endDate;

    /**
     * @param mixed $title The event title (localized).
     * @return EventInterface Chainable
     */
    public function setTitle($title)
    {
        $this->title = new TranslationString($title);

        return $this;
    }

    /**
     * @return TranslationString
     */
    public function title()
    {
        return $this->title;
    }

    /**
     * @param mixed $subtitle The event subtitle (localized).
     * @return EventInterface Chainable
     */
    public function setSubtitle($subtitle)
    {
        $this->subtitle = new TranslationString($subtitle);

        return $this;
    }

    /**
     * @return TranslationString
     */
    public function subtitle()
    {
        return $this->subtitle;
    }

    /**
     * @param mixed $summary The news summary (localized).
     * @return EventInterface Chainable
     */
    public function setSummary($summary)
    {
        $this->summary = new TranslationString($summary);

        return $this;
    }

    /**
     * @return TranslationString
     */
    public function summary()
    {
        return $this->summary;
    }

    /**
     * @param mixed $content The event content (localized).
     * @return EventInterface Chainable
     */
    public function setContent($content)
    {
        $this->content = new TranslationString($content);

        return $this;
    }

    /**
     * @return TranslationString
     */
    public function content()
    {
        return $this->content;
    }

    /**
     * @param mixed $image The section main image (localized).
     * @return EventInterface Chainable
     */
    public function setImage($image)
    {
        $this->image = new TranslationString($image);

        return $this;
    }

    /**
     * @return TranslationString
     */
    public function image()
    {
        return $this->image;
    }

    /**
     * @param string|DateTime $startDate Event starting date.
     * @throws InvalidArgumentException If the timestamp is invalid.
     * @return EventInterface Chainable
     */
    public function setStartDate($startDate)
    {
        if ($startDate === null || $startDate === '') {
            $this->startDate = null;

            return $this;
        }
        if (is_string($startDate)) {
            $startDate = new DateTime($startDate);
        }
        if (!($startDate instanceof DateTimeInterface)) {
            throw new InvalidArgumentException(
                'Invalid "Start Date" value. Must be a date/time string or a DateTime object.'
            );
        }
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function startDate()
    {
        return $this->startDate;
    }

    /**
     * @param string|DateTime $endDate Event end date.
     * @throws InvalidArgumentException If the timestamp is invalid.
     * @return EventInterface Chainable
     */
    public function setEndDate($endDate)
    {
        if ($endDate === null || $endDate === '') {
            $this->endDate = null;

            return $this;
        }
        if (is_string($endDate)) {
            $endDate = new DateTime($endDate);
        }
        if (!($endDate instanceof DateTimeInterface)) {
            throw new InvalidArgumentException(
                'Invalid "End Date" value. Must be a date/time string or a DateTime object.'
            );
        }
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function endDate()
    {
        return $this->endDate;
    }

    /**
     * MetatagTrait > canonical_url
     *
     * @return string
     * @todo
     */
    public function canonicalUrl()
    {
        return '';
    }

    /**
     * @return TranslationString
     */
    public function defaultMetaTitle()
    {
        return $this->title();
    }

    /**
     * @return TranslationString
     */
    public function defaultMetaDescription()
    {
        $content = $this->content();

        if (!($content instanceof TranslationString)) {
            $content = new TranslationString($content);
        }

        $out = [];
        foreach ($content->all() as $lang => $text) {
            $out[$lang] = strip_tags($text);
        }

        // Don't affect the content's content.
        return new TranslationString($out);
    }

    /**
     * @return TranslationString
     */
    public function defaultMetaImage()
    {
        return $this->image();
    }

    /**
     * {@inheritdoc}
     *
     * @return boolean
     */
    public function preSave()
    {
        $this->setSlug($this->generateSlug());
        $this->generateDefaultMetaTags();

        return parent::preSave();
    }

    /**
     * {@inheritdoc}
     *
     * @param array $properties Optional properties to update.
     * @return boolean
     */
    public function preUpdate(array $properties = null)
    {
        if (!$this->slug) {
            $this->setSlug($this->generateSlug());
        }
        $this->generateDefaultMetaTags();

        return parent::preUpdate($properties);
    }
}