<?php

namespace Charcoal\Cms;

// From 'charcoal-translator'
use Charcoal\Translator\Translation;

/**
 *
 */
interface DocumentInterface
{
    /**
     * @param  mixed $name The document name (localized).
     * @return self
     */
    public function setName($name);

    /**
     * @return Translation|string|null
     */
    public function name();

    /**
     * @param  string $file The file relative path / url (localized).
     * @return self
     */
    public function setFile($file);

    /**
     * @return string
     */
    public function file();

    /**
     * @return string
     */
    public function path();

    /**
     * @return string
     */
    public function url();

    /**
     * @return string
     */
    public function mimetype();

    /**
     * Get the filename (basename; without any path segment).
     *
     * @return string
     */
    public function filename();

    /**
     * Get the document's file size, in bytes.
     *
     * @return integer
     */
    public function filesize();
}
