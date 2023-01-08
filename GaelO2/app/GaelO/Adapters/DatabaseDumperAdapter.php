<?php

namespace App\GaelO\Adapters;

use App\GaelO\Interfaces\Adapters\DatabaseDumperInterface;
use Illuminate\Support\Facades\Config;
use Spatie\DbDumper\Databases\MySql;
use Spatie\DbDumper\Databases\PostgreSql;

class DatabaseDumperAdapter implements DatabaseDumperInterface
{

    const DB_MYSQL = "mysql";
    const DB_PGSQL = "pgsql";

    public function createDatabaseDumpFile(string $filePath): void
    {
        $databaseType = Config::get('database.default');

        if ($databaseType === self::DB_MYSQL) {
            $this->getMysqlDump($filePath);
        } else if ($databaseType === self::DB_PGSQL) {
            $this->getPosgresDump($filePath);
        }

    }

    private function getMysqlDump(String $file)
    {

        $databaseHost = Config::get('database.connections.mysql.host');
        $databasePort = Config::get('database.connections.mysql.port');
        $databaseName = Config::get('database.connections.mysql.database');
        $userName = Config::get('database.connections.mysql.username');
        $password = Config::get('database.connections.mysql.password');

        return MySql::create()
            ->setHost($databaseHost)
            ->setPort($databasePort)
            ->setDbName($databaseName)
            ->setUserName($userName)
            ->setPassword($password)
            ->dumpToFile($file);
    }


    private function getPosgresDump(String $file)
    {

        $databaseHost = Config::get('database.connections.pgsql.host');
        $databasePort = Config::get('database.connections.pgsql.port');
        $databaseName = Config::get('database.connections.pgsql.database');
        $userName = Config::get('database.connections.pgsql.username');
        $password = Config::get('database.connections.pgsql.password');

        return PostgreSql::create()
            ->setHost($databaseHost)
            ->setPort($databasePort)
            ->setDbName($databaseName)
            ->setUserName($userName)
            ->setPassword($password)
            ->dumpToFile($file);
    }
}
