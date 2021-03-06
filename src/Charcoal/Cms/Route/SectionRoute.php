<?php

namespace Charcoal\Cms\Route;

// From Pimple
use Pimple\Container;

// From PSR-7
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

// From 'charcoal-translator'
use Charcoal\Translator\TranslatorAwareTrait;

// From 'charcoal-app'
use Charcoal\App\Route\TemplateRoute;

// From 'charcoal-cms'
use Charcoal\Cms\SectionInterface;
use Charcoal\Object\RoutableInterface;

/**
 * Section Route Handler
 */
class SectionRoute extends TemplateRoute
{
    use TranslatorAwareTrait;

    /**
     * URI path.
     *
     * @var string
     */
    private $path;

    /**
     * The section object matching the URI path.
     *
     * @var SectionInterface|RoutableInterface
     */
    private $section;

    /**
     * The section model.
     *
     * @var string
     */
    private $objType = 'charcoal/cms/section';

    /**
     * @param array $data Class depdendencies.
     */
    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->path = ltrim($data['path'], '/');
    }

    /**
     * Determine if the URI path resolves to an object.
     *
     * @param  Container $container A DI (Pimple) container.
     * @return boolean
     */
    public function pathResolvable(Container $container)
    {
        $section = $this->loadSectionFromPath($container);
        return ($section instanceof SectionInterface) && $section->id();
    }

    /**
     * @param  Container         $container A DI (Pimple) container.
     * @param  RequestInterface  $request   A PSR-7 compatible Request instance.
     * @param  ResponseInterface $response  A PSR-7 compatible Response instance.
     * @return ResponseInterface
     */
    public function __invoke(
        Container $container,
        RequestInterface $request,
        ResponseInterface $response
    ) {
        $config = $this->config();

        $section = $this->loadSectionFromPath($container);

        if (!$section) {
            return $response->withStatus(404);
        }

        $templateIdent      = (string)$section->templateIdent();
        $templateController = (string)$section->templateIdent();

        if (!$templateController) {
            return $response->withStatus(404);
        }

        $templateFactory = $container['template/factory'];
        $templateFactory->setDefaultClass($config['default_controller']);

        $template = $templateFactory->create($templateController);
        $template->init($request);

        // Set custom data from config.
        $template->setData($config['template_data']);
        $template->setSection($section);

        $templateContent = $container['view']->render($templateIdent, $template);

        $response->write($templateContent);

        return $response;
    }

    /**
     * @todo   Add support for `@see setlocale()`; {@see GenericRoute::setLocale()}
     * @param  Container $container Pimple DI container.
     * @return SectionInterface|null
     */
    protected function loadSectionFromPath(Container $container)
    {
        if (!$this->section) {
            $config = $this->config();
            $type   = (isset($config['obj_type']) ? $config['obj_type'] : $this->objType);
            $model  = $container['model/factory']->create($type);

            $langs = $container['translator']->availableLocales();
            $lang  = $model->loadFromL10n('slug', $this->path, $langs);
            $container['translator']->setLocale($lang);

            if ($model->id()) {
                $this->section = $model;
            }
        }

        return $this->section;
    }
}
