<?php

declare(strict_types=1);

namespace Domain\Contact\Exceptions;

use DomainException;

/**
 * Lançada quando um telefone em formato inválido é fornecido ao Value Object Phone.
 */
final class InvalidPhoneException extends DomainException {}
