<?php

/**
 * PostgreSQL専用の改造版マイグレーションプラグイン
 */

App::uses('CakeSchema', 'Model');
App::uses('ConnectionManager', 'Model');
App::uses('MigrationShell', 'Migrations.Console/Command');

class ExMigrationShell extends MigrationShell {

    public $ex_connection = null;
    public $ex_sql_filename = null;

    public function __construct($stdout = null, $stderr = null, $stdin = null) {
        parent::__construct($stdout, $stderr, $stdin);

        // 設定値の初期値
        $this->ex_connection = 'ex_migrations';
        $this->ex_sql_filename = APP . 'Vendor' . DS . 'db' . DS . 'db.sql';

        // 設定値をセットする
        foreach(array('ex_connection', 'ex_sql_filename') as $key) {
            $val = Configure::read('ExMigrations.' . $key);
            if(!empty($val)) {
                $this->{$key} = $val;
            }
        }
    }

/**
 * Generate a new migration file
 *
 * @return void
 */
    public function generate() {
        $fromSchema = false;
        $this->Schema = $this->_getSchema();
        $migration = array('up' => array(), 'down' => array());
        $migrationName = '';
        $comparison = array();

        if (!empty($this->args)) {
            // If args are passed in from the command line, we just want to
            // generate a migration based on them - don't offer to compare to database
            $this->_generateFromCliArgs($migration, $migrationName, $comparison);
        } else {
            $oldSchema = $this->_getSchema($this->type);
            if ($oldSchema !== false) {
                $response = $this->in(__d('migrations', 'Do you want compare the schema.php file to the database?'), array('y', 'n'), 'y');
                if (strtolower($response) === 'y') {
                    $this->_generateFromComparison($migration, $oldSchema, $comparison);
                    $fromSchema = true;
                }
            } else {
                $response = $this->in(__d('migrations', 'Do you want generate a dump from current database?'), array('y', 'n'), 'y');
                if (strtolower($response) === 'y') {
                    $this->_generateDump($migration);
                    $fromSchema = true;
                }
            }
        }

        $fromSchema = false;
        $this->_finalizeGeneratedMigration($migration, $migrationName, $fromSchema);
    }

    /**
     * マイグレーション専用のDBのテーブルをすべてdropして
     * DDLのsqlファイルを実行してテーブルを再構築する
     */
    protected function _getSchema($type = null) {
        if($type === null) {
            $db = ConnectionManager::getDataSource($this->ex_connection);
            // すべてのテーブルをdropする
            $tables = (array)$db->listSources();
            foreach($tables as $table) {
                $sql = 'DROP TABLE ' . $table . ';';
                $db->query($sql);
            }
            if(@file_exists($this->ex_sql_filename)) {
                // コマンドを組み立てる
                $cmd = '';
                $cmd .= ' env PGPASSWORD=' . $db->config['password'];
                $cmd .= ' psql';
                $cmd .= ' -U ' . $db->config['login'];
                $cmd .= ' -h ' . $db->config['host'];
                $cmd .= ' -p ' . $db->config['port'];
                $cmd .= ' -f "' . $this->ex_sql_filename . '"';
                $cmd .= ' ' . $db->config['database'];
                $cmd .= ' 2>&1';

                // コマンド実行
                exec($cmd, $output, $return_var);
            }
            return new CakeSchema(array('connection' => $this->ex_connection, 'plugin' => null));
        }
        return parent::_getSchema($type);
    }
}
