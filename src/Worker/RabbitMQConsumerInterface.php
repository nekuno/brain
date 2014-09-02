<?php
/**
 * @author adrian.web.dev@gmail.com
 */

namespace Worker;


interface RabbitMQConsumerInterface {

    /**
     * Starts to process queued tasks
     */
    public function consume();

}
