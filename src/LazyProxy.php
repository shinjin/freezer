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
    private $uuid;

    /**
     * @var object
     */
    private $thawedObject;

    /**
     * Constructor.
     *
     * @param Freezer\Storage $storage
     * @param string          $uuid
     */
    public function __construct(Storage $storage, $uuid)
    {
        $this->storage = $storage;
        $this->uuid    = $uuid;
    }

    /**
     * Returns the real object.
     *
     * @return object
     */
    public function getObject()
    {
        if ($this->thawedObject === null) {
            $this->thawedObject = $this->storage->fetch($this->uuid);
        }

        return $this->thawedObject;
    }

    /**
     * Delegates the attribute read access to the real object and
     * tries to replace the lazy proxy object with it.
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        $object    = $this->replaceProxy(2);
        $attribute = new \ReflectionProperty($object, $name);
        $attribute->setAccessible(true);

        return $attribute->getValue($object);
    }

    /**
     * Delegates the attribute write access to the real object and
     * tries to replace the lazy proxy object with it.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        $object    = $this->replaceProxy(2);
        $attribute = new \ReflectionProperty($object, $name);
        $attribute->setAccessible(true);

        $attribute->setValue($object, $value);
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
        $object    = $this->replaceProxy(3);
        $reflector = new \ReflectionMethod($object, $name);

        return $reflector->invokeArgs($object, $arguments);
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

            foreach ($reflector->getProperties() as $attribute) {
                $attribute->setAccessible(true);

                if ($attribute->getValue($trace[$offset]['object']) === $this) {
                    $attribute->setValue($trace[$offset]['object'], $object);
                    break;
                }
            }
        }

        return $object;
    }
}
