<?php

namespace Jarenal;

use GuzzleHttp\Client;

class HttpClient
{
    private $peers;

    private $client;

    const TIMEOUT = 5;

    /**
     * HttpClient constructor.
     * @param Peers $peers
     */
    public function __construct(Peers $peers)
    {
        $this->peers = $peers;
        $this->client = new Client();

        $localPeers = $this->peers->getPeers();

        if (!$localPeers) {
            echo "No peers stored locally. Requesting peers remotely...\n";
            $remotePeers = $this->getPeers();
            $myIpAddress = $this->getMyPublicIp();

            if ($remotePeers) {
                echo "Remote peers received!\n";

                foreach ($remotePeers as $peer) {
                    if ($myIpAddress !== $peer["ip"]) {
                        $this->peers->add($peer);
                    }
                }
            } else {
                echo "No remote peers received :(\n";
            }

            // If this node is not a MASTER NODE then add seed peer to peers
            if (!MASTER_NODE) {
                $seedPeer = ["ip" => SEED_PEER_IP, "port" => SEED_PEER_PORT];
                $this->peers->add($seedPeer);
            }
        }
    }

    public function getPeers()
    {
        try {
            return $this->makeRequest("getPeers");
        } catch (\Exception $ex) {
            // TODO log exception
            return [];
        }
    }

    public function getLastBlock()
    {
        try {
            return $this->makeRequest("getLastBlock");
        } catch (\Exception $ex) {
            // TODO log exception
            return [];
        }
    }

    public function getBlocksFromId($fromId = 0)
    {
        try {
            return $this->makeRequest("getBlocks", ["fromId" => $fromId]);
        } catch (\Exception $ex) {
            // TODO log exception
            return [];
        }
    }

    public function getMyPublicIp()
    {
        try {
            $data = $this->makeRequest("ping");

            if (isset($data["ip"])) {
                return $data["ip"];
            } else {
                return "";
            }
        } catch (\Exception $ex) {
            // TODO log exception
            return [];
        }
    }

    public function broadcastMyPeer()
    {
        try {
            $myIpAddress = $this->getMyPublicIp();

            if ($myIpAddress) {
                $peers = $this->peers->getPeers();

                foreach ($peers as $peer) {
                    echo "Broadcasting my peer to {$peer["ip"]}:{$peer["port"]}\n";
                    $this->makeRequest("addPeer", ["ip" => $myIpAddress, "port" => PORT_API], $peer);
                }

                return true;
            } else {
                return false;
            }
        } catch (\Exception $ex) {
            // TODO log exception
            return false;
        }
    }

    private function makeRequest($method, $params = [], $peer = [])
    {
        try {
            if (!$peer) {
                $peer = $this->peers->pickPeerRandomly();
            }

            if ($peer) {
                $response = $this->client->request("POST", $peer["ip"] . ":" . $peer["port"], [
                    "timeout" => self::TIMEOUT,
                    "json" => ["jsonrpc" => "2.0", "method" => $method, "params" => $params, "id" => 1]
                ]);
                $body = $response->getBody();
                $json = (string)$body;
                $result = json_decode($json, true);
                return $result["data"];
            } else {
                return [];
            }
        } catch (\Exception $ex) {
            // TODO log exception
            return [];
        }
    }
}
