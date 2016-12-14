<?php

namespace Charcoal\Cms\Mixin\Traits;


/**
 * An implementation, as Trait, of the `HasContentBlocksInterface`.
 *
 * @uses Charcoal\Support\Property\ParsableValueTrait
 * @uses Charcoal\Attachment\Traits\AttachmentAwareTrait
 */
class HasContentBlockTrait
{
    /**
     * Retrieve this object's content blocks.
     *
     * @return Collection|Attachment[]
     */
    public function contentBlocks()
    {
        return $this->attachments('contents');
    }

    /**
     * Determine if this object has any content blocks.
     *
     * @return boolean
     */
    public function hasContentBlocks()
    {
        return !!($this->numContentBlocks());
    }

    /**
     * Count the number of content blocks associated to this object.
     *
     * @return integer
     */
    public function numContentBlocks()
    {
        return count($this->contentBlocks());
    }
}
