<?php

namespace Jarenal;

use DI\Annotation\Inject;
use Doctrine\ODM\MongoDB\DocumentManager;
use Jarenal\Documents\Block;
use Jarenal\Documents\Transaction;
use Predis\Client;

class Node
{
    /**
     * @Inject("DI\Container")
     * @var \DI\Container $container
     */
    private $container;

    /**
     * @Inject("Jarenal\Daemon")
     * @var Daemon $daemon
     */
    private $daemon;

    public function start()
    {
        $this->createGenesisBlock();

        // Show statistics every 5 seconds
        $this->daemon->addPeriodicTimer(5, function () {

            $memory = memory_get_usage() / 1024;
            $formatted = number_format($memory, 3) . 'K';
            echo "Current memory usage: {$formatted}\n";

            /** @var Peers $peers */
            $peers = $this->container->get(Peers::class);
            $total_peers = $peers->count();
            echo "Peers online: {$total_peers}\n";

            $predis = $this->container->get(Client::class);
            $json = $predis->get('transactions');
            $transactions = json_decode($json, true);
            $total_transactions = $transactions ? count($transactions) : 0;
            echo "Pending transactions: {$total_transactions}\n";
        });

        // Check if blockchain is synchronized every 10
        $this->daemon->addPeriodicTimer(10, function () {

            if (!$this->isSynchronized()) {
                $this->synchronizeBlockchain();
            }
        });

        // Start mining every 20 seconds
        $this->daemon->addPeriodicTimer(20, function () {

            if ($this->isSynchronized()) {
                echo "Starting to mining block...\n";
                $this->mineBlock();
            }
        });

        $this->daemon->start();
    }

    private function createGenesisBlock()
    {
        $dm = $this->container->get(DocumentManager::class);
        $blocks = $dm->getRepository(Block::class)->findAll();

        if (!$blocks) {
            $tx = new Transaction();
            $tx->setFrom("")
                ->setTo("1111")
                ->setValue("100000");

            $block = new Block();
            $block->addTransaction($tx);
            $block->setHash("0000")
                ->setNonce(0)
                ->setPreviousHash("")
                ->setTimestamp(strtotime("2018-04-29 00:00:00"));
            $dm->persist($block);
            $dm->flush();
            $dm->clear();
            echo "---------------------\n";
            echo "Genesis block created\n";
            echo "---------------------\n";
        }
    }

    private function isSynchronized()
    {
        $httpClient = $this->container->get(HttpClient::class);

        // Get last remote block
        $lastRemoteBlock = $httpClient->getLastBlock();

        if ($lastRemoteBlock) {
            // Get last local block
            $lastLocalBlock = $this->getLastLocalBlock();
            $lastLocalBlockId = $lastLocalBlock->getId();
            echo "Last remote BlockId {$lastRemoteBlock["id"]} - Last local BlockId $lastLocalBlockId\n";
            if ($lastRemoteBlock["id"] > $lastLocalBlockId) {
                echo "Blockchain is outdated\n";
                return false;
            } else {
                echo "Blockchain is synchronized!\n";
                return true;
            }
        } else {
            return false;
        }
    }

    private function getLastLocalBlock()
    {
        $dm = $this->container->get(DocumentManager::class);
        return $dm->createQueryBuilder(Block::class)
            ->sort('timestamp', 'desc')
            ->getQuery()
            ->getSingleResult();
    }

    private function synchronizeBlockchain()
    {
        try {
            echo "Starting synchronization...\n";
            $httpClient = $this->container->get(HttpClient::class);
            $lastLocalBlock = $this->getLastLocalBlock();
            $blocks = $httpClient->getBlocksFromId($lastLocalBlock->getId());
            $dm = $this->container->get(DocumentManager::class);

            if ($blocks && is_array($blocks)) {
                foreach ($blocks as $current) {
                    $block = new Block();
                    $block->setNonce($current["nonce"]);
                    $block->setTimestamp($current["timestamp"]);
                    $block->setHash($current["hash"]);
                    $block->setPreviousHash($current["previous_hash"]);

                    foreach ($current["transactions"] as $currentTx) {
                        $transaction = new Transaction();
                        $transaction->setId($currentTx["id"]);
                        $transaction->setFrom($currentTx["from"]);
                        $transaction->setTo($currentTx["to"]);
                        $transaction->setValue($currentTx["value"]);
                        $block->addTransaction($transaction);
                    }

                    echo "Block with id " . $current["id"] . " imported\n";
                    $dm->persist($block);
                    $dm->flush();
                    $dm->clear();
                }

                echo "Synchronization finished!\n";
                $httpClient->broadcastMyPeer();
                return true;
            } else {
                echo "Synchronization failed, No blocks were received.\n";
                return false;
            }
        } catch (\Exception $ex) {
            // TODO log exception
            return false;
        }
    }

    private function mineBlock()
    {
        $predis = $this->container->get(Client::class);
        $json = $predis->get('transactions');
        $transactions = json_decode($json, true);
        $dm = $this->container->get(DocumentManager::class);

        if ($transactions) {
            $queryBuilder = $dm->createQueryBuilder(Block::class);
            $previousBlock = $queryBuilder->sort('timestamp', 'desc')
                ->getQuery()
                ->getSingleResult();

            $block = new Block();
            $block->setTimestamp(time())
                ->setPreviousHash($previousBlock->getHash());

            foreach ($transactions as $transaction) {
                $tx = new Transaction();
                $tx->setFrom($transaction['from'])
                    ->setTo($transaction['to'])
                    ->setValue($transaction['value']);
                $block->addTransaction($tx);
            }

            $pow = false;
            $nonce = 1;
            $zeros = "";

            for ($i = 0; $i < POW_DIFICULTY; $i++) {
                $zeros .= "0";
            }

            $pattern = '/^' . $zeros . '\w{60}$/';

            do {
                $hash = hash('sha256', serialize($block) . $nonce);

                if (preg_match($pattern, $hash) === 1) {
                    $pow = true;
                }
                $nonce++;
            } while ($pow === false);


            $block->setNonce($nonce);
            $block->setHash($hash);
            $dm->persist($block);
            $dm->flush();
            $dm->clear();

            $predis = $this->container->get(Client::class);
            $predis->set('transactions', json_encode([]));
            echo "---------------------\n";
            echo "New block generated with hash: $hash\n";
            echo "---------------------\n";
        } else {
            echo "---------------------\n";
            echo "Nothing for to mine\n";
            echo "---------------------\n";
        }
    }
}
