<?php

declare(strict_types=1);

namespace App\Domain\Downloads\Exceptions;

use RuntimeException;

final class DownloadConcurrencyException extends RuntimeException
{
}
