<?php

namespace Charcoal\Property;

use PDO;

use RuntimeException;
use InvalidArgumentException;

// From Pimple
use Pimple\Container;

// From 'charcoal-cms'
use Charcoal\Property\SelectablePropertyInterface;
use Charcoal\Property\SelectablePropertyTrait;

/**
 * Object Property holds a reference to an external object.
 *
 * The object property implements the full `SelectablePropertyInterface` without using
 * its accompanying trait. (`set_choices`, `add_choice`, `choices`, `has_choice`, `choice`).
 */
class TemplateProperty extends AbstractProperty implements SelectablePropertyInterface
{
    use SelectablePropertyTrait;

    /**
     * Inject dependencies from a DI Container.
     *
     * @param  Container $container A dependencies container instance.
     * @return void
     */
    public function setDependencies(Container $container)
    {
        parent::setDependencies($container);

        $this->setChoices($container['config']['templates']);
    }

    /**
     * @return string
     */
    public function type()
    {
        return 'template';
    }

    /**
     * Retrieve the selected template's fully-qualified class name.
     *
     * @return string|null
     */
    public function __toString()
    {
        $val = $this->val();
        $tpl = $this->choice($val);

        if (isset($tpl['class'])) {
            return $tpl['class'];
        }

        return null;
    }

    /**
     * @return string
     */
    public function sqlExtra()
    {
        return '';
    }

    /**
     * Get the SQL type (Storage format)
     *
     * Stored as `VARCHAR` for maxLength under 255 and `TEXT` for other, longer strings
     *
     * @return string The SQL type
     */
    public function sqlType()
    {
        // Multiple strings are always stored as TEXT because they can hold multiple values
        if ($this->multiple()) {
            return 'TEXT';
        }

        return 'VARCHAR(255)';
    }

    /**
     * @return integer
     */
    public function sqlPdoType()
    {
        return PDO::PARAM_STR;
    }
}
