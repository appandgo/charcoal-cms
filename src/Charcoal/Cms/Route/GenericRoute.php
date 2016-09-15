<?php

namespace Charcoal\Cms\Route;

use \Exception;

use \Pimple\Container;

// From PSR-7 (HTTP Messaging)
use \Psr\Http\Message\RequestInterface;
use \Psr\Http\Message\ResponseInterface;

// Dependency from 'charcoal-app'
use \Charcoal\App\Route\TemplateRoute;

// Dependency from 'charcoal-cms'
use \Charcoal\Cms\TemplateableInterface;

// From 'charcoal-factory'
use \Charcoal\Factory\FactoryInterface;

// From 'charcoal-core'
use \Charcoal\Model\ModelInterface;
use \Charcoal\Loader\CollectionLoader;

// From 'charcoal-translation'
use \Charcoal\Translation\TranslationConfig;

// Local Dependencies
use \Charcoal\Object\ObjectRoute;
use \Charcoal\Object\ObjectRouteInterface;
use \Charcoal\Object\RoutableInterface;

/**
 * Generic Object Route Handler
 *
 * Uses implementations of {@see \Charcoal\Object\ObjectRouteInterface}
 * to match routes for catch-all routing patterns.
 */
class GenericRoute extends TemplateRoute
{
    /**
     * The URI path.
     *
     * @var string
     */
    private $path;

    /**
     * The object route.
     *
     * @var ObjectRouteInterface
     */
    private $objectRoute;

    /**
     * The target object of the {@see self::$objectRoute}.
     *
     * @var ModelInterface|RoutableInterface
     */
    private $contextObject;

    /**
     * Store the factory instance for the current class.
     *
     * @var FactoryInterface
     */
    private $modelFactory;

    /**
     * Store the collection loader for the current class.
     *
     * @var CollectionLoader
     */
    private $collectionLoader;

    /**
     * The class name of the object route model.
     *
     * Must be a fully-qualified PHP namespace and an implementation of
     * {@see \Charcoal\Object\ObjectRouteInterface}. Used by the model factory.
     *
     * @var string
     */
    protected $objectRouteClass = ObjectRoute::class;

    /**
     * Returns new template route object.
     *
     * @param array|\ArrayAccess $data Class depdendencies.
     */
    public function __construct($data)
    {
        parent::__construct($data);

        $this->setPath(ltrim($data['path'], '/'));
    }

    /**
     * Inject dependencies from a DI Container.
     *
     * @param  Container $container A dependencies container instance.
     * @return void
     */
    public function setDependencies(Container $container)
    {
        $this->setModelFactory($container['model/factory']);
        $this->setCollectionLoader($container['model/collection/loader']);
    }

    /**
     * Determine if the URI path resolves to an object.
     *
     * @param  Container $container A DI (Pimple) container.
     * @return boolean
     */
    public function pathResolvable(Container $container)
    {
        $this->setDependencies($container);

        $object = $this->loadObjectRouteFromPath();
        if (!$object->id()) {
            return false;
        }

        $contextObject = $this->loadContextObject();

        if (!$contextObject || !$contextObject->id()) {
            return false;
        }

        return !!$contextObject->active();
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
        $objectRoute   = $this->loadObjectRouteFromPath();
        $contextObject = $this->loadContextObject();
        $translator    = TranslationConfig::instance();

        $translator->setCurrentLanguage($objectRoute->lang());

        $templateIdent = (string)$contextObject->templateIdent();

        $config = [
            'template'   => $templateIdent,
            'controller' => $templateIdent
        ];

        if ($contextObject instanceof TemplateableInterface) {
            $templateOptions = $contextObject->templateOptions();
            if ($templateOptions) {
                $config['template_data'] = $templateOptions;
            }
        }

        $this->setConfig($config);

        $templateContent = $this->templateContent($container, $request);

        $response->write($templateContent);

        return $response;
    }

    /**
     * @param  Container        $container A DI (Pimple) container.
     * @param  RequestInterface $request   The request to intialize the template with.
     * @return string
     */
    protected function createTemplate(Container $container, RequestInterface $request)
    {
        $template = parent::createTemplate($container, $request);

        $contextObject = $this->loadContextObject();
        $template->setContextObject($contextObject);

        return $template;
    }

    /**
     * Create a route object.
     *
     * @return ObjectRouteInterface
     */
    public function createRouteObject()
    {
        $route = $this->modelFactory()->create($this->objectRouteClass());

        return $route;
    }

    /**
     * Set the class name of the object route model.
     *
     * @param  string $className The class name of the object route model.
     * @throws InvalidArgumentException If the class name is not a string.
     * @return AbstractPropertyDisplay Chainable
     */
    protected function setObjectRouteClass($className)
    {
        if (!is_string($className)) {
            throw new InvalidArgumentException(
                'Route class name must be a string.'
            );
        }

        $this->objectRouteClass = $className;

        return $this;
    }

    /**
     * Retrieve the class name of the object route model.
     *
     * @return string
     */
    public function objectRouteClass()
    {
        return $this->objectRouteClass;
    }

    /**
     * Load the object associated with the matching object route.
     *
     * Validating if the object ID exists is delegated to the
     * {@see self::pathResolvable()} method.
     *
     * @return RoutableInterface
     */
    protected function loadContextObject()
    {
        if ($this->contextObject) {
            return $this->contextObject;
        }

        $objectRoute = $this->loadObjectRouteFromPath();

        // Could be the SAME
        $latest = $this->getLatestObjectPathHistory($objectRoute);

        if ($latest->creationDate() > $objectRoute->creationDate()) {
            $objectRoute = $latest;
            // Redirect 302
        }

        $obj = $this->modelFactory()->create($objectRoute->routeObjType());
        $obj->load($objectRoute->routeObjId());

        $this->contextObject = $obj;

        return $this->contextObject;
    }

    /**
     * Load the object route matching the URI path.
     *
     * @return \Charcoal\Object\ObjectRouteInterface
     */
    protected function loadObjectRouteFromPath()
    {
        if ($this->objectRoute) {
            return $this->objectRoute;
        }

        // Load current slug
        // Slug are uniq
        $route = $this->createRouteObject();
        $route->loadFromQuery(
            'SELECT * FROM `'.$route->source()->table().'` WHERE (`slug` = :route1 OR `slug` = :route2) LIMIT 1',
            [
                'route1' => '/'.$this->path(),
                'route2' => $this->path()
            ]
        );

        $this->objectRoute = $route;

        return $this->objectRoute;
    }

    /**
     * Retrieve the latest object route from the given object route's
     * associated object.
     *
     * The object routes are ordered by descending creation date (latest first).
     * Should never MISS, the given object route should exist.
     *
     * @param  ObjectRouteInterface $route Routable Object.
     * @return ObjectRouteInterface
     */
    public function getLatestObjectPathHistory(ObjectRouteInterface $route)
    {
        $loader = $this->collectionLoader();
        $loader->setModel($route);

        $loader
            ->addFilter('active', true)
            ->addFilter('route_obj_type', $route->routeObjType())
            ->addFilter('route_obj_id', $route->routeObjId())
            ->addFilter('lang', $route->lang())
            ->addOrder('creation_date', 'desc')
            ->setPage(1)
            ->setNumPerPage(1);

        $collection = $loader->load();
        $routes     = $collection->objects();

        $latestRoute = $routes[0];

        return $latestRoute;
    }

/**
 * SETTERS
 */

    /**
     * Set the specified URI path.
     *
     * @param string $path The path to use for route resolution.
     * @return self
     */
    protected function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Set an object model factory.
     *
     * @param FactoryInterface $factory The model factory, to create objects.
     * @return self
     */
    protected function setModelFactory(FactoryInterface $factory)
    {
        $this->modelFactory = $factory;

        return $this;
    }

    /**
     * Set a model collection loader.
     *
     * @param CollectionLoader $loader The collection loader.
     * @return self
     */
    public function setCollectionLoader(CollectionLoader $loader)
    {
        $this->collectionLoader = $loader;

        return $this;
    }

/**
 * GETTERS
 */

    /**
     * Retrieve the URI path.
     *
     * @return string
     */
    protected function path()
    {
        return $this->path;
    }

    /**
     * Retrieve the object model factory.
     *
     * @throws RuntimeException If the model factory was not previously set.
     * @return FactoryInterface
     */
    public function modelFactory()
    {
        if (!isset($this->modelFactory)) {
            throw new RuntimeException(
                sprintf('Model Factory is not defined for "%s"', get_class($this))
            );
        }

        return $this->modelFactory;
    }

    /**
     * Retrieve the model collection loader.
     *
     * @throws RuntimeException If the collection loader was not previously set.
     * @return CollectionLoader
     */
    protected function collectionLoader()
    {
        if (!isset($this->collectionLoader)) {
            throw new RuntimeException(
                sprintf('Collection Loader is not defined for "%s"', get_class($this))
            );
        }

        return $this->collectionLoader;
    }
}