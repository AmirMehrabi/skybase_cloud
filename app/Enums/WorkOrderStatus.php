<?php

namespace App\Enums;

enum WorkOrderStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Triaged = 'triaged';
    case Scheduled = 'scheduled';
    case Dispatched = 'dispatched';
    case InProgress = 'in_progress';
    case AwaitingCustomer = 'awaiting_customer';
    case Blocked = 'blocked';
    case ReadyForActivation = 'ready_for_activation';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Failed = 'failed';

    /** @return list<string> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Submitted->value, self::Cancelled->value],
            self::Submitted => [self::Triaged->value, self::Cancelled->value],
            self::Triaged => [self::Scheduled->value, self::Blocked->value, self::Cancelled->value],
            self::Scheduled => [self::Dispatched->value, self::Blocked->value, self::Cancelled->value],
            self::Dispatched => [self::InProgress->value, self::Blocked->value, self::Cancelled->value],
            self::InProgress => [self::AwaitingCustomer->value, self::Blocked->value, self::ReadyForActivation->value, self::Completed->value, self::Failed->value],
            self::AwaitingCustomer, self::Blocked => [self::Triaged->value, self::Scheduled->value, self::InProgress->value, self::Cancelled->value],
            self::ReadyForActivation => [self::Completed->value, self::Blocked->value, self::Failed->value],
            self::Failed => [self::Triaged->value, self::Cancelled->value],
            self::Completed, self::Cancelled => [],
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled], true);
    }
}
