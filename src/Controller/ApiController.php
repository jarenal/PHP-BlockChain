<?php

namespace Jarenal\Controller;

use DI\Annotation\Inject;
use Doctrine\ODM\MongoDB\DocumentManager;
use Jarenal\Documents\Transaction;
use Predis\Client;
use React\Http\Response;
use Elliptic\EC;
use Jarenal\Documents\Block;

class ApiController
{
    /**
     * @Inject("DI\Container")
     * @var \DI\Container $container
     */
    private $container;

    /**
     * @Inject("Jarenal\Peers")
     * @var \Jarenal\Peers $peers
     */
    private $peers;

    const WALLETS_PATH = __DIR__ . "/../../wallets";

    private function sendTransaction($data)
    {
        try {
            if ($data && count($data)) {
                $predis = $this->container->get(Client::class);
                $json = $predis->get('transactions');
                $transactions = json_decode($json, true);

                foreach ($data as $tx) {
                    $hash = hash('sha256', serialize($tx));
                    $transactions[$hash] = $tx;
                }

                $predis->set('transactions', json_encode($transactions));
            }

            return true;
        } catch (\Exception $ex) {
            // TODO send to log
            throw $ex;
        }
    }

    private function addPeer($data)
    {
        try {
            $this->peers->add($data);
            return true;
        } catch (\Exception $ex) {
            // TODO send to log
            throw $ex;
        }
    }

    private function createWallet($data)
    {
        try {
            $ec = new EC('secp256k1');
            /** @var \Elliptic\EC\KeyPair $key */
            $key = $ec->genKeyPair();
            $private = $key->getPrivate()->toString('hex');
            $public = $key->getPublic()->jsonSerialize()[0]->toString('hex');
            $data = ["alias" => $data['alias'], "private" => $private, "public" => $public];
            $walletPath = self::WALLETS_PATH . "/$public.json";
            $fp = fopen($walletPath, 'w');
            fwrite($fp, json_encode($data));
            fclose($fp);
            return true;
        } catch (\Exception $ex) {
            // TODO send to log
            throw $ex;
        }
    }

    private function getWallets()
    {

        try {
            $wallets = [];
            $files = [];

            if ($handle = opendir(self::WALLETS_PATH)) {
                while (false !== ($entry = readdir($handle))) {
                    if ($entry != "." && $entry != "..") {
                        $files[] = $entry;
                    }
                }
                closedir($handle);
            }

            foreach ($files as $file) {
                $data = file_get_contents(self::WALLETS_PATH . "/$file");
                $walletData = json_decode($data, true);
                unset($walletData['private']);
                $wallets[] = $walletData;
            }

            return $wallets;
        } catch (\Exception $ex) {
            // TODO send to log
            throw $ex;
        }
    }

    private function getBlocks($fromId = 0)
    {
        try {
            $dm = $this->container->get(DocumentManager::class);
            $documents = $dm->createQueryBuilder(Block::class)
                ->field('id')->gt($fromId)
                ->sort('timestamp', 'asc')
                ->getQuery()
                ->execute();

            $blocks = [];
            /** @var Block $record */
            foreach ($documents as $record) {
                $current = [
                    "id" => $record->getId(),
                    "nonce" => $record->getNonce(),
                    "timestamp" => $record->getTimestamp(),
                    "hash" => $record->getHash(),
                    "previous_hash" => $record->getPreviousHash(),
                    "total_transactions" => count($record->getTransactions()),
                    "transactions" => []
                ];

                /** @var Transaction $transaction */
                foreach ($record->getTransactions() as $transaction) {
                    $current["transactions"][] = [
                        "id" => $transaction->getId(),
                        "from" => $transaction->getFrom(),
                        "to" => $transaction->getTo(),
                        "value" => $transaction->getValue()
                    ];
                }
                $blocks[] = $current;
            }

            return $blocks;
        } catch (\Exception $ex) {
            // TODO send to log
            throw $ex;
        }
    }

    private function getLastBlock()
    {
        try {
            $dm = $this->container->get(DocumentManager::class);
            $document = $dm->createQueryBuilder(Block::class)
                ->sort('timestamp', 'desc')
                ->getQuery()
                ->getSingleResult();

            $block = [
                "id" => $document->getId(),
                "nonce" => $document->getNonce(),
                "timestamp" => $document->getTimestamp(),
                "hash" => $document->getHash(),
                "previousHash" => $document->getPreviousHash(),
                "transactions" => count($document->getTransactions())
            ];

            return $block;
        } catch (\Exception $ex) {
            // TODO send to log
            throw $ex;
        }
    }

    public function execute($data, $serverParams = [])
    {

        $output = [
            "status" => 200,
            "content-type" => "application/json",
            "body" => ["status" => "success", "data" => []]
        ];

        switch ($data['method']) {
            case 'sendTransaction':
                try {
                    $this->sendTransaction($data["params"]);
                } catch (\Exception $ex) {
                    $output['body'] = ["status" => "fail", "message" => $ex->getMessage()];
                }
                break;
            case 'addPeer':
                try {
                    $this->addPeer($data["params"]);
                } catch (\Exception $ex) {
                    $output['body'] = ["status" => "fail", "message" => $ex->getMessage()];
                }
                break;
            case 'getPeers':
                try {
                    $peers = $this->peers->getPeers();
                    $peers[md5(SEED_PEER_IP . ":" . SEED_PEER_PORT)] = ["ip" => SEED_PEER_IP, "port" => SEED_PEER_PORT];
                    $output['body'] = ["status" => "success", "data" => array_values($peers)];
                } catch (\Exception $ex) {
                    $output['body'] = ["status" => "fail", "message" => $ex->getMessage()];
                }
                break;
            case 'createWallet':
                try {
                    $this->createWallet($data["params"]);
                } catch (\Exception $ex) {
                    $output['body'] = ["status" => "fail", "message" => $ex->getMessage()];
                }
                break;
            case 'getWallets':
                try {
                    $wallets = $this->getWallets();
                    $output['body'] = ["status" => "success", "data" => $wallets];
                } catch (\Exception $ex) {
                    $output['body'] = ["status" => "fail", "message" => $ex->getMessage()];
                }
                break;
            case 'getBlocks':
                try {
                    $fromId = isset($data["params"]["fromId"]) ? $data["params"]["fromId"] : 0;
                    $blocks = $this->getBlocks($fromId);
                    $output['body'] = ["status" => "success", "data" => $blocks];
                } catch (\Exception $ex) {
                    $output['body'] = ["status" => "fail", "message" => $ex->getMessage()];
                }
                break;
            case 'getLastBlock':
                try {
                    $block = $this->getLastBlock();
                    $output['body'] = ["status" => "success", "data" => $block];
                } catch (\Exception $ex) {
                    $output['body'] = ["status" => "fail", "message" => $ex->getMessage()];
                }
                break;
            case 'ping':
                $remoteAddress = isset($serverParams["REMOTE_ADDR"]) ? $serverParams["REMOTE_ADDR"] : "";
                $output['body'] = ["status" => "pong", "data" => ["ip" => $remoteAddress]];
                break;
            default:
                $output = [
                    "status" => 404,
                    "content-type" => "application/json",
                    "body" => ["status" => "fail", "message" => "Not found"]
                ];
                break;
        }

        return new Response(
            $output['status'],
            array(
                'Content-Type' => $output['content-type']
            ),
            json_encode($output['body'])
        );
    }
}
