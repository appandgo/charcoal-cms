<?php

namespace Charcoal\Property;

// From 'charcoal-property'
use \Charcoal\Property\StructureProperty;

/**
 * Object Property holds a reference to an external object.
 *
 * The object property implements the full `SelectablePropertyInterface` without using
 * its accompanying trait. (`set_choices`, `add_choice`, `choices`, `has_choice`, `choice`).
 */
class TemplateOptionsProperty extends StructureProperty
{
    /**
     * Retrieve the property's type identifier.
     *
     * @return string
     */
    public function type()
    {
        return 'template-options';
    }
}
