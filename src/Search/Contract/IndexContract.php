<?php

namespace Devkit\Search\Contract;

/**
 * Eloquent-like base contract for ES index documents. Concrete subclasses
 * (Devkit\Search\Index\Index) declare the index mapping via
 * getMapping() and may opt into partitioning via getPartition() to
 * spread writes across date- or tenant-bucketed indices.
 *
 * Pure PHP — no Illuminate imports.
 */
interface IndexContract
{
    /**
     * Return the base index name (without partition suffix).
     *
     * @return string
     */
    public function getIndex();

    /**
     * Return the native ES mapping DSL for this index as a raw array.
     *
     * @return array
     */
    public function getMapping();

    /**
     * Return the partition suffix for this document (e.g. "2026-05"),
     * or null when the index is unpartitioned.
     *
     * @return string|null
     */
    public function getPartition();

    /**
     * Persist this document. Implementations choose between index() and
     * update() per the document's lifecycle (new vs existing _id). The
     * optional `$attributes` argument is mass-assigned before the write
     * — implementations MUST treat omission as "use whatever attributes
     * the document already carries".
     *
     * @param  array<string, mixed>  $attributes  Optional partial body to merge.
     * @return mixed  Provider response array.
     */
    public function save(array $attributes = array());
}
