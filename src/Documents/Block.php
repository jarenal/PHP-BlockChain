<?php

namespace Jarenal\Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Block
{
    /** @ODM\Id(strategy="INCREMENT", type="int") */
    private $id;

    /** @ODM\Field(type="int") */
    private $nonce;

    /** @ODM\Field(type="int") */
    private $timestamp;

    /** @ODM\Field(type="string") */
    private $hash;

    /** @ODM\Field(type="string") */
    private $previous_hash;

    /**
     * @ODM\ReferenceMany(targetDocument="Transaction", cascade={"persist"})
     */
    private $transactions = [];

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getNonce()
    {
        return $this->nonce;
    }

    /**
     * @param mixed $nonce
     * @return Block
     */
    public function setNonce($nonce)
    {
        $this->nonce = $nonce;
        return $this;
    }

    /**
     * @param mixed $id
     * @return Block
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param int $timestamp
     * @return Block
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param mixed $hash
     * @return Block
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
        return $this;
    }

    /**
     * @return string
     */
    public function getPreviousHash()
    {
        return $this->previous_hash;
    }

    /**
     * @param string $previous_hash
     * @return Block
     */
    public function setPreviousHash($previous_hash)
    {
        $this->previous_hash = $previous_hash;
        return $this;
    }

    public function addTransaction(Transaction $transaction)
    {
        $this->transactions[] = $transaction;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * @param mixed $transactions
     */
    public function setTransactions($transactions)
    {
        $this->transactions = $transactions;
    }
}
