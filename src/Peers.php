<?php

namespace Jarenal;

use DI\Annotation\Inject;

class Peers
{
    /**
     * @Inject("Predis\Client")
     * @var \Predis\Client $predis
     */
    private $predis;

    public function count()
    {
        $peers = $this->getPeers();

        return $peers ? count($peers) : 0;
    }

    public function getPeers()
    {
        $json = $this->predis->get('peers');
        return json_decode($json, true);
    }

    public function add($data)
    {
        $peers = $this->getPeers();
        $peers[md5($data['ip'] . ":" . $data['port'])] = $data;
        $this->predis->set('peers', json_encode($peers));
    }

    public function pickPeerRandomly()
    {
        $peers = $this->getPeers();

        if ($peers && is_array($peers)) {
            $randomKey = array_rand($peers, 1);
            return $peers[$randomKey];
        } else {
            return [];
        }
    }
}
