## Magento Slave MySQL: Separate Database Provider for Select Queries

### Overview

This Magento 2 module enhances database management capabilities by enabling SELECT queries to execute on a dedicated secondary database connection within Magento applications.

### Installation

#### Prerequisites

- Magento 2.x installed and configured.
- Composer globally installed.

#### Installation

```bash
composer require ant0x64/magento-slave-sql
bin/magento module:enable Ant0x64_SlaveSql
```

#### Configuration

To utilize the module's features, you need to configure the secondary database connection in Magento's env.php configuration file called `slave`:
```php
'db' => [
    'connection' => [
        'slave' => [
            'host' => 'slave_host',
            'dbname' => 'slave_db',
            'username' => 'db_username',
            'password' => 'db_password',
            'active' => '1',
        ],
    ],
],
```
