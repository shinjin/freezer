<?php
namespace Freezer\Storage;

use Freezer\Freezer;
use Freezer\Storage;
use Freezer\Exception\InvalidArgumentException;

class CouchDB extends Storage
{
    /**
     * @var string
     */
    private $database;

    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * Constructor.
     *
     * @param  string                   $database
     *                                  Name of the database to be used
     * @param  string                   $host
     *                                  Hostname of the CouchDB instance to be used
     * @param  int                      $port
     *                                  Port of the CouchDB instance to be used
     * @param  Freezer\Freezer          $freezer
     *                                  Object_Freezer instance to be used
     * @param  boolean                  $useLazyLoad
     *                                  Flag that controls whether objects are
     *                                  fetched using lazy load or not
     * @throws InvalidArgumentException
     */
    public function __construct(
        $database,
        Freezer $freezer = null,
        $useLazyLoad = false,
        $host = 'localhost',
        $port = 5984
    ){
        parent::__construct($freezer, $useLazyLoad);

        if (!is_string($database)) {
            throw new InvalidArgumentException(1, 'string');
        }

        if (!is_string($host)) {
            throw new InvalidArgumentException(4, 'string');
        }

        if (!is_int($port)) {
            throw new InvalidArgumentException(5, 'integer');
        }

        $this->database = $database;
        $this->host     = $host;
        $this->port     = $port;
    }

    /**
     * @inheritdoc
     */
    protected function doStore(array $frozenObject)
    {
        $payload = array('docs' => array());

        foreach ($frozenObject['objects'] as $id => $object) {
            if ($object['isDirty'] === true) {
                $doc = array(
                    '_id'   => $id,
                    'class' => $object['class'],
                    'state' => $object['state']
                );

                $__freezer = json_decode($object['state']['__freezer'], true);

                if (isset($__freezer['_rev'])) {
                    $doc['_rev'] = $__freezer['_rev'];
                }

                array_push($payload['docs'], $doc);
            }
        }

        if (!empty($payload['docs'])) {
            $this->send(
                'POST',
                '/' . $this->database . '/_bulk_docs',
                json_encode($payload)
            );
        }
    }

    /**
     * @inheritdoc
     */
    protected function doFetch($id, array &$objects = array())
    {
        $isRoot = empty($objects);

        if (!isset($objects[$id])) {
            $response = $this->send('GET', '/' . $this->database . '/' . $id);

            if (strpos($response['headers'], 'HTTP/1.1 200 OK') === 0) {
                $object = json_decode($response['body'], true);
            } else {
                return false;
            }

            $object['state']['__freezer'] = sprintf(
                '%s,"_rev":"%s"}',
                rtrim($object['state']['__freezer'], '}'),
                $object['_rev']
            );

            $objects[$id] = array(
              'class'   => $object['class'],
              'isDirty' => false,
              'state'   => $object['state']
            );

            if (!$this->useLazyLoad) {
                $this->fetchArray($object['state'], $objects);
            }
        }

        if ($isRoot) {
            return array('root' => $id, 'objects' => $objects);
        }
    }

    /**
     * Sends an HTTP request to the CouchDB server.
     *
     * @param  string $method
     * @param  string $url
     * @param  string $payload
     * @return array
     * @throws RuntimeException
     */
    public function send($method, $url, $payload = null)
    {
        $socket = @fsockopen($this->host, $this->port, $errno, $errstr);

        if (!$socket) {
            throw new \RuntimeException($errno . ': ' . $errstr);
        }

        $request = sprintf(
            "%s %s HTTP/1.1\r\nHost: %s:%d\r\nContent-Type: application/json\r\nConnection: close\r\n",
            $method,
            $url,
            $this->host,
            $this->port
        );

        if ($payload !== null) {
            $request .= 'Content-Length: ' . strlen($payload) . "\r\n\r\n" .
                        $payload;
        }

        $request .= "\r\n";
        fwrite($socket, $request);

        $buffer = '';

        while (!feof($socket)) {
            $buffer .= fgets($socket);
        }

        list($headers, $body) = explode("\r\n\r\n", $buffer);

        return array('headers' => $headers, 'body' => $body);
    }
}
