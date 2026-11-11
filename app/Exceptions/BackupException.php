<?php

namespace App\Exceptions;

use Exception;

class BackupException extends Exception
{
    protected string $mensajeUsuario;

    public function __construct(string $message, string $mensajeUsuario = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->mensajeUsuario = $mensajeUsuario ?: $message;
    }

    public function getMensajeUsuario(): string
    {
        return $this->mensajeUsuario;
    }
}
