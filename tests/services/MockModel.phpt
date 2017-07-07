<?php

namespace Test;

use Masala\MockModel,
    Nette\DI\Container,
    Nette\Database\Connection,
    Nette\Database\Context,
    Nette\Database\Row,
    Nette\Database\Structure,
    Nette\Caching\Storages\FileStorage,
    Tester\Assert,
    Tester\TestCase;

$container = require __DIR__ . '/../../../bootstrap.php';

/** @author Lubomir Andrisek */
final class MockModelTest extends TestCase {

    /** @var Container */
    private $container;

    /** @var MockModel */
    private $class;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    protected function setUp() {
        $connection = new Connection($this->container->parameters['database']['dsn'], $this->container->parameters['database']['user'], $this->container->parameters['database']['password']);
        $cacheStorage = new FileStorage(__DIR__ . '/../../../temp');
        $structure = new Structure($connection, $cacheStorage);
        $context = new Context($connection, $structure, null, $cacheStorage);
        $this->class = new MockModel($context, $cacheStorage);
    }

    public function __destruct() {
        echo 'Tests of ' . get_class($this->class) . ' finished.' . "\n";
    }

    public function testGetters() {
        $path = $this->container->parameters['appDir'] . '/Masala/services/MockModel.php';
        Assert::true(is_file($path), 'File ' . $path . ' for parsing was not found.');
        $methods = [];
        $method = '';
        $file = fopen($path, 'r+');
        while ($line = fgets($file)) {
            if (preg_match('/function/', $line) and preg_match('/get([A-Z+])/', $line)) {
                $method = '';
            } elseif (preg_match('/}/', $line)) {
                $methods[] = $method;
            }
            $method .= trim($line);
        }
        /** parse clauses */
        $parameters = $this->container->getParameters();
        foreach ($methods as $method) {
            if (preg_match('/->where\(/', $method) and preg_match('/->table\(\$this->source\)/', $method)) {
                $clauses = explode('->where(', $method);
                unset($clauses[0]);
                Assert::notSame(false, $activeRow = $this->class->getTestRow($parameters['masala']['log']), 'There is no row in table ' . $parameters['masala']['log']);
                Assert::true(is_array($structure = $this->class->getColumns($parameters['masala']['log'])), 'Structure missing in table ' . $parameters['masala']['log']);
                foreach ($structure as $column) {
                    $queryColumns[$column['name']] = $column['name'];
                }
                foreach ($clauses as $clause) {
                    $column = trim(preg_replace('/\,(.*)|\'|\"| (.*)/', '', htmlspecialchars($clause)));
                    $row = (!in_array($column, $queryColumns)) ? $this->class->explainColumn('fc_trigger', $column) : $this->class->explainColumn($parameters['masala']['log'], $column);
                    Assert::notSame(false, $row, 'Testing non-existing column.');
                    Assert::true($row instanceof Row, 'Testing non-existing column.');
                    Assert::same('index', $row->type, 'In table ' . $parameters['masala']['log'] . ' is used unindexed column ' . $column);
                }
            }
        }
    }

}

id(new MockModelTest($container))->run();
