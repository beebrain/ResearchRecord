<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPublicationSyncFlags extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('publications')) {
            return;
        }

        $fields = [];
        if (! $this->db->fieldExists('sync_external_key', 'publications')) {
            $fields['sync_external_key'] = ['type' => 'VARCHAR', 'constraint' => 96, 'null' => true];
        }
        if (! $this->db->fieldExists('ns_publication_id', 'publications')) {
            $fields['ns_publication_id'] = ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true];
        }
        if (! $this->db->fieldExists('sync_origin', 'publications')) {
            $fields['sync_origin'] = ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true];
        }
        if (! $this->db->fieldExists('last_synced_from', 'publications')) {
            $fields['last_synced_from'] = ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true];
        }
        if (! $this->db->fieldExists('content_hash', 'publications')) {
            $fields['content_hash'] = ['type' => 'CHAR', 'constraint' => 64, 'null' => true];
        }

        if ($fields !== []) {
            $this->forge->addColumn('publications', $fields);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('publications')) {
            return;
        }

        foreach (['content_hash', 'last_synced_from', 'sync_origin', 'ns_publication_id', 'sync_external_key'] as $field) {
            if ($this->db->fieldExists($field, 'publications')) {
                $this->forge->dropColumn('publications', $field);
            }
        }
    }
}
