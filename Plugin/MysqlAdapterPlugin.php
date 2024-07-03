<?php

namespace Ant0x64\SlaveSql\Plugin;

class MysqlAdapterPlugin
{
    protected $connection;
    protected \Ant0x64\SlaveSql\DB\Arapter\Pdo\Mysql $adapter;

    public function __construct(
        \Magento\Framework\DB\Adapter\Pdo\MysqlFactory $readAdapterFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\App\ResourceConnection\ConnectionFactory $connectionFactory,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
    )
    {
        $config = $deploymentConfig->get('db/connection/slave');

        if ($config) {
            /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
            $this->adapter = $readAdapterFactory->create(
                \Ant0x64\SlaveSql\DB\Arapter\Pdo\Mysql::class,
                $config
            );
            $this->connection = $this->adapter->getConnection();
        }

        // Initialize the secondary database connection (read)
    }

    public function aroundQuery(\Magento\Framework\DB\Adapter\Pdo\Mysql $subject, $proceed, $sql, $bind = [])
    {
        if (
            !($subject instanceof \Ant0x64\SlaveSql\DB\Arapter\Pdo\Mysql) &&
            $this->connection &&
            $this->isSelectQuery($sql)
        ) {
            return $this->adapter->query($sql, $bind);
        }
        return $proceed($sql, $bind);
    }

    protected function isSelectQuery($sql)
    {
        return ($sql !== null &&
            stripos($sql, 'SELECT') === 0 &&
            count($this->_splitMultiQuery($sql)) === 1
        );
    }

    /** @see \Magento\Framework\DB\Adapter\Pdo\Mysql::_splitMultiQuery * */
    protected function _splitMultiQuery($sql)
    {
        $parts = preg_split(
            '#(;|\'|"|\\\\|//|--|\n|/\*|\*/)#',
            $sql,
            -1,
            PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE
        );

        $q = false;
        $c = false;
        $stmts = [];
        $s = '';

        foreach ($parts as $i => $part) {
            // strings
            if (($part === "'" || $part === '"') && ($i === 0 || $parts[$i - 1] !== '\\')) {
                if ($q === false) {
                    $q = $part;
                } elseif ($q === $part) {
                    $q = false;
                }
            }

            // single line comments
            if (($part === '//' || $part === '--') && ($i === 0 || $parts[$i - 1] === "\n")) {
                $c = $part;
            } elseif ($part === "\n" && ($c === '//' || $c === '--')) {
                $c = false;
            }

            // multi line comments
            if ($part === '/*' && $c === false) {
                $c = '/*';
            } elseif ($part === '*/' && $c === '/*') {
                $c = false;
            }

            // statements
            if ($part === ';' && $q === false && $c === false) {
                if (trim($s) !== '') {
                    $stmts[] = trim($s);
                    $s = '';
                }
            } else {
                $s .= $part;
            }
        }
        if (trim($s) !== '') {
            $stmts[] = trim($s);
        }

        return $stmts;
    }
}
