<?php


namespace Model\Bank\Fio;


use FioApi\Downloader;

interface IDownloaderFactory
{

    public function create(string $token): Downloader;

}
