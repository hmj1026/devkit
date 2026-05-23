<?php

namespace Devkit\Core\Exception\Contract;

/**
 * Declares whether an exception type should be reported to the surrounding
 * framework's logger / monitoring channel. Frameworks (e.g. Laravel) consult
 * this contract from their exception handler before deciding to write a log
 * entry or notify an external service.
 */
interface ReportExceptionContract
{
    /**
     * Return true when the surrounding framework's exception handler should
     * report (log / notify) this exception, false to suppress.
     *
     * @return bool
     */
    public function shouldReport();
}
