<?php

namespace Charcoal\Cms;

use InvalidArgumentException;

// From 'charcoal-core'
use Charcoal\Model\Collection;
use Charcoal\Loader\CollectionLoader;

// From 'charcoal-object'
use Charcoal\Object\Content;
use Charcoal\Object\HierarchicalInterface;
use Charcoal\Object\HierarchicalTrait;
use Charcoal\Object\RoutableInterface;
use Charcoal\Object\RoutableTrait;

// From 'charcoal-translator'
use Charcoal\Translator\Translation;

// From 'charcoal-cms'
use Charcoal\Cms\MetatagInterface;
use Charcoal\Cms\SearchableInterface;
use Charcoal\Cms\SectionInterface;
use Charcoal\Cms\TemplateableInterface;

/**
 * A Section is a unique, reachable page.
 *
 * ## Types of sections
 * There can be different types of ection. 4 exists in the CMS module:
 * - `blocks`
 * - `content`
 * - `empty`
 * - `external`
 *
 * ## External implementations
 * Sections imlpement the following _Interface_ / _Trait_:
 * - From the `Charcoal\Object` namespace (in `charcoal-base`)
 *   - `Hierarchical`
 *   - `Routable`
 * - From the local `Charcoal\Cms` namespace
 *   - `Metatag`
 *   - `Searchable`
 *
 */
abstract class AbstractSection extends Content implements
    HierarchicalInterface,
    MetatagInterface,
    RoutableInterface,
    SearchableInterface,
    SectionInterface,
    TemplateableInterface
{
    use HierarchicalTrait;
    use MetatagTrait;
    use RoutableTrait;
    use SearchableTrait;
    use TemplateableTrait;

    const TYPE_BLOCKS   = 'charcoal/cms/section/blocks';
    const TYPE_CONTENT  = 'charcoal/cms/section/content';
    const TYPE_EMPTY    = 'charcoal/cms/section/empty';
    const TYPE_EXTERNAL = 'charcoal/cms/section/external';
    const DEFAULT_TYPE  = self::TYPE_CONTENT;

    /**
     * @var string
     */
    private $sectionType = self::DEFAULT_TYPE;

    /**
     * @var Translation|string|null
     */
    private $title;

    /**
     * @var Translation|string|null
     */
    private $subtitle;

    /**
     * @var Translation|string|null
     */
    private $content;

    /**
     * @var Translation|string|null
     */
    private $image;

    /**
     * @var array $attachments
     */
    private $attachments;

    /**
     * Set the section's type.
     *
     * @param  string $type The section type.
     * @throws InvalidArgumentException If the section type is not a string or not a valid section type.
     * @return self
     */
    public function setSectionType($type)
    {
        if (!is_string($type)) {
            throw new InvalidArgumentException(
                'Section type must be a string'
            );
        }

        $validTypes = $this->acceptedSectionTypes();
        if (!in_array($type, $validTypes)) {
            throw new InvalidArgumentException(
                'Section type is not valid'
            );
        }

        $this->sectionType = $type;

        return $this;
    }

    /**
     * Retrieve the available section types.
     *
     * @return array
     */
    public function acceptedSectionTypes()
    {
        return [
            self::TYPE_CONTENT,
            self::TYPE_EMPTY,
            self::TYPE_EXTERNAL
        ];
    }

    /**
     * Retrieve the section's type.
     *
     * @return string
     */
    public function sectionType()
    {
        return $this->sectionType;
    }

    /**
     * @param  mixed $title The section title (localized).
     * @return self
     */
    public function setTitle($title)
    {
        $this->title = $this->translator()->translation($title);
        return $this;
    }

    /**
     * @return Translation|string|null
     */
    public function title()
    {
        return $this->title;
    }

    /**
     * @param  mixed $subtitle The section subtitle (localized).
     * @return self
     */
    public function setSubtitle($subtitle)
    {
        $this->subtitle = $this->translator()->translation($subtitle);
        return $this;
    }

    /**
     * @return Translation|string|null
     */
    public function subtitle()
    {
        return $this->subtitle;
    }

    /**
     * @param  mixed $content The section content (localized).
     * @return self
     */
    public function setContent($content)
    {
        $this->content = $this->translator()->translation($content);
        return $this;
    }

    /**
     * @return Translation|string|null
     */
    public function content()
    {
        return $this->content;
    }

    /**
     * @param  mixed $image The section main image (localized).
     * @return self
     */
    public function setImage($image)
    {
        $this->image = $this->translator()->translation($image);
        return $this;
    }

    /**
     * @return Translation|string|null
     */
    public function image()
    {
        return $this->image;
    }

    /**
     * @param  string $type Optional type.
     * @return array
     */
    public function attachments($type = null)
    {
        if (!$this->attachments) {
            $this->attachments = $this->loadAttachments();
        }
        if ($type) {
            // Return only the attachments of a certain type.
            return $this->attachments[$type];
        } else {
            // Return all attachments, grouped by types.
            return $this->attachments;
        }
    }

    /**
     * @return array
     */
    public function loadAttachments()
    {
        return [];
    }

    /**
     * HierarchicalTrait > loadChildren
     *
     * @return Collection
     */
    public function loadChildren()
    {
        $loader = new CollectionLoader([
            'logger'  => $this->logger,
            'factory' => $this->modelFactory()
        ]);
        $loader->setModel($this);
        $loader->addFilter([
            'property' => 'master',
            'val'      => $this->id()
        ]);
        $loader->addFilter([
            'property' => 'active',
            'val'      => true
        ]);

        $loader->addOrder([
            'property' => 'position',
            'mode'     => 'asc'
        ]);
        return $loader->load();
    }

    /**
     * MetatagTrait > canonicalUrl
     *
     * @todo
     * @return string
     */
    public function canonicalUrl()
    {
        return '';
    }

    /**
     * @return Translation|string|null
     */
    public function defaultMetaTitle()
    {
        return $this->title();
    }

    /**
     * @return Translation|string|null
     */
    public function defaultMetaDescription()
    {
        $content = $this->translator()->translation($this->content());
        if ($content instanceof Translation) {
            $desc = [];
            foreach ($content->data() as $lang => $text) {
                $desc[$lang] = strip_tags($text);
            }

            return $this->translator()->translation($desc);
        }

        return null;
    }

    /**
     * @return Translation|string|null
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
        $this->setSlug($this->generateSlug());
        $this->generateDefaultMetaTags();
        return parent::preUpdate($properties);
    }
}
