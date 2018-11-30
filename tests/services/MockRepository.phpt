<?php

namespace Tests\Masala;

use Masala\IMock,
    Nette\DI\Container,
    Nette\Database\Row,
    Tester\Assert,
    Tester\TestCase;

$container = require __DIR__ . '/../../../../bootstrap.php';

/** @author Lubomir Andrisek */
final class MockRepositoryTest extends TestCase {

    /** @var Container */
    private $container;

    /** @var IMock */
    private $class;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    protected function setUp(): void {
        $this->class = $this->container->getByType('Masala\IMock');
    }

    public function __destruct() {
        echo 'Tests of ' . get_class($this->class) . ' finished.' . "\n";
    }

    public function testGetters(): void {
        $path = $this->container->parameters['appDir'] . '/components/masala/services/MockRepository.php';
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
                    Assert::same($row, 'debug');
                    Assert::true($row instanceof Row, 'Testing non-existing column.');
                    Assert::same('index', $row->type, 'In table ' . $parameters['masala']['log'] . ' is used unindexed column ' . $column);
                }
            }
        }
    }

}

id(new MockRepositoryTest($container))->run();