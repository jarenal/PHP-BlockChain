<?php

namespace Jarenal\Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Transaction
{
    /** @ODM\Id */
    private $id;

    /** @ODM\Field(type="string") */
    private $from;

    /** @ODM\Field(type="string") */
    private $to;

    /** @ODM\Field(type="int") */
    private $value = 0;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return Transaction
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getFrom(): string
    {
        return $this->from;
    }

    /**
     * @param string $from
     * @return Transaction
     */
    public function setFrom(string $from): Transaction
    {
        $this->from = $from;
        return $this;
    }

    /**
     * @return string
     */
    public function getTo(): string
    {
        return $this->to;
    }

    /**
     * @param string $to
     * @return Transaction
     */
    public function setTo(string $to): Transaction
    {
        $this->to = $to;
        return $this;
    }

    /**
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * @param int $value
     * @return Transaction
     */
    public function setValue(int $value): Transaction
    {
        $this->value = $value;
        return $this;
    }
}
