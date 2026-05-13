<?php

declare(strict_types=1);

namespace Domain\Contact\Exceptions;

use DomainException;

/**
 * Lançada quando um e-mail em formato inválido é fornecido ao Value Object Email.
 */
final class InvalidEmailException extends DomainException {}
