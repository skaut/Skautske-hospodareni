<?php

namespace Model\Bank\Http;

interface IClient
{

    /**
     * @param string $url
     * @param int $timeout
     * @return Response
     */
    public function get($url, $timeout);

}
