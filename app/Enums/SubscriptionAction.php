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

    public static function fromCallback(string $action, int $userStatus, bool $userExists = false): self
    {
        $action = strtoupper(trim($action));

        if ($action === 'UNSUBSCRIPTION') {
            return self::UNSUBSCRIPTION;
        }

        if ($action === 'RENEWAL' || $action === 'SUBSCRIBED_RENEWAL') {
            return self::SUBSCRIBED_RENEWAL;
        }

        // If the user already exists, any subscription flow is treated as a renewal rather than a new subscription
        if ($userExists) {
            return self::SUBSCRIBED_RENEWAL;
        }

        if ($action === 'SUBSCRIPTION') {
            if ($userStatus === 0) {
                return self::SUBSCRIBED_RENEWAL;
            }
            return self::SUBSCRIBED_NEW;
        }

        return self::SUBSCRIBED_NEW;
    }
}
