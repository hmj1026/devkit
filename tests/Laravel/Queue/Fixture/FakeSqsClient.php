<?php

namespace Aws\Sqs;

if (!class_exists('Aws\\Sqs\\SqsClient', false)) {
    class SqsClient
    {
        public $messages = array();

        public function __construct(array $config = array())
        {
        }

        public function sendMessage(array $params)
        {
            $this->messages[] = $params;

            return new class {
                public function get($key)
                {
                    return $key === 'MessageId' ? 'msg-1' : null;
                }
            };
        }
    }
}
