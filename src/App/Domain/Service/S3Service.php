<?php

namespace App\Domain\Service;

use Aws\S3\S3Client;

class S3Service
{
    private S3Client $s3Client;
    private string $bucket;

    public function __construct(S3Client $s3Client, string $bucket)
    {
        $this->s3Client = $s3Client;
        $this->bucket = $bucket;
    }

    /**
     * @return S3Client
     */
    public function getS3Client(): S3Client
    {
        return $this->s3Client;
    }

    /**
     * @return string
     */
    public function getBucket(): string
    {
        return $this->bucket;
    }

}
