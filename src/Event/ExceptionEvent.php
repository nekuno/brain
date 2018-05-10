<?php

namespace Event;


use Symfony\Component\EventDispatcher\Event;

class ExceptionEvent extends Event
{

    /**
     * @var \Exception
     */
    protected $exception;

    /**
     * @var \DateTime
     */
    protected $datetime;

    /**
     * @var string
     */
    protected $process;

    /**
     * ExceptionEvent constructor.
     * @param \Exception $exception
     * @param string $process
     */
    public function __construct(\Exception $exception, $process = null)
    {
        $this->exception = $exception;
        $this->process = $process;
        $this->datetime = new \DateTime();
    }

    /**
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @param \Exception $exception
     */
    public function setException($exception)
    {
        $this->exception = $exception;
    }

    /**
     * @return \DateTime
     */
    public function getDatetime()
    {
        return $this->datetime;
    }

    /**
     * @param \DateTime $datetime
     */
    public function setDatetime($datetime)
    {
        $this->datetime = $datetime;
    }


    /**
     * @return string
     */
    public function getProcess()
    {
        return $this->process;
    }

    /**
     * @param string $process
     */
    public function setProcess($process)
    {
        $this->process = $process;
    }



}