<?php
namespace Freezer;

use Freezer\Exception\InvalidArgumentException;
use Freezer\Exception\ObjectNotFoundException;

abstract class Storage
{
    /**
     * @var Freezer\Freezer
     */
    protected $freezer;

    /**
     * @var boolean
     */
    protected $useLazyLoad;

    /**
     * Constructor.
     *
     * @param  Freezer\Freezer      $freezer
     *                              Freezer instance to be used
     * @param  boolean              $useLazyLoad
     *                              Flag that controls whether objects are
     *                              fetched using lazy load or not
     */
    public function __construct(Freezer $freezer = null, $useLazyLoad = false)
    {
        if ($freezer === null) {
            $freezer = new Freezer;
        }

        $this->freezer = $freezer;
        $this->setUseLazyLoad($useLazyLoad);
    }

    /**
     * Returns the freezer object.
     *
     * @return Freezer\Freezer
     */
    public function getFreezer()
    {
        return $this->freezer;
    }

    /**
     * Sets the flag that controls whether objects are fetched using lazy load.
     *
     * @param  boolean $flag
     * @throws InvalidArgumentException
     */
    public function setUseLazyLoad($flag)
    {
        if (!is_bool($flag)) {
            throw new InvalidArgumentException(1, 'boolean');
        }

        $this->useLazyLoad = $flag;
    }

    /**
     * Returns the flag that controls whether objects are fetched using lazy load.
     *
     * @return boolean
     */
    public function getUseLazyLoad()
    {
        return $this->useLazyLoad;
    }

    /**
     * Freezes an object and stores it in the object storage.
     *
     * @param  object $object The object that is to be stored.
     * @return string
     */
    public function store($object)
    {
        if (!is_object($object)) {
            throw new InvalidArgumentException(1, 'object');
        }

        $objects = array();
        $id = $this->doStore($this->freezer->freeze($object, $objects));

        if ($id !== null) {
            $object->{$this->freezer->getIdProperty()} = $id;
        }

        return $object->{$this->freezer->getIdProperty()};
    }

    /**
     * Fetches a frozen object from the object storage and thaws it.
     *
     * @param  string $id The ID of the object that is to be fetched.
     * @return object
     */
    public function fetch($id)
    {
        if (!is_string($id)) {
            throw new InvalidArgumentException(1, 'string');
        }

        if (($frozenObject = $this->doFetch($id)) !== false) {
            $this->fetchArray($frozenObject['objects'][$id]['state']);
            $object = $this->freezer->thaw($frozenObject);
        } else {
            throw new ObjectNotFoundException(
                sprintf('Object with id "%s" could not be fetched.', $id)
            );
        }

        return $object;
    }

    /**
     * Fetches a frozen array from the object storage and thaws it.
     *
     * @param array $array
     * @param array $objects
     */
    protected function fetchArray(array &$array, array &$objects = array())
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->fetchArray($value, $objects);
            } elseif (is_string($value) && strpos($value, '__freezer_') === 0) {
                $id = str_replace('__freezer_', '', $value);

                if (!$this->useLazyLoad) {
                    $this->doFetch($id, $objects);
                } else {
                    $idProperty = $this->freezer->getIdProperty();
                    $value = new LazyProxy($this, $id, $idProperty);
                }
            }
        }
    }

    /**
     * Freezes an object and stores it in the object storage.
     *
     * @param array $frozenObject
     */
    abstract protected function doStore(array $frozenObject);

    /**
     * Fetches a frozen object from the object storage and thaws it.
     *
     * @param  string $id The ID of the object that is to be fetched.
     * @return object
     */
    abstract protected function doFetch($id);
}
