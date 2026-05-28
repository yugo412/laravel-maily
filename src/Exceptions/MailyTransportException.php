<?php

declare(strict_types=1);

namespace Yugo\Maily\Exceptions;

use Symfony\Component\Mailer\Exception\TransportException;

class MailyTransportException extends TransportException {}
