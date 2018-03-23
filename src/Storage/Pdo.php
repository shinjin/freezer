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
    protected $db;

    /**
     * @var string
     */
    protected $table;

    /**
     * Constructor.
     *
     * @param  Db|\PDO|array   $db          Shinjin\Pdo\Db object, PDO object,
     *                                      or array of db parameters
     * @param  Freezer\Freezer $freezer     Freezer instance to be used
     * @param  boolean         $useLazyLoad Flag that controls whether objects
     *                                      are fetched using lazy load or not
     * @param  string          $table       Db table name
     * @param  array           $dbOptions   PDO options
     * @throws InvalidArgumentException
     */
    public function __construct(
        $db,
        Freezer $freezer = null,
        $useLazyLoad = false,
        $table = 'freezer',
        array $dbOptions = array()
    ){
        parent::__construct($freezer, $useLazyLoad);

        if (!$db instanceof Db) {
            try {
                $db = new Db($db, $dbOptions);
            } catch (\Exception $e) {
                throw new InvalidArgumentException(1,
                    '$db arg must be a Shinjin\\Pdo\\Db object, PDO object, ' .
                    'or array of db parameters.'
                );
            }
        }

        $this->db = $db;
        $this->table = $table;
    }

    /**
     * @inheritdoc
     */
    protected function doStore(array $frozenObject)
    {
        $stmt1 = sprintf('UPDATE %s SET body = ? WHERE id = ?', $this->table);
        $stmt2 = sprintf('INSERT INTO %s (id,body) VALUES (?,?)', $this->table);

        foreach (array_reverse($frozenObject['objects']) as $id => $object) {
            if ($object['isDirty'] === true) {
                $payload = array(
                    'class' => $object['class'],
                    'state' => $object['state']
                );
                $body = json_encode($payload);

                $this->db->beginTransaction();

                $stmt1 = $this->db->query($stmt1, array($body, $id));

                if ($stmt1->rowCount() === 0) {
                    $stmt2 = $this->db->query($stmt2, array($id, $body));
                }

                $this->db->commit();
            }
        }

        return $id;
    }

    /**
     * @inheritdoc
     */
    protected function doFetch($id, array &$objects = array())
    {
        $isRoot = empty($objects);

        if (!isset($objects[$id])) {
            $stmt = sprintf('SELECT * FROM %s WHERE id = ?', $this->table);
            $stmt = $this->db->query($stmt, array($id));

            if (($result = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
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
