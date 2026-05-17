<?php

declare(strict_types=1);

namespace Domain\Contact\Exceptions;

use DomainException;

/**
 * Lançada quando um score com valor negativo é instanciado.
 */
final class InvalidScoreException extends DomainException {}
