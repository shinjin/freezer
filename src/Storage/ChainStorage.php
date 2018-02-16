<?php
namespace Freezer\Storage;

use Doctrine\Common\Cache\CacheProvider;
use Freezer\Freezer;
use Freezer\Storage;

class ChainStorage extends Storage
{
    /**
     * @var array $storageChain
     */
    private $storageChain;

    /**
     * Constructor.
     *
     * @param  array           $storageChain List of storage objects
     * @param  Freezer\Freezer $freezer      Freezer instance to be used
     * @param  boolean         $useLazyLoad  Flag that controls whether objects
     *                                       are fetched using lazy load or not
     */
    public function __construct(
        array $storageChain,
        Freezer $freezer = null,
        $useLazyLoad = false
    ){
        parent::__construct($freezer, $useLazyLoad);

        $this->storageChain = $storageChain;
    }

    /**
     * @inheritdoc
     */
    protected function doStore(array $frozenObject, $checkForDirt = true)
    {
        $stored = true;

        foreach ($this->storageChain as $storage) {
            if (!$storage instanceof \Freezer\Storage) {
                throw new \InvalidArgumentException(
                    'Storage chain contains invalid storage type'
                );
            }

            $stored = $storage->doStore($frozenObject) && $stored;
        }

        return $stored;
    }

    /**
     * @inheritdoc
     */
    protected function doFetch($id, array &$objects = array())
    {
        foreach ($this->storageChain as $key => $storage) {
            $frozenObject = $storage->doFetch($id, $objects);

            if ($frozenObject !== false) {
                for ($subKey = $key - 1; $subKey >= 0; $subKey--) {
                    $this->storageChain[$subKey]->doStore($frozenObject, false);
                }

                return $frozenObject;
            }
        }

        return false;
    }
}
