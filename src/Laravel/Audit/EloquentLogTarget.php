<?php

namespace Devkit\Laravel\Audit;

use Devkit\Logging\Contract\LogTargetContract;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EloquentLogTarget implements LogTargetContract
{
    /**
     * @var string|null
     */
    protected $table;

    public function __construct($table = null)
    {
        $this->table = $table;
    }

    public function save(array $entry)
    {
        $table = $this->table ?: $this->tableFromEntry($entry);

        DB::table($table)->insert(array(
            'entity_id' => isset($entry['entity_id']) ? $entry['entity_id'] : null,
            'action' => isset($entry['action']) ? $entry['action'] : '',
            'changes' => json_encode(isset($entry['changes']) ? $entry['changes'] : array()),
            'user_id' => isset($entry['user_id']) ? $entry['user_id'] : null,
            'created_at' => isset($entry['created_at']) ? $entry['created_at'] : date('Y-m-d H:i:s'),
        ));
    }

    protected function tableFromEntry(array $entry)
    {
        if (!empty($entry['entity_table'])) {
            return $entry['entity_table'] . '_logs';
        }

        throw new InvalidArgumentException('EloquentLogTarget requires a table or entity_table entry.');
    }
}
