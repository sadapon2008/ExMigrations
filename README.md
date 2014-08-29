#Requirements

Migrations Plugin for CakePHP (CakeDC/migrations) >= 2.3.1

#Settings

## DB connection

```
Configure::write('ExMigrations.ex_connection', 'ex_migrations');
```

## SQL filename for DDL
 
```
Configure::write('ExMigrations.ex_sql_filename', APP . 'Vendor' . DS . 'db' . DS . 'db.sql');
```

#Commands

```
./Console/cake ExMigrations.ex_migration generate -f
./Console/cake ExMigrations.ex_migration run up
./Console/cake schema generate -f
```
