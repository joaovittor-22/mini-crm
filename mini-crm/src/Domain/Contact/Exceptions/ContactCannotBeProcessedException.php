<?php

declare(strict_types=1);

namespace Domain\Contact\Exceptions;

use DomainException;

/**
 * Lançada quando se tenta processar um contato que não está em um estado
 * que permita o início do processamento de score.
 */
final class ContactCannotBeProcessedException extends DomainException {}
