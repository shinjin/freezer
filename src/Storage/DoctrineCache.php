<?php
namespace Freezer\Storage;

use Doctrine\Common\Cache\CacheProvider;
use Freezer\Freezer;
use Freezer\Storage;

class DoctrineCache extends Storage
{
    /**
     * @var Doctrine\CacheProvider
     */
    protected $cache;

    /**
     * Constructor.
     *
     * @param  CacheProvider   $cache        Doctrine Cache object
     * @param  Freezer\Freezer $freezer      Freezer instance to be used
     * @param  boolean         $useLazyLoad  Flag that controls whether objects
     *                                       are fetched using lazy load or not
     */
    public function __construct(
        CacheProvider $cache,
        Freezer $freezer = null,
        $useLazyLoad = false
    ){
        parent::__construct($freezer, $useLazyLoad);

        $this->cache = $cache;
    }

    /**
     * @inheritdoc
     */
    protected function doStore(array $frozenObject, $checkForDirt = true)
    {
        foreach ($frozenObject['objects'] as $id => $object) {
            if ($object['isDirty'] !== false || $checkForDirt === false) {
                $payload = array(
                    'class' => $object['class'],
                    'state' => $object['state']
                );

                $this->cache->save($id, $payload);
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function doFetch($id, array &$objects = array())
    {
        $isRoot = empty($objects);

        if (!isset($objects[$id])) {
            if (($object = $this->cache->fetch($id)) === false) {
                return false;
            }

            $object['isDirty'] = false;
            $objects[$id] = $object;

            if (!$this->useLazyLoad) {
                $this->fetchArray($object['state'], $objects);
            }
        }

        if ($isRoot) {
            return array('root' => $id, 'objects' => $objects);
        }
    }
}
