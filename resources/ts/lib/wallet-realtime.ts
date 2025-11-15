import type { Channel } from 'laravel-echo'
import { echo } from './echo'

export interface TransactionCreatedPayload {
  transaction: {
    id: string
    sender_id: number
    receiver_id: number
    amount: string
    commission_fee: string
    created_at: string | null
    sender: {
      id: number
      name: string
    }
    receiver: {
      id: number
      name: string
    }
  }
  sender_balance: string
  receiver_balance: string
}

/**
 * Subscribe to the authenticated user's wallet channel.
 * Returns a function to unsubscribe.
 */
export function subscribeToUserWallet(
  userId: number,
  handler: (payload: TransactionCreatedPayload) => void,
): () => void {
  const channel: Channel = echo.private(`user.${userId}`)

  channel.listen('.transaction.created', (event: TransactionCreatedPayload) => {
    handler(event)
  })

  return () => {
    echo.leave(`private-user.${userId}`)
  }
}
