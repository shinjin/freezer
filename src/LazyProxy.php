<?php
namespace Freezer;

class LazyProxy
{
    /**
     * @var Freezer\Storage
     */
    private $storage;

    /**
     * @var string
     */
    private $id;

    /**
     * @var object
     */
    private $thawedObject;

    /**
     * Constructor.
     *
     * @param Freezer\Storage $storage
     * @param string          $id
     */
    public function __construct(Storage $storage, $id)
    {
        $this->storage      = $storage;
        $this->id           = $id;
        $this->thawedObject = null;
    }

    /**
     * Returns the real object.
     *
     * @return object
     */
    public function getObject()
    {
        if ($this->thawedObject === null) {
            $this->thawedObject = $this->storage->fetch($this->id);
        }

        return $this->thawedObject;
    }

    /**
     * Returns the real object id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns true if the object has been fetched; false otherwise.
     *
     * @return string
     */
    public function isThawed()
    {
        return $this->thawedObject !== null;
    }

    /**
     * Delegates the property read access to the real object and
     * tries to replace the lazy proxy object with it.
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        if ($this->thawedObject === null) {
            if ($name === $this->storage->getFreezer()->getIdProperty()) {
                return $this->id;
            }
        }

        return $this->replaceProxy(2)->{$name};
    }

    /**
     * Delegates the property write access to the real object and
     * tries to replace the lazy proxy object with it.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        $this->replaceProxy(2)->{$name} = $value;
    }

    /**
     * Delegates the message to the real object and
     * tries to replace the lazy proxy object with it.
     *
     * @param  string $name
     * @param  array  $arguments
     * @return mixed
     */
    public function __call($name, array $arguments)
    {
        $callback = array($this->replaceProxy(3), $name);
        return call_user_func_array($callback, $arguments);
    }

    /**
     * Delegates the property isset check to the real object and
     * tries to replace the lazy proxy object with it.
     *
     * @param  string $name
     * @return mixed
     */
    public function __isset($name)
    {
        if ($this->thawedObject === null) {
            if ($name === $this->storage->getFreezer()->getIdProperty()) {
                return true;
            }
        }

        return isset($this->replaceProxy(2)->{$name});
    }

    /**
     * Tries to replace the lazy proxy object with the real object.
     *
     * @param  integer $offset
     * @return object
     */
    protected function replaceProxy($offset)
    {
        $object = $this->getObject();

        /**
         * 0: LazyProxy::replaceProxy()
         * 1: LazyProxy::__get($name) / LazyProxy::__set($name, $value)
         *    2: Frame that accesses $name
         * 1: LazyProxy::__call($method, $arguments)
         * 2: LazyProxy::$method()
         *    3: Frame that invokes $method
         */
        $trace = debug_backtrace();

        if (isset($trace[$offset]['object'])) {
            $reflector = new \ReflectionObject($trace[$offset]['object']);

            foreach ($reflector->getProperties() as $property) {
                $property->setAccessible(true);

                if ($property->getValue($trace[$offset]['object']) === $this) {
                    $property->setValue($trace[$offset]['object'], $object);
                    break;
                }
            }
        }

        return $object;
    }
}
