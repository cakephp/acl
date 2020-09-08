<?php

use Phinx\Migration\AbstractMigration;

class CakePhpDbAcl extends AbstractMigration
{

    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('acos');
        $table->addColumn('parent_id', 'integer',['null'=>true])
            ->addColumn('model', 'string', ['limit' => 255,'null'=>true])
            ->addColumn('foreign_key', 'integer',['null'=>true])
            ->addColumn('alias', 'string', ['limit' => 255,'null'=>true])
            ->addColumn('lft', 'integer',['null'=>true])
            ->addColumn('rght', 'integer',['null'=>true])
            ->addIndex(array('lft','rght'))
            ->addIndex(array('alias'))
            ->create();
        $table = $this->table('aros');
        $table->addColumn('parent_id', 'integer',['null'=>true])
            ->addColumn('model', 'string', ['limit' => 255,'null'=>true])
            ->addColumn('foreign_key', 'integer',['null'=>true])
            ->addColumn('alias', 'string', ['limit' => 255,'null'=>true])
            ->addColumn('lft', 'integer',['null'=>true])
            ->addColumn('rght', 'integer',['null'=>true])
            ->addIndex(array('lft','rght'))
            ->addIndex(array('alias'))
            ->create();
        $table = $this->table('aros_acos');
        $table->addColumn('aro_id', 'integer',['null'=>false])
            ->addColumn('aco_id', 'integer',['null'=>false])
            ->addColumn('_create', 'string', ['default' => '0', 'limit' => 2,'null'=>false])
            ->addColumn('_read', 'string', ['default' => '0', 'limit' => 2,'null'=>false])
            ->addColumn('_update', 'string', ['default' => '0', 'limit' => 2,'null'=>false])
            ->addColumn('_delete', 'string', ['default' => '0', 'limit' => 2,'null'=>false])
            ->addIndex(array('aro_id', 'aco_id'),['unique'=>true])
            ->addIndex(array('aco_id'))
            ->create();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->table('aros_acos')->drop()->save();
        $this->table('acos')->drop()->save();
        $this->table('aros')->drop()->save();
    }

}