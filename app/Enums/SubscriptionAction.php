<?php

namespace App\Enums;

/**
 * Subscription action enum.
 *
 * Maps the incoming `action` + `userStatus` into three concrete cases:
 * - SUBSCRIBED_NEW: new subscriber (action=SUBSCRIBED, userStatus=1)
 * - SUBSCRIBED_RENEWAL: renewal/update (action=SUBSCRIBED, userStatus!=1)
 * - UNSUBSCRIPTION: unsubscribe (action=UNSUBSCRIPTION)
 */
enum SubscriptionAction: string
{
    case SUBSCRIBED_NEW = 'SUBSCRIBED_NEW';
    case SUBSCRIBED_RENEWAL = 'SUBSCRIBED_RENEWAL';
    case UNSUBSCRIPTION = 'UNSUBSCRIPTION';

    public static function fromCallback(string $action, int $userStatus): self
    {
        $action = strtoupper(trim($action));

        if ($action === 'UNSUBSCRIPTION') {
            return self::UNSUBSCRIPTION;
        }

        if ($action === 'SUBSCRIPTION') {
             // Determine new vs renewal based on userStatus
             return $userStatus === 1 ? self::SUBSCRIBED_NEW : self::SUBSCRIBED_RENEWAL;
            return $userStatus === 1 ? self::SUBSCRIBED_NEW : self::SUBSCRIBED_RENEWAL;
        }

        // Fallback: use userStatus to decide between new or renewal
        return $userStatus === 1 ? self::SUBSCRIBED_NEW : self::SUBSCRIBED_RENEWAL;
    }
}
