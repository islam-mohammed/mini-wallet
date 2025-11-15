import { onBeforeUnmount } from 'vue'
import type { TransactionCreatedPayload } from '@/lib/wallet-realtime'
import { subscribeToUserWallet } from '@/lib/wallet-realtime'

export function useWalletRealtime(
  userId: number,
  onEvent: (payload: TransactionCreatedPayload) => void,
) {
  const unsubscribe = subscribeToUserWallet(userId, onEvent)

  onBeforeUnmount(() => {
    unsubscribe()
  })
}
