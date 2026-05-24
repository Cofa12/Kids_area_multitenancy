<?php

namespace App\Enums;

/**
 * Subscription action enum.
 *
 * Maps the incoming `action` + `userStatus` into three concrete cases:
 * - SUBSCRIBED_NEW: new subscriber (action=SUBSCRIBED, userStatus=1)
 * - SUBSCRIBED_RENEWAL: renewal/update (action=SUBSCRIBED, userStatus!=1)
 * - UNSUBSCRIBED: unsubscribe (action=UNSUBSCRIBED)
 */
enum SubscriptionAction: string
{
    case SUBSCRIBED_NEW = 'SUBSCRIBED_NEW';
    case SUBSCRIBED_RENEWAL = 'SUBSCRIBED_RENEWAL';
    case UNSUBSCRIBED = 'UNSUBSCRIBED';

    public static function fromCallback(string $action, int $userStatus): self
    {
        $action = strtoupper(trim($action));

        if ($action === 'UNSUBSCRIBED') {
            return self::UNSUBSCRIBED;
        }

        if ($action === 'SUBSCRIBED') {
            return $userStatus === 1 ? self::SUBSCRIBED_NEW : self::SUBSCRIBED_RENEWAL;
        }

        // Fallback: use userStatus to decide between new or renewal
        return $userStatus === 1 ? self::SUBSCRIBED_NEW : self::SUBSCRIBED_RENEWAL;
    }
}
