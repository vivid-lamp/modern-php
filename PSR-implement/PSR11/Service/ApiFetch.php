<?php

namespace PsrImplement\PSR11\Service;

use Laminas\Diactoros\Response;


class ApiFetch
{
    public function success($msg = '', $data = null)
    {
        return $this->result($msg, 0, $data);
    }

    public function result($msg = '', $code = 0, $data = null)
    {
        $response = new Response();
        $response = $response->withHeader('Content-type', 'application/json');

        $response->getBody()->write(json_encode([
            'msg' => $msg,
            'code' => $code,
            'data' => $data,
        ]));
        return $response;
    }
}
