<?php

namespace App\Jobs\GenerateCatalog;


class GeneratePricesFileChunkJob extends AbstractJob
{
    protected $chunk;
    protected $fileNum;

    /**
     * Create a new job instance.
     *
     * @param mixed $chunk
     * @param int $fileNum
     * @return void
     */
    public function __construct($chunk, $fileNum)
    {
        parent::__construct();

        $this->chunk = $chunk;
        $this->fileNum = $fileNum;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->debug("Processing chunk {$this->fileNum}");
        parent::handle();
    }
}
