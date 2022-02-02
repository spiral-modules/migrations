<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Migrations\Tests;

use Spiral\Migrations\Fixtures\AddForeignKeyMigration;
use Spiral\Migrations\Fixtures\AlterForeignKeyMigration;
use Spiral\Migrations\Fixtures\AlterNonExistedColumnMigration;
use Spiral\Migrations\Fixtures\AlterNonExistedIndexMigration;
use Spiral\Migrations\Fixtures\CreateEmptyMigration;
use Spiral\Migrations\Fixtures\CreateSampleMigration;
use Spiral\Migrations\Fixtures\DropForeignKeyMigration;
use Spiral\Migrations\Fixtures\DropNonExistedIndexMigration;
use Spiral\Migrations\Fixtures\DropNonExistedMigration;
use Spiral\Migrations\Fixtures\DuplicateColumnMigration;
use Spiral\Migrations\Fixtures\RenameColumnMigration;
use Spiral\Migrations\Fixtures\RenameTableMigration;
use Spiral\Migrations\Exception\MigrationException;

abstract class ExceptionsTest extends BaseTest
{
    private const MIGRATION_EXCEPTION_PREFIX_REGEX = "/Error in the migration \([0-9a-z_\-]+ \(\d{4}-\d{2}-\d{2} "
    . "\d{2}:\d{2}:\d{2}\)\) occurred: ";

    public function testDropNonExisted(): void
    {
        $this->expectException(\Spiral\Migrations\Exception\Operation\TableException::class);
        $this->expectExceptionMessageMatches("/Unable to drop table '.+'\.'.+', table does not exists/");

        //Create thought migration
        $this->migrator->configure();
        $this->repository->registerMigration('m', DropNonExistedMigration::class);

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessageMatches(
            self::MIGRATION_EXCEPTION_PREFIX_REGEX . "Unable to drop table \'.+\'\.\'.+\', table does not exists/"
        );

        $this->migrator->run();
    }

    public function testCreateEmpty(): void
    {
        $this->expectException(\Spiral\Migrations\Exception\Operation\TableException::class);
        $this->expectExceptionMessageMatches("/Unable to create table '.+'\.'.+', no columns were added/");

        //Create thought migration
        $this->migrator->configure();
        $this->repository->registerMigration('m', CreateEmptyMigration::class);

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessageMatches(
            self::MIGRATION_EXCEPTION_PREFIX_REGEX . "Unable to create table \'.+\'\.\'.+\', no columns were added/"
        );

        $this->migrator->run();
    }

    public function testCreateDuplicate(): void
    {
        $this->expectException(\Spiral\Migrations\Exception\Operation\TableException::class);
        $this->expectExceptionMessageMatches("/Unable to create table '.+'\.'.+', table already exists/");

        //Create thought migration
        $this->migrator->configure();

        $s = $this->db->table('sample')->getSchema();
        $s->primary('idi');
        $s->save();

        $this->repository->registerMigration('m', CreateSampleMigration::class);

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessageMatches(
            self::MIGRATION_EXCEPTION_PREFIX_REGEX . "Unable to create table '.+'\.'.+', table already exists/"
        );

        $this->migrator->run();
    }

    public function testUpdateNonExisted(): void
    {
        $this->expectException(\Spiral\Migrations\Exception\Operation\TableException::class);
        $this->expectExceptionMessageMatches("/Unable to update table '.+'\.'.+', no table exists/");

        //Create thought migration
        $this->migrator->configure();

        $this->repository->registerMigration('m', DuplicateColumnMigration::class);

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessageMatches(
            self::MIGRATION_EXCEPTION_PREFIX_REGEX . "Unable to update table '.+'\.'.+', no table exists/"
        );

        $this->migrator->run();
    }

    public function testRenameNonExisted(): void
    {
        $this->expectException(\Spiral\Migrations\Exception\Operation\TableException::class);
        $this->expectExceptionMessageMatches("/Unable to rename table '.+'\.'.+', table does not exists/");

        //Create thought migration
        $this->migrator->configure();

        $this->repository->registerMigration('m', RenameTableMigration::class);

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessageMatches(
            self::MIGRATION_EXCEPTION_PREFIX_REGEX . "Unable to rename table '.+'\.'.+', table does not exists/"
        );

        $this->migrator->run();
    }

    public function testRenameButBusy(): void
    {
        $this->expectException(\Spiral\Migrations\Exception\Operation\TableException::class);
        $this->expectExceptionMessageMatches("/Unable to rename table '.+'\.'.+', table '.+' already exists/");

        //Create thought migration
        $this->migrator->configure();

        $s = $this->db->table('sample')->getSchema();
        $s->primary('idi');
        $s->save();

        $s = $this->db->table('new_name')->getSchema();
        $s->primary('idi');
        $s->save();

        $this->repository->registerMigration('m', RenameTableMigration::class);

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessageMatches(
            self::MIGRATION_EXCEPTION_PREFIX_REGEX . "Unable to rename table '.+'\.'.+', table '.+' already exists/"
        );

        $this->migrator->run();
    }

    public function testDuplicateColumn(): void
    {
        $this->expectException(\Spiral\Migrations\Exception\Operation\ColumnException::class);
        $this->expectExceptionMessageMatches("/Unable to create column '.+'\.'.+', column already exists/");

        //Create thought migration
        $this->migrator->configure();

        $s = $this->db->table('sample')->getSchema();
        $s->primary('id');
        $s->string('column');
        $s->save();

        $this->repository->registerMigration('m', DuplicateColumnMigration::class);

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessageMatches(
            self::MIGRATION_EXCEPTION_PREFIX_REGEX . "Unable to create column '.+'\.'.+', column already exists/"
        );

        $this->migrator->run();
    }

    public function testDropNonExistedIndex(): void
    {
        $this->expectException(\Spiral\Migrations\Exception\Operation\IndexException::class);
        $this->expectExceptionMessageMatches("/Unable to drop index '.+'\.(.+), index does not exists/");

        //Create thought migration
        $this->migrator->configure();

        $s = $this->db->table('sample')->getSchema();
        $s->primary('id');
        $s->string('column');
        $s->save();

        $this->repository->registerMigration('m', DropNonExistedIndexMigration::class);

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessageMatches(
            self::MIGRATION_EXCEPTION_PREFIX_REGEX . "Unable to drop index '.+'\.(.+), index does not exists/"
        );

        $this->migrator->run();
    }

    public function testAlterNonExistedIndex(): void
    {
        $this->expectException(\Spiral\Migrations\Exception\Operation\IndexException::class);
        $this->expectExceptionMessageMatches("/Unable to alter index '.+'\.(.+), no such index/");

        //Create thought migration
        $this->migrator->configure();

        $s = $this->db->table('sample')->getSchema();
        $s->primary('id');
        $s->string('column');
        $s->save();

        $this->repository->registerMigration('m', AlterNonExistedIndexMigration::class);

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessageMatches(
            self::MIGRATION_EXCEPTION_PREFIX_REGEX . "Unable to alter index '.+'\.(.+), no such index/"
        );

        $this->migrator->run();
    }

    public function testAlterNonExistedColumn(): void
    {
        $this->expectException(\Spiral\Migrations\Exception\Operation\ColumnException::class);
        $this->expectExceptionMessageMatches("/Unable to alter column '.+'\.'.+', column does not exists/");

        //Create thought migration
        $this->migrator->configure();

        $s = $this->db->table('sample')->getSchema();
        $s->primary('id');
        $s->save();

        $this->repository->registerMigration('m', AlterNonExistedColumnMigration::class);

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessageMatches(
            self::MIGRATION_EXCEPTION_PREFIX_REGEX . "Unable to alter column '.+'\.'.+', column does not exists/"
        );

        $this->migrator->run();
    }

    public function testRenameNonExistedColumn(): void
    {
        $this->expectException(\Spiral\Migrations\Exception\Operation\ColumnException::class);
        $this->expectExceptionMessageMatches("/Unable to rename column '.+'\.'.+', column does not exists/");

        //Create thought migration
        $this->migrator->configure();

        $s = $this->db->table('sample')->getSchema();
        $s->primary('id');
        $s->save();

        $this->repository->registerMigration('m', RenameColumnMigration::class);

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessageMatches(
            self::MIGRATION_EXCEPTION_PREFIX_REGEX . "Unable to rename column '.+'\.'.+', column does not exists/"
        );

        $this->migrator->run();
    }

    public function testRenameDuplicateExistedColumn(): void
    {
        $this->expectException(\Spiral\Migrations\Exception\Operation\ColumnException::class);
        $this->expectExceptionMessageMatches("/Unable to rename column '.+'\.'.+', column '.+' already exists/");

        //Create thought migration
        $this->migrator->configure();

        $s = $this->db->table('sample')->getSchema();
        $s->primary('id');
        $s->string('column');
        $s->float('new_name');
        $s->save();

        $this->repository->registerMigration('m', RenameColumnMigration::class);

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessageMatches(
            self::MIGRATION_EXCEPTION_PREFIX_REGEX . "Unable to rename column '.+'\.'.+', column '.+' already exists/"
        );

        $this->migrator->run();
    }

    public function testAddForeignNoTarget(): void
    {
        $this->expectException(\Spiral\Migrations\Exception\Operation\ForeignKeyException::class);
        $this->expectExceptionMessageMatches("/Unable to add foreign key 'tests_sample'.'column', foreign table 'target' does not exists/");

        //Create thought migration
        $this->migrator->configure();

        $s = $this->db->table('sample')->getSchema();
        $s->primary('id');
        $s->integer('column');
        $s->save();

        $this->repository->registerMigration('m', AddForeignKeyMigration::class);

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessageMatches(
            self::MIGRATION_EXCEPTION_PREFIX_REGEX
            . "Unable to add foreign key 'tests_sample'.'column', foreign table 'target' does not exists/"
        );

        $this->migrator->run();
    }

    public function testAddForeignNoTargetColumn(): void
    {
        $this->expectException(\Spiral\Migrations\Exception\Operation\ForeignKeyException::class);
        $this->expectExceptionMessageMatches("/Unable to add foreign key '.+'\.'.+', foreign column '.+'\.'.+' does not exists/");

        //Create thought migration
        $this->migrator->configure();

        $s = $this->db->table('sample')->getSchema();
        $s->primary('id');
        $s->integer('column');
        $s->save();

        $s2 = $this->db->table('target')->getSchema();
        $s2->primary('id2');
        $s2->save();

        $this->repository->registerMigration('m', AddForeignKeyMigration::class);

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessageMatches(
            self::MIGRATION_EXCEPTION_PREFIX_REGEX
            . "Unable to add foreign key '.+'\.'.+', foreign column '.+'\.'.+' does not exists/"
        );

        $this->migrator->run();
    }

    public function testAlterForeignNoFK(): void
    {
        $this->expectException(\Spiral\Migrations\Exception\Operation\ForeignKeyException::class);
        $this->expectExceptionMessageMatches("/Unable to alter foreign key '.+'\.(.+), key does not exists/");

        //Create thought migration
        $this->migrator->configure();

        $s = $this->db->table('sample')->getSchema();
        $s->primary('id');
        $s->integer('column');
        $s->save();

        $this->repository->registerMigration('m', AlterForeignKeyMigration::class);

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessageMatches(
            self::MIGRATION_EXCEPTION_PREFIX_REGEX . "Unable to alter foreign key '.+'\.(.+), key does not exists/"
        );

        $this->migrator->run();
    }

    public function testAlterForeignNoTable(): void
    {
        $this->expectException(\Spiral\Migrations\Exception\Operation\ForeignKeyException::class);
        $this->expectExceptionMessageMatches("/Unable to alter foreign key '.+'\.'.+', foreign table '.+' does not exists/");

        //Create thought migration
        $this->migrator->configure();

        $s2 = $this->db->table('target')->getSchema();
        $s2->primary('id');
        $s2->save();

        $s = $this->db->table('sample')->getSchema();
        $s->primary('id');
        $s->integer('column');
        $s->foreignKey(['column'])->references('target', ['id']);
        $s->save();

        $this->repository->registerMigration('m', AlterForeignKeyMigration::class);

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessageMatches(
            self::MIGRATION_EXCEPTION_PREFIX_REGEX
            . "Unable to alter foreign key '.+'\.'.+', foreign table '.+' does not exists/"
        );

        $this->migrator->run();
    }

    public function testAlterForeignNoColumn(): void
    {
        $this->expectException(\Spiral\Migrations\Exception\Operation\ForeignKeyException::class);
        $this->expectExceptionMessageMatches("/Unable to alter foreign key '.+'\.'.+', foreign column '.+'\.'.+' does not exists/");

        //Create thought migration
        $this->migrator->configure();

        $s2 = $this->db->table('target')->getSchema();
        $s2->primary('id');
        $s2->save();

        $s2 = $this->db->table('target2')->getSchema();
        $s2->primary('id');
        $s2->save();

        $s = $this->db->table('sample')->getSchema();
        $s->primary('id');
        $s->integer('column');
        $s->foreignKey(['column'])->references('target', ['id']);
        $s->save();

        $this->repository->registerMigration('m', AlterForeignKeyMigration::class);

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessageMatches(
            self::MIGRATION_EXCEPTION_PREFIX_REGEX
            . "Unable to alter foreign key '.+'\.'.+', foreign column '.+'\.'.+' does not exists/"
        );

        $this->migrator->run();
    }

    public function testDropNonExistedFK(): void
    {
        $this->expectException(\Spiral\Migrations\Exception\Operation\ForeignKeyException::class);
        $this->expectExceptionMessageMatches("/Unable to drop foreign key '.+'\.'.+', foreign key does not exists/");

        //Create thought migration
        $this->migrator->configure();

        $s = $this->db->table('sample')->getSchema();
        $s->primary('id');
        $s->integer('column');
        $s->save();

        $this->repository->registerMigration('m', DropForeignKeyMigration::class);

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessageMatches(
            self::MIGRATION_EXCEPTION_PREFIX_REGEX
            . "Unable to drop foreign key '.+'\.'.+', foreign key does not exists/"
        );

        $this->migrator->run();
    }

    public function testAddExisted(): void
    {
        $this->expectException(\Spiral\Migrations\Exception\Operation\ForeignKeyException::class);
        $this->expectExceptionMessageMatches("/Unable to add foreign key '.+'\.(.+), foreign key already exists/");

        //Create thought migration
        $this->migrator->configure();

        $s2 = $this->db->table('target')->getSchema();
        $s2->primary('id');
        $s2->save();

        $s = $this->db->table('sample')->getSchema();
        $s->primary('id');
        $s->integer('column');
        $s->foreignKey(['column'])->references('target', ['id']);
        $s->save();

        $this->repository->registerMigration('m', AddForeignKeyMigration::class);

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessageMatches(
            self::MIGRATION_EXCEPTION_PREFIX_REGEX . "Unable to add foreign key '.+'\.(.+), foreign key already exists/"
        );

        $this->migrator->run();
    }

    public function testBadMigrationFile(): void
    {
        file_put_contents(__DIR__ . '/../files/mmm.php', 'test');

        $this->expectException(\Spiral\Migrations\Exception\RepositoryException::class);
        $this->expectExceptionMessageMatches("/Invalid migration filename '.+'/");
        $this->repository->getMigrations();
    }

    public function testBadDateFormatMigrationFile(): void
    {
        $fileName = (new \DateTime())->format('dmY-his') . '_0_test.php';
        file_put_contents(__DIR__ . "/../files/{$fileName}", 'test');

        $this->expectException(\Spiral\Migrations\Exception\RepositoryException::class);
        $this->expectExceptionMessageMatches("/Invalid migration filename '.+' - corrupted date format/");
        $this->repository->getMigrations();
    }

    public function testDuplicateClassMigration(): void
    {
        $this->expectException(\Spiral\Migrations\Exception\RepositoryException::class);
        $this->expectExceptionMessageMatches("/Unable to register migration '.+', migration already exists/");
        $this->repository->registerMigration('unique_name_1', DuplicateColumnMigration::class);
        $this->repository->registerMigration('unique_name_2', DuplicateColumnMigration::class);
    }

    public function testDuplicateFileNameMigration(): void
    {
        $this->expectException(\Spiral\Migrations\Exception\RepositoryException::class);
        $this->expectExceptionMessageMatches("/Unable to register migration 'camel_case_duplicate', migration under the same name already exists/");
        $this->repository->registerMigration('camel_case_duplicate', DuplicateColumnMigration::class);
        $this->repository->registerMigration('camelCaseDuplicate', CreateEmptyMigration::class);
    }

    public function testInvalidMigration(): void
    {
        $this->expectException(\Spiral\Migrations\Exception\RepositoryException::class);
        $this->expectExceptionMessageMatches("/Unable to register migration '.+', representing class does not exists/");
        $this->repository->registerMigration('m', 'invalid');
    }
}
