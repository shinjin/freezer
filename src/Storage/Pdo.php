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
    private $table;

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
        $this->table = 'freezer';

        if (is_array($pdo) and isset($pdo['table'])) {
            $this->table = $pdo['table'];
        }
    }

    /**
     * @inheritdoc
     */
    protected function doStore(array $frozenObject, $checkForDirt = true)
    {
        $stmt1 = sprintf('UPDATE %s SET body = ? WHERE id = ?', $this->table);
        $stmt2 = sprintf('INSERT INTO %s (id,body) VALUES (?,?)', $this->table);

        foreach ($frozenObject['objects'] as $id => $object) {
            if ($object['isDirty'] !== false || $checkForDirt === false) {
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
    }

    /**
     * @inheritdoc
     */
    protected function doFetch($id, array &$objects = array())
    {
        $isRoot = empty($objects);

        if (!isset($objects[$id])) {
            $query = sprintf('SELECT * FROM %s WHERE id = ?', $this->table);
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
