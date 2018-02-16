<?php
namespace Freezer\Storage;

use Freezer\Freezer;
use Freezer\Storage;
use Freezer\Exception\InvalidArgumentException;
use Shinjin\Pdo\Db;

class Pdo extends Storage
{
    /**
     * @var Shinjin\Pdo\Db
     */
    private $db;

    /**
     * @var string
     */
    private $tblname;

    /**
     * Constructor.
     *
     * @param  \PDO|array      $pdo         PDO object or array of db parameters
     * @param  Freezer\Freezer $freezer     Freezer instance to be used
     * @param  boolean         $useLazyLoad Flag that controls whether objects
     *                                      are fetched using lazy load or not
     * @param  array           $db_options  PDO options
     * @throws InvalidArgumentException
     */
    public function __construct(
        $pdo,
        Freezer $freezer = null,
        $useLazyLoad = false,
        array $db_options = array()
    ){
        if (!$pdo instanceof \PDO && !is_array($pdo)) {
            throw new InvalidArgumentException(
                1, '$pdo must be a PDO object or an array'
            );
        }

        parent::__construct($freezer, $useLazyLoad);

        $this->db = new Db($pdo, $db_options);
        $this->tblname = 'freezer';

        if (is_array($pdo) and isset($pdo['tblname'])) {
            $this->tblname = $pdo['tblname'];
        }
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

                $this->db->insert(
                    $this->tblname,
                    array('id' => $id, 'body' => json_encode($payload))
                );
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
            $query = sprintf('SELECT * FROM %s WHERE id = ?', $this->tblname);
            $sth = $this->db->query($query, array($id));

            if (($result = $sth->fetch()) !== false) {
                $object = json_decode($result['body'], true);
            } else {
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
