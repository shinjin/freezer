<?php
namespace Freezer;

use Freezer\Exception\InvalidArgumentException;

class Freezer
{
    /**
     * @var string
     */
    private $idProperty;

    /**
     * @var array
     */
    private $blacklist;

    /**
     * @var boolean
     */
    private $useAutoload;

    /**
     * Constructor.
     *
     * @param  string                   $idProperty
     * @param  array                    $blacklist
     * @param  boolean                  $useAutoload
     * @throws InvalidArgumentException
     */
    public function __construct(
        $idProperty = '__freezer_uuid',
        array $blacklist = array(),
        $useAutoload = true
    ){
        $this->setIdProperty($idProperty);
        $this->setBlacklist($blacklist);
        $this->setUseAutoload($useAutoload);
    }

    /**
     * Freezes an object.
     *
     * @param  object  $object  The object that is to be frozen.
     * @param  array   $objects Only used internally.
     * @return array            The frozen object(s).
     * @throws InvalidArgumentException
     */
    public function freeze($object, array &$objects = array())
    {
        if (!is_object($object)) {
            throw new InvalidArgumentException(1, 'object');
        }

        // If the object has not been frozen before, generate a new UUID and
        // store it in the "special" __freezer_uuid property.
        if (!isset($object->{$this->idProperty})) {
            $object->{$this->idProperty} = $this->generateId();
        }

        if (!isset($object->__freezer)) {
            $object->__freezer = '{}';
        }

        $isDirty = $this->isDirty($object, true);
        $uuid    = $object->{$this->idProperty};

        if (!isset($objects[$uuid])) {
            $objects[$uuid] = array(
                'class'   => get_class($object),
                'isDirty' => $isDirty,
                'state'   => array()
            );

            // Iterate over the properties of the object.
            foreach ($this->readProperties($object) as $k => $v) {
                if ($k !== $this->idProperty) {
                    if (is_array($v)) {
                        $this->freezeArray($v, $objects);
                    } elseif (is_object($v)) {
                        // Freeze the aggregated object.
                        $this->freeze($v, $objects);

                        // Replace $v with the aggregated object's UUID.
                        $v = '__freezer_' . $v->{$this->idProperty};
                    } elseif (is_resource($v)) {
                        $v = null;
                    }

                    // Store the attribute in the object's state array.
                    $objects[$uuid]['state'][$k] = $v;
                }
            }
        }

        return array('root' => $uuid, 'objects' => $objects);
    }

    /**
     * Freezes an array.
     *
     * @param array $array   The array that is to be frozen.
     * @param array $objects Only used internally.
     */
    protected function freezeArray(array &$array, array &$objects)
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->freezeArray($value, $objects);
            } elseif (is_object($value)) {
                $tmp   = $this->freeze($value, $objects);
                $value = '__freezer_' . $tmp['root'];
                unset($tmp);
            }
        }
    }

    /**
     * Thaws an object.
     *
     * @param  array   $frozenObject The frozen object that should be thawed.
     * @param  string  $root         The UUID of the object that should be
     *                               treated as the root object when multiple
     *                               objects are present in $frozenObject.
     * @param  array   $objects      Only used internally.
     * @return object                The thawed object.
     * @throws RuntimeException
     */
    public function thaw(array $frozenObject, $root = null, array &$objects = array())
    {
        foreach ($frozenObject['objects'] as $object) {
            if (!class_exists($object['class'], $this->useAutoload)) {
                throw new \RuntimeException(
                    sprintf('Class "%s" could not be found.', $object['class'])
                );
            }
        }

        // By default, we thaw the root object and (recursively)
        // its aggregated objects.
        if ($root === null) {
            $root = $frozenObject['root'];
        }

        // Thaw object (if it has not been thawed before).
        if (!isset($objects[$root])) {
            $class = $frozenObject['objects'][$root]['class'];
            $state = $frozenObject['objects'][$root]['state'];

            // Use a trick to create a new object of a class
            // without invoking its constructor.
            $objects[$root] = unserialize(
                sprintf('O:%d:"%s":0:{}', strlen($class), $class)
            );

            // Handle aggregated objects.
            $this->thawArray($state, $frozenObject, $objects);

            $reflector = new \ReflectionObject($objects[$root]);

            foreach ($state as $name => $value) {
                if (strpos($name, '__freezer') !== 0) {
                    $property = $reflector->getProperty($name);
                    $property->setAccessible(true);
                    $property->setValue($objects[$root], $value);
                }
            }

            // Store UUID.
            $objects[$root]->{$this->idProperty} = $root;

            // Store __freezer.
            if (isset($state['__freezer'])) {
                $objects[$root]->__freezer = $state['__freezer'];
            }
        }

        return $objects[$root];
    }

    /**
     * Thaws an array.
     *
     * @param  array   $array        The array that is to be thawed.
     * @param  array   $frozenObject The frozen object structure from which to
     *                               thaw.
     * @param  array   $objects      Only used internally.
     */
    protected function thawArray(array &$array, array $frozenObject, array &$objects)
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->thawArray($value, $frozenObject, $objects);
            } elseif (is_string($value) && strpos($value, '__freezer') === 0) {
                $aggregatedObjectId = str_replace(
                  '__freezer_', '', $value
                );

                if (isset($frozenObject['objects'][$aggregatedObjectId])) {
                    $value = $this->thaw(
                        $frozenObject, $aggregatedObjectId, $objects
                    );
                }
            }
        }
    }

    /**
     * Returns the id property name
     *
     * @return string
     */
    public function getIdProperty()
    {
        return $this->idProperty;
    }

    /**
     * Sets the name to use for the id property
     *
     * @param  string $idProperty
     */
    public function setIdProperty($idProperty)
    {
        if (!is_string($idProperty)) {
            throw new InvalidArgumentException(1, 'string');
        }

        $this->idProperty = $idProperty;
    }

    /**
     * Returns the blacklist of properties that are not stored.
     *
     * @return array
     */
    public function getBlacklist()
    {
        return $this->blacklist;
    }

    /**
     * Sets the blacklist of properties that are not stored.
     *
     * @param array $blacklist
     */
    public function setBlacklist(array $blacklist)
    {
        $this->blacklist = $blacklist;
    }

    /**
     * Returns the flag that controls whether or not __autoload()
     * should be invoked.
     *
     * @return boolean
     */
    public function getUseAutoload()
    {
        return $this->useAutoload;
    }

    /**
     * Sets the flag that controls whether or not __autoload()
     * should be invoked.
     *
     * @param  boolean $flag
     * @throws InvalidArgumentException
     */
    public function setUseAutoload($flag)
    {
        if (!is_bool($flag)) {
            throw new InvalidArgumentException(1, 'boolean');
        }

        $this->useAutoload = $flag;
    }

    /**
     * Hashes an object using the SHA1 hashing function on the property values
     * of an object without recursing into aggregated arrays or objects.
     *
     * @param  object $object The object that is to be hashed.
     * @return string
     * @throws InvalidArgumentException
     */
    public function generateHash($object)
    {
        if (!is_object($object)) {
            throw new InvalidArgumentException(1, 'object');
        }

        $properties = $this->readProperties($object);
        ksort($properties);

        if (isset($properties['__freezer'])) {
            unset($properties['__freezer']);
        }

        foreach ($properties as $key => $value) {
            if (is_array($value)) {
                $properties[$key] = '<array>';
            } elseif (is_object($value)) {
                if (!isset($value->{$this->idProperty})) {
                    $value->{$this->idProperty} = $this->generateId();
                }

                $properties[$key] = $value->{$this->idProperty};
            } elseif (is_resource($value)) {
                $properties[$key] = null;
            }
        }

        return sha1(get_class($object) . join(':', $properties));
    }

    /**
     * This implementation of UUID generation is based on code from an answer
     * on stackoverflow.
     *
     * @return string
     * @link   https://stackoverflow.com/a/15875555
     */
    public function generateId()
    {
        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Checks whether an object is dirty, ie. if its SHA1 hash is still valid.
     *
     * Returns true when the object's __freezer['hash'] value is no longer
     * valid or does not exist.
     * Returns false when the object's __freezer['hash'] value is still valid.
     *
     * @param  object  $object The object that is to be checked.
     * @param  boolean $rehash Whether or not to rehash dirty objects.
     * @return boolean
     * @throws InvalidArgumentException
     */
    public function isDirty($object, $rehash = false)
    {
        if (!is_object($object)) {
            throw new InvalidArgumentException(1, 'object');
        }

        if (!is_bool($rehash)) {
            throw new InvalidArgumentException(2, 'boolean');
        }

        $isDirty = true;

        if (isset($object->__freezer)) {
            $hash = $this->generateHash($object);
            $__freezer = json_decode($object->__freezer, true);

            if (isset($__freezer['hash']) && $__freezer['hash'] === $hash) {
                $isDirty = false;
            }

            if ($isDirty && $rehash) {
                $__freezer['hash'] = $hash;
                $object->__freezer = json_encode($__freezer);
            }
        }

        return $isDirty;
    }

    /**
     * Returns an associative array of all properties of an object,
     * including those declared as protected or private.
     *
     * @param  object $object The object for which all properties are returned.
     * @return array
     * @throws InvalidArgumentException
     */
    public function readProperties($object)
    {
        if (!is_object($object)) {
            throw new InvalidArgumentException(1, 'object');
        }

        $reflector = new \ReflectionObject($object);
        $result    = array();

        // Iterate over the properties of the object.
        foreach ($reflector->getProperties() as $property) {
            $name = $property->getName();
            if (!in_array($name, $this->blacklist)) {
                $property->setAccessible(true);
                $result[$name] = $property->getValue($object);
            }
        }

        return $result;
    }
}
